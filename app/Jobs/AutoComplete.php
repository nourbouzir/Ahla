<?php

namespace App\Jobs;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Confirmed bookings become `completed` two hours after they end. Idempotent. */
class AutoComplete implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(BookingService $bookings): int
    {
        $done = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->whereDate('date', '<=', now()->toDateString())
            ->orderBy('id')
            ->each(function (Booking $booking) use ($bookings, &$done) {
                $done += $bookings->complete($booking) ? 1 : 0;
            });

        return $done;
    }
}
