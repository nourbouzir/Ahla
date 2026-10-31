<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class BookingConfirmed
{
    use Dispatchable;

    public function __construct(
        public int $bookingId,
        public int $touristId,
        public int $hostId,
    ) {}
}
