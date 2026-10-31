<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class BookingCancelled
{
    use Dispatchable;

    public function __construct(
        public int $bookingId,
        public int $cancelledBy,
        public ?string $reason,
    ) {}
}
