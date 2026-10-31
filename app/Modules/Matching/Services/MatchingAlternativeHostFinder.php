<?php

namespace App\Modules\Matching\Services;

use App\Contracts\Bookings\HostSchedule;
use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Modules\Booking\Models\Booking;
use App\Modules\Matching\Support\SearchCriteria;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

/**
 * Real AlternativeHostFinder (FR-17): verified hosts in the SAME city as the
 * cancelled booking's host, free at the same date and time (weekly hours and
 * days off through AvailabilityChecker, other bookings through HostSchedule),
 * never the host who cancelled, best match first, at most N (config, default 3).
 * The tourist's profile languages and interests decide the order.
 */
class MatchingAlternativeHostFinder implements AlternativeHostFinder
{
    public function __construct(
        private readonly MatchingService $matching,
        private readonly AvailabilityChecker $availability,
        private readonly Container $container,
    ) {}

    /** @return Collection<int, int> host user ids, best first */
    public function find(Booking $booking): Collection
    {
        $booking->loadMissing(['host.hostProfile', 'tourist']);
        $city = $booking->host->hostProfile?->city;

        if ($city === null) {
            return collect();
        }

        // Resolved here, not in the constructor: BookingService (the HostSchedule) itself needs this finder.
        $schedule = $this->container->make(HostSchedule::class);
        $date = $booking->date->toDateString();
        $criteria = SearchCriteria::fromInput(['city' => $city], $booking->tourist);

        return $this->matching->rank($criteria)
            ->filter(fn ($match) => $match->host->user_id !== $booking->host_id)
            ->filter(fn ($match) => $this->availability->isSlotAvailable($match->host->user_id, $date, $booking->start_time, $booking->end_time)
                && $schedule->isFree($match->host->user_id, $date, $booking->start_time, $booking->end_time))
            ->take((int) config('ahla.alternatives_max'))
            ->map(fn ($match) => $match->host->user_id)
            ->values();
    }
}
