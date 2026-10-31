<?php

namespace App\Modules\Booking;

use App\Contracts\Bookings\ConfirmedBookingLookup;
use App\Modules\Booking\Services\EloquentConfirmedBookingLookup;
use App\Modules\ModuleServiceProvider;

class BookingModuleServiceProvider extends ModuleServiceProvider
{
    // Routes, migrations, views and translations are loaded from this folder by
    // the base class. Module-only bindings go in register(); listeners and
    // policies go in boot() (call parent::boot() first).
    public function register(): void
    {
        $this->app->bind(ConfirmedBookingLookup::class, EloquentConfirmedBookingLookup::class);
    }
}
