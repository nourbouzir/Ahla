<?php

namespace App\Modules\Verification\States;

use App\Modules\Verification\Enums\HostStatus;

final class VerifiedState extends HostState
{
    public function status(): HostStatus
    {
        return HostStatus::Verified;
    }

    public function next(): array
    {
        return [HostStatus::Suspended];
    }

    public function isBookable(): bool
    {
        return true;
    }
}
