<?php

namespace App\Jobs;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
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
 *  0. A request whose payment the gateway already captured (its answer was lost) is
 *     confirmed, or refunded and declined if the slot is gone.
 *  1. A cancelled booking whose refund failed gets the refund retried.
 *  2. A reservation still held for a booking that is already declined, expired
 *     or cancelled is released.
 * Each step is idempotent (gateway keys + status checks), so re-running is harmless.
 */
class ReconcilePayments implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** @return array{refunds: int, releases: int, recovered: int} */
    public function handle(RefundService $refunds, PaymentService $payments, ?BookingService $bookings = null): array
    {
        $bookings ??= app(BookingService::class);
        $result = ['refunds' => 0, 'releases' => 0, 'recovered' => 0];

        // 0. Money captured at the gateway whose answer we never received: ask the gateway and finish the job.
        Booking::query()->with('payment')
            ->where('status', BookingStatus::Requested->value)
            ->whereHas('payment', fn ($q) => $q->where('status', PaymentStatus::Authorised->value)->where('updated_at', '<', now()->subMinute()))
            ->each(function (Booking $booking) use ($bookings, &$result) {
                try {
                    $result['recovered'] += $bookings->recoverPaidRequest($booking) ? 1 : 0;
                } catch (Throwable $e) {
                    report($e);
                }
            });

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
