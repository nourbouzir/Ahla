<?php

namespace App\Contracts\Payments;

use App\Modules\Payment\Enums\PaymentStatus;

final readonly class PaymentGatewayResponse
{
    public function __construct(
        public PaymentStatus $status,
        public string $amount,
        public string $currency,
        public ?string $gatewayReference,
    ) {}
}
