<?php

namespace App\Modules\Verification\States;

use App\Modules\Verification\Enums\HostStatus;

final class RejectedState extends HostState
{
    public function status(): HostStatus
    {
        return HostStatus::Rejected;
    }

    /** A rejected host may apply again, which puts them back to pending. */
    public function next(): array
    {
        return [HostStatus::Pending];
    }
}
