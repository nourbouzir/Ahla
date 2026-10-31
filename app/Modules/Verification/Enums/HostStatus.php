<?php

namespace App\Modules\Verification\Enums;

enum HostStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
}
