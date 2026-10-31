<?php

namespace App\Modules\Verification\States;

use App\Modules\Verification\Enums\HostStatus;

final class PendingState extends HostState
{
    public function status(): HostStatus
    {
        return HostStatus::Pending;
    }

    public function next(): array
    {
        return [HostStatus::Verified, HostStatus::Rejected];
    }
}
