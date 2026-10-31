<?php

namespace App\Jobs;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\RefundService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Safety net for payments that did not finish their last step:
 *  1. A cancelled booking whose refund failed gets the refund retried.
 *  2. A reservation still held for a booking that is already declined, expired
 *     or cancelled is released.
 * Each step is idempotent (gateway keys + status checks), so re-running is harmless.
 */
class ReconcilePayments implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** @return array{refunds: int, releases: int} */
    public function handle(RefundService $refunds, PaymentService $payments): array
    {
        $result = ['refunds' => 0, 'releases' => 0];

        Booking::query()->with(['payment', 'canceller'])
            ->where('status', BookingStatus::Cancelled->value)
            ->whereHas('payment', fn ($q) => $q->where('status', PaymentStatus::Captured->value))
            ->each(function (Booking $booking) use ($refunds, &$result) {
                try {
                    // Same clock as at cancellation time, so a late retry never changes the amount owed.
                    $amount = $refunds->refundCancelledBooking($booking, $booking->canceller, $booking->cancelled_at);
                    $result['refunds'] += bccomp($amount, '0', 3) > 0 ? 1 : 0;
                } catch (Throwable $e) {
                    report($e);
                }
            });

        Payment::query()->with('booking')
            ->where('status', PaymentStatus::Authorised->value)
            ->whereHas('booking', fn ($q) => $q->whereIn('status', [
                BookingStatus::Declined->value, BookingStatus::Expired->value, BookingStatus::Cancelled->value,
            ]))
            ->each(function (Payment $payment) use ($payments, &$result) {
                try {
                    $payments->release($payment, null, 'reconciled: booking is closed');
                    $result['releases']++;
                } catch (Throwable $e) {
                    report($e);
                }
            });

        return $result;
    }
}
