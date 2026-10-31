<?php

namespace App\Contracts\Fakes;

use App\Contracts\Payments\GatewayTimeoutException;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayResponse;
use App\Modules\Payment\Enums\PaymentStatus;
use InvalidArgumentException;

class FakePaymentGateway implements PaymentGateway
{
    protected FakeGatewayMode $mode = FakeGatewayMode::Success;

    /** @var array<string, PaymentGatewayResponse> */
    protected array $responses = [];

    /** @var array<string, PaymentStatus> */
    protected array $statuses = [];

    /** @var array<string, string> */
    protected array $amounts = [];

    /** @var array<string, string> */
    protected array $currencies = [];

    public function setMode(FakeGatewayMode $mode): void
    {
        $this->mode = $mode;
    }

    public function authorise(string $amount, string $currency, string $idempotencyKey): PaymentGatewayResponse
    {
        return $this->remember('authorise', $idempotencyKey, function () use ($amount, $currency, $idempotencyKey) {
            $this->throwIfTimedOut();
            $status = $this->mode === FakeGatewayMode::Decline
                ? PaymentStatus::Failed
                : PaymentStatus::Authorised;
            $reference = $status === PaymentStatus::Authorised
                ? 'fake-'.hash('sha256', $idempotencyKey)
                : null;

            if ($reference !== null) {
                $this->statuses[$reference] = $status;
                $this->amounts[$reference] = $amount;
                $this->currencies[$reference] = $currency;
            }

            return new PaymentGatewayResponse($status, $amount, $currency, $reference);
        });
    }

    public function capture(string $gatewayReference, string $idempotencyKey): PaymentGatewayResponse
    {
        return $this->remember('capture', $idempotencyKey, function () use ($gatewayReference) {
            $this->throwIfTimedOut();
            $this->assertStatus($gatewayReference, PaymentStatus::Authorised);
            $status = $this->mode === FakeGatewayMode::Decline
                ? PaymentStatus::Failed
                : PaymentStatus::Captured;
            $this->statuses[$gatewayReference] = $status;

            return new PaymentGatewayResponse(
                $status,
                $this->amounts[$gatewayReference],
                $this->currencies[$gatewayReference],
                $gatewayReference,
            );
        });
    }

    public function refund(
        string $gatewayReference,
        string $amount,
        string $idempotencyKey,
    ): PaymentGatewayResponse {
        return $this->remember('refund', $idempotencyKey, function () use ($gatewayReference, $amount) {
            $this->throwIfTimedOut();
            $this->assertStatus($gatewayReference, PaymentStatus::Captured);
            $status = $this->mode === FakeGatewayMode::Decline
                ? PaymentStatus::Failed
                : PaymentStatus::Refunded;
            $this->statuses[$gatewayReference] = $status;

            return new PaymentGatewayResponse(
                $status,
                $amount,
                $this->currencies[$gatewayReference],
                $gatewayReference,
            );
        });
    }

    public function release(string $gatewayReference, string $idempotencyKey): PaymentGatewayResponse
    {
        return $this->remember('release', $idempotencyKey, function () use ($gatewayReference) {
            $this->throwIfTimedOut();
            $this->assertStatus($gatewayReference, PaymentStatus::Authorised);
            $this->statuses[$gatewayReference] = PaymentStatus::Released;

            return new PaymentGatewayResponse(
                PaymentStatus::Released,
                $this->amounts[$gatewayReference],
                $this->currencies[$gatewayReference],
                $gatewayReference,
            );
        });
    }

    /** @return array<string, mixed> */
    public function exportState(): array
    {
        return ['mode' => $this->mode, 'responses' => $this->responses, 'statuses' => $this->statuses,
            'amounts' => $this->amounts, 'currencies' => $this->currencies];
    }

    /** @param array<string, mixed> $state */
    public function importState(array $state): void
    {
        $this->mode = $state['mode'];
        $this->responses = $state['responses'];
        $this->statuses = $state['statuses'];
        $this->amounts = $state['amounts'];
        $this->currencies = $state['currencies'];
    }

    public function status(string $gatewayReference): PaymentStatus
    {
        return $this->statuses[$gatewayReference]
            ?? throw new InvalidArgumentException('Unknown fake payment gateway reference.');
    }

    private function remember(string $operation, string $idempotencyKey, callable $callback): PaymentGatewayResponse
    {
        $key = $operation.':'.$idempotencyKey;

        return $this->responses[$key] ??= $callback();
    }

    private function throwIfTimedOut(): void
    {
        if ($this->mode === FakeGatewayMode::Timeout) {
            throw new GatewayTimeoutException('The fake payment gateway timed out.');
        }
    }

    private function assertStatus(string $reference, PaymentStatus $expected): void
    {
        if (($this->statuses[$reference] ?? null) !== $expected) {
            throw new InvalidArgumentException('The fake payment reference is not in the required state.');
        }
    }
}
