<?php

namespace App\Contracts\Hosts;

interface AvailabilityChecker
{
    public function isSlotAvailable(
        int $hostId,
        string $date,
        string $start,
        string $end,
    ): bool;
}
