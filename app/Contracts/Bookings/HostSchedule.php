<?php

namespace App\Contracts\Bookings;

/**
 * Lets other modules ask "could this host take a new confirmed booking in this
 * slot?" without knowing the overlap and daily-limit rules. The answer comes
 * from BookingService, the only place those rules live.
 */
interface HostSchedule
{
    public function isFree(int $hostId, string $date, string $start, string $end): bool;
}
