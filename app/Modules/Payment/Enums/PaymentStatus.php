<?php

namespace App\Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Authorised = 'authorised';
    case Captured = 'captured';
    case Released = 'released';
    case Refunded = 'refunded';
    case Failed = 'failed';
}
