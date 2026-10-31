<?php

namespace App\Modules\Payment\Gateways;

use App\Contracts\Fakes\FakePaymentGateway;
use App\Contracts\Payments\PaymentGatewayResponse;
use App\Modules\Payment\Enums\PaymentStatus;
use Illuminate\Support\Facades\Cache;

/**
 * The v1 gateway (ADR 0005). It behaves like a real authorise / capture /
 * release / refund provider but moves no money. Unlike the in-memory fake it
 * remembers its state in the cache, so a payment authorised in one web request
 * can be captured in the next one. A real provider adapter replaces this class
 * through config('ahla.bindings'); nothing else changes.
 */
class SandboxPaymentGateway extends FakePaymentGateway
{
    private const CACHE_KEY = 'ahla.sandbox-gateway';

    public function authorise(string $amount, string $currency, string $idempotencyKey): PaymentGatewayResponse
    {
        return $this->persisted(fn () => parent::authorise($amount, $currency, $idempotencyKey));
    }

    public function capture(string $gatewayReference, string $idempotencyKey): PaymentGatewayResponse
    {
        return $this->persisted(fn () => parent::capture($gatewayReference, $idempotencyKey));
    }

    public function refund(string $gatewayReference, string $amount, string $idempotencyKey): PaymentGatewayResponse
    {
        return $this->persisted(fn () => parent::refund($gatewayReference, $amount, $idempotencyKey));
    }

    public function release(string $gatewayReference, string $idempotencyKey): PaymentGatewayResponse
    {
        return $this->persisted(fn () => parent::release($gatewayReference, $idempotencyKey));
    }

    public function status(string $gatewayReference): PaymentStatus
    {
        $this->load();

        return parent::status($gatewayReference);
    }

    /** Load, run, save - under a lock so two requests cannot overwrite each other. */
    private function persisted(callable $operation): PaymentGatewayResponse
    {
        return Cache::lock(self::CACHE_KEY.'.lock', 10)->block(5, function () use ($operation) {
            $this->load();
            $result = $operation();
            Cache::forever(self::CACHE_KEY, $this->exportState());

            return $result;
        });
    }

    private function load(): void
    {
        if ($state = Cache::get(self::CACHE_KEY)) {
            $this->importState($state);
        }
    }
}
