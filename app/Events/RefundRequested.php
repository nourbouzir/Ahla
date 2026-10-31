<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class RefundRequested
{
    use Dispatchable;

    public function __construct(
        public int $paymentId,
        public int $bookingId,
        public string $amount,
        public ?string $reason,
    ) {}
}
