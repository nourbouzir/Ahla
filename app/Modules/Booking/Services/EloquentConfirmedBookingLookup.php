<?php

namespace App\Modules\Booking\Services;

use App\Contracts\Bookings\ConfirmedBookingLookup;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;

class EloquentConfirmedBookingLookup implements ConfirmedBookingLookup
{
    public function haveConfirmedBooking(User $a, User $b): bool
    {
        return $this->between($a, $b)->where('status', BookingStatus::Confirmed)->exists();
    }

    public function haveAnyBooking(User $a, User $b): bool
    {
        return $this->between($a, $b)->exists();
    }

    private function between(User $a, User $b)
    {
        return Booking::query()->where(function ($q) use ($a, $b) {
            $q->where(fn ($w) => $w->where('tourist_id', $a->id)->where('host_id', $b->id))
                ->orWhere(fn ($w) => $w->where('tourist_id', $b->id)->where('host_id', $a->id));
        });
    }
}
