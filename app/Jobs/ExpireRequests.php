<?php

namespace App\Jobs;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Requests the host did not answer within 12 hours become `expired` and the
 * held amount is released. Safe to run twice: expire() does nothing for a
 * booking that is no longer a due request.
 */
class ExpireRequests implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(BookingService $bookings): int
    {
        $expired = 0;

        Booking::query()
            ->where('status', BookingStatus::Requested->value)
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->each(function (Booking $booking) use ($bookings, &$expired) {
                try {
                    $expired += $bookings->expire($booking) ? 1 : 0;
                } catch (Throwable $e) {
                    // One stuck booking (for example a gateway outage) must not block the others; the next run retries it.
                    report($e);
                }
            });

        return $expired;
    }
}
