<?php

namespace App\Modules\Booking\Console;

use App\Models\User;
use App\Modules\Booking\Exceptions\BookingRuleViolation;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use Illuminate\Console\Command;

/**
 * Accept a booking as its host from the command line. It runs the same
 * BookingService code as the web page; the concurrency and soak tests start
 * several of these processes at once to prove the lock holds.
 */
class AcceptBookingCommand extends Command
{
    protected $signature = 'booking:accept {booking : Booking id}';

    protected $description = 'Accept a booking request as its host (same code path as the web page)';

    public function handle(BookingService $bookings): int
    {
        $booking = Booking::findOrFail($this->argument('booking'));
        $host = User::findOrFail($booking->host_id);

        try {
            $bookings->accept($booking, $host);
        } catch (BookingRuleViolation $e) {
            $this->line('REFUSED:'.$e->rule);

            return self::FAILURE;
        } catch (PaymentFailedException $e) {
            $this->line('REFUSED:payment');

            return self::FAILURE;
        }

        $this->line('ACCEPTED');

        return self::SUCCESS;
    }
}
