<?php

namespace App\Modules\Booking\Policies;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Booking\Models\Booking;

class BookingPolicy
{
    /** The tourist and the host of the booking, and admins (read only). */
    public function view(User $user, Booking $booking): bool
    {
        return $user->role === Role::Admin || in_array($user->id, [$booking->tourist_id, $booking->host_id], true);
    }
}
