<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class PaymentStatusChanged
{
    use Dispatchable;

    public function __construct(
        public int $paymentId,
        public int $bookingId,
        public string $oldStatus,
        public string $newStatus,
    ) {}
}
