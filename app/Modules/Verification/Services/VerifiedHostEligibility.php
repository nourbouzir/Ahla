<?php

namespace App\Modules\Verification\Services;

use App\Contracts\Hosts\HostEligibility;
use App\Modules\Verification\Models\HostProfile;
use App\Modules\Verification\States\HostState;

/** Real HostEligibility: a host can be booked only when their status is verified. */
class VerifiedHostEligibility implements HostEligibility
{
    public function isBookable(int $hostId): bool
    {
        $profile = HostProfile::query()->where('user_id', $hostId)->first();

        return $profile !== null && HostState::for($profile->status)->isBookable();
    }
}
