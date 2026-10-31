<?php

namespace App\Modules\Booking\States;

use App\Modules\Booking\Enums\BookingStatus;

final class ConfirmedState extends BookingState
{
    public function next(): array
    {
        return [BookingStatus::Cancelled, BookingStatus::Completed];
    }
}
