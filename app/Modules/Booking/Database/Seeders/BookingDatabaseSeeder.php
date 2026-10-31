<?php

namespace App\Modules\Booking\Database\Seeders;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Experience\Models\Experience;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Two sample bookings for the demo (not seeded in production), created through
 * BookingService so payments, audit rows and notifications are real:
 *  - a CONFIRMED booking: Lea with Amel, three days from now
 *  - a REQUESTED booking: Sam asks Youssef; log in as youssef@ahla.test to accept it
 */
class BookingDatabaseSeeder extends Seeder
{
    public function run(BookingService $bookings): void
    {
        if (app()->isProduction() || Booking::query()->exists()) {
            return;
        }

        $lea = User::where('email', 'tourist@ahla.test')->first();
        $sam = User::where('email', 'sam@ahla.test')->first();
        $amel = Experience::where('title', 'The medina, behind the blue doors')->first();
        $youssef = Experience::where('title', 'Sidi Bou Said, after the crowds')->first();

        if (! $lea || ! $sam || ! $amel || ! $youssef) {
            return;
        }

        try {
            $confirmed = $bookings->request($lea, $amel, now()->addDays(3)->toDateString(), '10:00', 2);
            $bookings->accept($confirmed, User::findOrFail($confirmed->host_id));
            $bookings->request($sam, $youssef, now()->addDays(4)->toDateString(), '16:00', 3);
        } catch (Throwable $e) {
            $this->command?->warn('Demo bookings were not created: '.$e->getMessage());
        }
    }
}
