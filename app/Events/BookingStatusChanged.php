<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class BookingStatusChanged
{
    use Dispatchable;

    public function __construct(
        public int $bookingId,
        public string $oldStatus,
        public string $newStatus,
        public ?int $actorId,
    ) {}
}
