<?php

namespace App\Modules\Booking\Enums;

enum BookingStatus: string
{
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
