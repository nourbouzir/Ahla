<?php

namespace App\Contracts\Bookings;

use App\Models\User;

/**
 * Lets the Auth module ask "do these two people share a confirmed booking?"
 * without reading the bookings table itself. Contact details (phone, email)
 * are shown to the other party only when this is true (ADR 0004).
 */
interface ConfirmedBookingLookup
{
    public function haveConfirmedBooking(User $a, User $b): bool;

    /** True when the two users share a booking in any status (used to allow viewing a profile). */
    public function haveAnyBooking(User $a, User $b): bool;
}
