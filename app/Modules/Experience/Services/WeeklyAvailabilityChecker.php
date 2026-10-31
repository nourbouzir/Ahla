<?php

namespace App\Modules\Experience\Services;

use App\Contracts\Hosts\AvailabilityChecker;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\BlockedDate;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Support\Carbon;

/**
 * Real AvailabilityChecker: is the host free in their published weekly hours
 * and not on a blocked date? Overlaps with other bookings and the 2-per-day
 * limit are NOT checked here - BookingService owns them (one place per rule).
 */
class WeeklyAvailabilityChecker implements AvailabilityChecker
{
    public function isSlotAvailable(int $hostId, string $date, string $start, string $end): bool
    {
        $profileId = HostProfile::query()->where('user_id', $hostId)->value('id');

        if ($profileId === null) {
            return false;
        }

        $day = Carbon::parse($date);
        $start = $this->normalise($start);
        $end = $this->normalise($end);

        if ($start >= $end) {
            return false;
        }

        if (BlockedDate::query()->where('host_profile_id', $profileId)->whereDate('date', $day->toDateString())->exists()) {
            return false;
        }

        return Availability::query()
            ->where('host_profile_id', $profileId)
            ->where('day_of_week', $day->dayOfWeek)
            ->where('starts_at', '<=', $start)
            ->where('ends_at', '>=', $end)
            ->exists();
    }

    /** "9:00", "09:00" and "09:00:00" all become "09:00:00" so strings compare correctly. */
    private function normalise(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }
}
