<?php

namespace App\Contracts\Payments;

use App\Modules\Payment\Enums\PaymentStatus;

interface PaymentGateway
{
    public function authorise(string $amount, string $currency, string $idempotencyKey): PaymentGatewayResponse;

    public function capture(string $gatewayReference, string $idempotencyKey): PaymentGatewayResponse;

    public function refund(
        string $gatewayReference,
        string $amount,
        string $idempotencyKey,
    ): PaymentGatewayResponse;

    /** Cancel an authorisation that was never captured (request declined or expired). */
    public function release(string $gatewayReference, string $idempotencyKey): PaymentGatewayResponse;

    public function status(string $gatewayReference): PaymentStatus;
}
