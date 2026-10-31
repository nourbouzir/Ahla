<?php

namespace App\Modules\Booking\Console;

use App\Models\User;
use App\Modules\Booking\Exceptions\BookingRuleViolation;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Experience\Models\Experience;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use Illuminate\Console\Command;

/** Request a booking as a tourist from the command line (same code path as the web form); used by the soak test. */
class RequestBookingCommand extends Command
{
    protected $signature = 'booking:request {tourist : User id} {experience : Experience id} {date : YYYY-MM-DD} {start : HH:MM} {group=1 : People}';

    protected $description = 'Request a booking as a tourist (same code path as the web form)';

    public function handle(BookingService $bookings): int
    {
        try {
            $booking = $bookings->request(
                User::findOrFail($this->argument('tourist')),
                Experience::findOrFail($this->argument('experience')),
                $this->argument('date'),
                $this->argument('start'),
                (int) $this->argument('group'),
            );
        } catch (BookingRuleViolation $e) {
            $this->line('REFUSED:'.$e->rule);

            return self::FAILURE;
        } catch (PaymentFailedException) {
            $this->line('REFUSED:payment');

            return self::FAILURE;
        }

        $this->line('REQUESTED:'.$booking->id);

        return self::SUCCESS;
    }
}
