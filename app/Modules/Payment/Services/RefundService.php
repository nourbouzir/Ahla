<?php

namespace App\Modules\Payment\Services;

use App\Events\RefundRequested;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Policies\HostCancellationRefundPolicy;
use App\Modules\Payment\Policies\RefundPolicy;
use App\Modules\Payment\Policies\TouristCancellationRefundPolicy;
use Carbon\CarbonInterface;

/**
 * Turns "this booking was cancelled by X" into money going back. It picks the
 * policy; the policy decides the amount; PaymentService moves the money.
 */
class RefundService
{
    public function __construct(private readonly PaymentService $payments) {}

    /** @return string The amount refunded (3 decimals), "0.000" when nothing is owed. */
    public function refundCancelledBooking(Booking $booking, User $canceller, ?CarbonInterface $now = null): string
    {
        $payment = $booking->payment;

        if ($payment === null) {
            return '0.000';
        }

        $amount = $this->policyFor($booking, $canceller)->refundAmount($booking, (string) $payment->amount, $now ?? now());

        // Only a captured payment can be refunded; a still-authorised one is released instead.
        if ($payment->status->value === 'authorised') {
            $this->payments->release($payment, $canceller, 'booking cancelled before capture');

            return '0.000';
        }

        $this->payments->refund($payment, $amount, $canceller, 'refund after cancellation');
        RefundRequested::dispatch($payment->id, $booking->id, $amount, $booking->cancellation_reason);

        return $amount;
    }

    public function policyFor(Booking $booking, User $canceller): RefundPolicy
    {
        return $canceller->id === $booking->host_id
            ? new HostCancellationRefundPolicy
            : new TouristCancellationRefundPolicy;
    }
}
