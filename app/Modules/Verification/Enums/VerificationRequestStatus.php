<?php

namespace App\Modules\Verification\Enums;

enum VerificationRequestStatus: string
{
    case Submitted = 'submitted';
    case Scheduled = 'scheduled';
    case Decided = 'decided';
}
