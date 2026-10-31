<?php

namespace App\Modules\Booking\States;

use App\Modules\Booking\Enums\BookingStatus;

final class RequestedState extends BookingState
{
    public function next(): array
    {
        return [BookingStatus::Confirmed, BookingStatus::Declined, BookingStatus::Expired];
    }
}
