<?php

namespace App\Modules\Payment\Gateways;

use App\Contracts\Fakes\FakeGatewayMode;
use App\Contracts\Fakes\FakePaymentGateway;
use App\Contracts\Payments\GatewayTimeoutException;
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
            $fault = $this->rollFault();

            if ($fault === 'timeout') {
                throw new GatewayTimeoutException('Sandbox fault: the gateway timed out.');
            }

            $previousMode = $this->mode;
            $this->mode = $fault === 'decline' ? FakeGatewayMode::Decline : $previousMode;
            $result = $operation();
            $this->mode = $previousMode;
            Cache::forever(self::CACHE_KEY, $this->exportState());

            // The nastiest real-world case: the provider DID act but the answer never reached us.
            if ($fault === 'lost') {
                throw new GatewayTimeoutException('Sandbox fault: the answer was lost after the gateway acted.');
            }

            return $result;
        });
    }

    /**
     * Fault injection for chaos and soak tests (AHLA_SANDBOX_FAULT_RATE, 0 = off):
     * timeout (nothing happened), decline, or lost answer (it happened but we never heard).
     */
    private function rollFault(): ?string
    {
        $rate = (float) config('ahla.sandbox.fault_rate', 0);

        if ($rate <= 0 || mt_rand() / mt_getrandmax() >= $rate) {
            return null;
        }

        return ['timeout', 'decline', 'lost'][mt_rand(0, 2)];
    }

    private function load(): void
    {
        if ($state = Cache::get(self::CACHE_KEY)) {
            $this->importState($state);
        }
    }
}
