<?php

namespace App\Modules\Verification\States;

use App\Modules\Verification\Enums\HostStatus;

final class SuspendedState extends HostState
{
    public function status(): HostStatus
    {
        return HostStatus::Suspended;
    }

    /** Suspension is manual in v1 (ADR 0004): an admin reinstates or rejects. */
    public function next(): array
    {
        return [HostStatus::Verified, HostStatus::Rejected];
    }
}
