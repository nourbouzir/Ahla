<?php

namespace App\Modules\Verification\Services;

use App\Events\HostStatusChanged;
use App\Models\User;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Exceptions\InvalidHostTransition;
use App\Modules\Verification\Models\HostProfile;
use App\Modules\Verification\States\HostState;
use Illuminate\Support\Facades\DB;

/** The only code that changes host_profiles.status. Always audited. */
class HostStatusService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function transition(HostProfile $profile, HostStatus $to, ?User $actor, ?string $reason = null): HostProfile
    {
        // Rejecting or suspending without saying why would leave the host guessing.
        if (in_array($to, [HostStatus::Rejected, HostStatus::Suspended], true) && blank($reason)) {
            throw new InvalidHostTransition('A reason is required to reject or suspend a host.');
        }

        return DB::transaction(function () use ($profile, $to, $actor, $reason) {
            // Lock the row so two admins deciding at once cannot both win.
            $locked = HostProfile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! HostState::for($from)->canTransitionTo($to)) {
                throw new InvalidHostTransition("A {$from->value} host cannot become {$to->value}.");
            }

            $locked->forceFill(['status' => $to])->save();
            $this->audit->record('host', $locked->id, $from, $to, $actor, $reason);
            HostStatusChanged::dispatch($locked->id, $from->value, $to->value, $actor?->id);

            return $locked;
        });
    }
}
