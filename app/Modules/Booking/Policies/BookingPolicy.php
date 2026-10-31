<?php

namespace App\Modules\Booking\Policies;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Booking\Models\Booking;

class BookingPolicy
{
    /** Only the booking's own tourist and host may read or write its conversation (not even admins). */
    public function chat(User $user, Booking $booking): bool
    {
        return in_array($user->id, [$booking->tourist_id, $booking->host_id], true);
    }

    /** The tourist and the host of the booking, and admins (read only). */
    public function view(User $user, Booking $booking): bool
    {
        return $user->role === Role::Admin || in_array($user->id, [$booking->tourist_id, $booking->host_id], true);
    }
}
