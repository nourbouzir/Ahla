<?php

namespace App\Modules\Messaging\Database\Seeders;

use App\Modules\Booking\Models\Booking;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Database\Seeder;

/** A short French / English conversation on the confirmed demo booking (not seeded in production). */
class MessagingDatabaseSeeder extends Seeder
{
    public function run(MessagingService $messaging): void
    {
        if (app()->isProduction() || Message::query()->exists()) {
            return;
        }

        $booking = Booking::query()->where('status', 'confirmed')->with(['tourist', 'host'])->first();

        if ($booking === null) {
            return;
        }

        $messaging->send($booking, $booking->tourist, 'Bonjour Amel ! Y a-t-il un endroit pour s’asseoir pendant la visite ?');
        $messaging->send($booking, $booking->host, 'Hello Lea! Yes, we stop for mint tea halfway. See you at Bab Bhar.');
    }
}
