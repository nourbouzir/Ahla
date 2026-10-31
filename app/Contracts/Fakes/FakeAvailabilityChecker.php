<?php

namespace App\Contracts\Fakes;

use App\Contracts\Hosts\AvailabilityChecker;

class FakeAvailabilityChecker implements AvailabilityChecker
{
    public function __construct(private bool $available = false) {}

    public function setAvailable(bool $available): void
    {
        $this->available = $available;
    }

    public function isSlotAvailable(
        int $hostId,
        string $date,
        string $start,
        string $end,
    ): bool {
        return $this->available;
    }
}
