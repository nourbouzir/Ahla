<?php

namespace App\Modules\Booking\Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Experience\Models\Experience;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
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
            $this->history();
        } catch (Throwable $e) {
            $this->command?->warn('Demo bookings were not created: '.$e->getMessage());
        }
    }

    /**
     * About forty past bookings spread over the last six months, so that the dashboards (earnings,
     * clients, revenue, charts) have something to show. Written directly because BookingService
     * rightly refuses bookings in the past. Deterministic: the same data on every machine.
     */
    private function history(): void
    {
        mt_srand(7);
        $tourists = User::query()->where('role', Role::Tourist->value)->orderBy('id')->get();
        $experiences = Experience::query()->where('published', true)->with('hostProfile')->orderBy('id')->get();

        foreach (range(0, 39) as $i) {
            $experience = $experiences[$i % $experiences->count()];
            $tourist = $tourists[mt_rand(0, $tourists->count() - 1)];
            $date = now()->subDays(4 + $i * 4 + mt_rand(0, 2));
            $group = mt_rand(1, min(4, $experience->max_group_size));
            $price = bcmul((string) $experience->price_per_person, (string) $group, 3);
            $cancelled = mt_rand(1, 10) === 1;
            $start = ['09:00:00', '10:30:00', '14:00:00'][mt_rand(0, 2)];
            $end = date('H:i:s', strtotime($start) + $experience->duration_hours * 3600);

            $booking = new Booking([
                'tourist_id' => $tourist->id, 'host_id' => $experience->hostProfile->user_id, 'experience_id' => $experience->id,
                'date' => $date->toDateString(), 'start_time' => $start, 'end_time' => $end, 'group_size' => $group,
                'location' => $experience->meeting_point, 'displayed_price' => $price,
            ]);
            $booking->forceFill([
                'status' => $cancelled ? BookingStatus::Cancelled : BookingStatus::Completed,
                'cancelled_at' => $cancelled ? $date->copy()->subDays(3) : null,
                'cancelled_by' => $cancelled ? $booking->host_id : null,
                'cancellation_reason' => $cancelled ? 'Seeded cancellation' : null,
                'created_at' => $date->copy()->subDays(6), 'updated_at' => $date,
            ])->save();

            $payment = new Payment(['booking_id' => $booking->id, 'amount' => $price, 'currency' => 'TND']);
            $payment->forceFill([
                'gateway_ref' => 'seed-'.$booking->id,
                'status' => $cancelled ? PaymentStatus::Refunded : PaymentStatus::Captured,
                'refunded_amount' => $cancelled ? $price : '0.000',
                'authorised_at' => $date->copy()->subDays(6), 'captured_at' => $date->copy()->subDays(5),
                'refunded_at' => $cancelled ? $date->copy()->subDays(3) : null,
            ])->save();
        }
    }
}
