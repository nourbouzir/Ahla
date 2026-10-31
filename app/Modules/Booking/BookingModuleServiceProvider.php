<?php

namespace App\Modules\Booking;

use App\Contracts\Bookings\ConfirmedBookingLookup;
use App\Modules\Booking\Console\AcceptBookingCommand;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Policies\BookingPolicy;
use App\Modules\Booking\Services\EloquentConfirmedBookingLookup;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class BookingModuleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConfirmedBookingLookup::class, EloquentConfirmedBookingLookup::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Booking::class, BookingPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([AcceptBookingCommand::class]);
        }
    }
}
