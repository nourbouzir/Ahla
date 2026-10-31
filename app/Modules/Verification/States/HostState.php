<?php

namespace App\Modules\Verification\States;

use App\Modules\Verification\Enums\HostStatus;

/**
 * State pattern for a host: each state knows which states may follow it and
 * whether the host can be booked. This is the only place that says so.
 */
abstract class HostState
{
    abstract public function status(): HostStatus;

    /** @return list<HostStatus> */
    abstract public function next(): array;

    /** Only a verified host can be booked or found in search (FR-07). */
    public function isBookable(): bool
    {
        return false;
    }

    public function canTransitionTo(HostStatus $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    public static function for(HostStatus $status): self
    {
        return match ($status) {
            HostStatus::Pending => new PendingState,
            HostStatus::Verified => new VerifiedState,
            HostStatus::Rejected => new RejectedState,
            HostStatus::Suspended => new SuspendedState,
        };
    }
}
