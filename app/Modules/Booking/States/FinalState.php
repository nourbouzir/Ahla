<?php

namespace App\Modules\Booking\States;

/** Declined, expired, cancelled and completed bookings never change again. */
final class FinalState extends BookingState
{
    public function next(): array
    {
        return [];
    }
}
