<?php

namespace App\Contracts\Fakes;

use App\Contracts\Hosts\AlternativeHostFinder;
use App\Modules\Booking\Models\Booking;
use Illuminate\Support\Collection;

class FakeAlternativeHostFinder implements AlternativeHostFinder
{
    /** @var Collection<int, int> */
    private Collection $hostUserIds;

    /** @param Collection<int, int>|null $hostUserIds */
    public function __construct(?Collection $hostUserIds = null)
    {
        $this->hostUserIds = $hostUserIds ?? collect();
    }

    /** @param Collection<int, int> $hostUserIds */
    public function setHostUserIds(Collection $hostUserIds): void
    {
        $this->hostUserIds = $hostUserIds;
    }

    public function find(Booking $booking): Collection
    {
        return $this->hostUserIds;
    }
}
