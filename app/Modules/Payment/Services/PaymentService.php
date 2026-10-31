<?php

namespace App\Modules\Payment\Services;

use App\Contracts\Payments\GatewayTimeoutException;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayResponse;
use App\Events\PaymentStatusChanged;
use App\Models\User;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use App\Modules\Payment\Models\Payment;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * The only place that talks to the PaymentGateway and the only writer of
 * payments.status. Each step is idempotent: the gateway call carries a key
 * built from the payment, and repeating a step that already happened is a no-op.
 * Never stores card data - only the gateway reference.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly AuditLogger $audit,
    ) {}

    /** Reserve the amount when the booking is requested. A failure throws and the caller rolls back. */
    public function authorise(Booking $booking, ?User $actor = null): Payment
    {
        $amount = bcadd((string) $booking->displayed_price, '0', 3);
        $currency = config('ahla.currency', 'TND');

        $response = $this->callGateway(
            fn () => $this->gateway->authorise($amount, $currency, "booking-{$booking->id}-authorise"),
            PaymentStatus::Authorised,
        );

        return DB::transaction(function () use ($booking, $amount, $currency, $response, $actor) {
            $payment = new Payment(['booking_id' => $booking->id, 'amount' => $amount, 'currency' => $currency]);
            $payment->forceFill([
                'gateway_ref' => $response->gatewayReference,
                'status' => PaymentStatus::Authorised,
                'authorised_at' => now(),
            ])->save();

            $this->changed($payment, null, PaymentStatus::Authorised, $actor, 'authorised on request');

            return $payment;
        });
    }

    /** Charge the reserved amount when the host accepts. Not captured means not confirmed. */
    public function capture(Payment $payment, ?User $actor = null): Payment
    {
        if ($payment->status === PaymentStatus::Captured) {
            return $payment;
        }

        $this->requireStatus($payment, PaymentStatus::Authorised);
        $this->callGateway(
            fn () => $this->gateway->capture($payment->gateway_ref, "payment-{$payment->id}-capture"),
            PaymentStatus::Captured,
        );

        return $this->transition($payment, PaymentStatus::Captured, ['captured_at' => now()], $actor, 'captured on acceptance');
    }

    /** Give the reservation back (request declined or expired). */
    public function release(Payment $payment, ?User $actor = null, string $reason = 'released'): Payment
    {
        if ($payment->status === PaymentStatus::Released) {
            return $payment;
        }

        $this->requireStatus($payment, PaymentStatus::Authorised);
        $this->callGateway(
            fn () => $this->gateway->release($payment->gateway_ref, "payment-{$payment->id}-release"),
            PaymentStatus::Released,
        );

        return $this->transition($payment, PaymentStatus::Released, ['released_at' => now()], $actor, $reason);
    }

    /** Return money after a capture. A zero amount does nothing (late tourist cancellation). */
    public function refund(Payment $payment, string $amount, ?User $actor = null, string $reason = 'refunded'): Payment
    {
        $amount = bcadd($amount, '0', 3);

        if ($payment->status === PaymentStatus::Refunded || bccomp($amount, '0', 3) === 0) {
            return $payment;
        }

        $this->requireStatus($payment, PaymentStatus::Captured);

        if (bccomp($amount, (string) $payment->amount, 3) > 0) {
            throw new PaymentFailedException('Refund is larger than the amount paid.');
        }

        $this->callGateway(
            fn () => $this->gateway->refund($payment->gateway_ref, $amount, "payment-{$payment->id}-refund"),
            PaymentStatus::Refunded,
        );

        return $this->transition(
            $payment,
            PaymentStatus::Refunded,
            ['refunded_amount' => $amount, 'refunded_at' => now()],
            $actor,
            $reason,
        );
    }

    /**
     * A status reported by the provider's webhook. Only forward moves are
     * accepted; repeating the current status is a harmless no-op.
     */
    public function applyGatewayStatus(Payment $payment, PaymentStatus $reported): Payment
    {
        if ($payment->status === $reported) {
            return $payment;
        }

        $allowed = [
            PaymentStatus::Authorised->value => [PaymentStatus::Captured, PaymentStatus::Released, PaymentStatus::Failed],
            PaymentStatus::Captured->value => [PaymentStatus::Refunded],
        ];

        if (! in_array($reported, $allowed[$payment->status->value] ?? [], true)) {
            throw new PaymentFailedException("Cannot move a {$payment->status->value} payment to {$reported->value}.");
        }

        $extra = match ($reported) {
            PaymentStatus::Captured => ['captured_at' => now()],
            PaymentStatus::Released => ['released_at' => now()],
            PaymentStatus::Refunded => ['refunded_amount' => (string) $payment->amount, 'refunded_at' => now()],
            default => [],
        };

        return $this->transition($payment, $reported, $extra, null, 'reported by gateway webhook');
    }

    /** @param  Closure(): PaymentGatewayResponse  $call */
    private function callGateway(Closure $call, PaymentStatus $expected): PaymentGatewayResponse
    {
        try {
            $response = $call();
        } catch (GatewayTimeoutException $e) {
            // A timeout is never success (BR-05).
            throw new PaymentFailedException('The payment gateway timed out.', 0, $e);
        }

        if ($response->status !== $expected) {
            throw new PaymentFailedException("The payment gateway answered {$response->status->value}.");
        }

        return $response;
    }

    private function requireStatus(Payment $payment, PaymentStatus $required): void
    {
        if ($payment->status !== $required) {
            throw new PaymentFailedException("Payment is {$payment->status->value}, expected {$required->value}.");
        }
    }

    /** @param  array<string, mixed>  $extra */
    private function transition(Payment $payment, PaymentStatus $to, array $extra, ?User $actor, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $to, $extra, $actor, $reason) {
            // Re-read under a lock so two workers cannot both apply the same step.
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;
            $locked->forceFill(['status' => $to] + $extra)->save();
            $this->changed($locked, $from, $to, $actor, $reason);

            return $locked;
        });
    }

    private function changed(Payment $payment, ?PaymentStatus $from, PaymentStatus $to, ?User $actor, string $reason): void
    {
        $this->audit->record('payment', $payment->id, $from, $to, $actor, $reason);
        PaymentStatusChanged::dispatch($payment->id, $payment->booking_id, $from?->value ?? '', $to->value);
    }
}
