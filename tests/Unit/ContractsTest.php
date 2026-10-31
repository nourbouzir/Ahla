<?php

namespace Tests\Unit;

use App\Contracts\Fakes\FakeAlternativeHostFinder;
use App\Contracts\Fakes\FakeAvailabilityChecker;
use App\Contracts\Fakes\FakeGatewayMode;
use App\Contracts\Fakes\FakeHostEligibility;
use App\Contracts\Fakes\FakePaymentGateway;
use App\Contracts\Fakes\NullTranslator;
use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\GatewayTimeoutException;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Translation\Translator;
use App\Events\BookingStatusChanged;
use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Enums\PaymentStatus;
use InvalidArgumentException;
use Tests\TestCase;

class ContractsTest extends TestCase
{
    public function test_payment_gateway_fake_supports_idempotent_authorise_capture_and_refund(): void
    {
        $gateway = app(PaymentGateway::class);
        $this->assertInstanceOf(FakePaymentGateway::class, $gateway);

        $authorisation = $gateway->authorise('125.500', 'TND', 'booking-1');
        $repeatedAuthorisation = $gateway->authorise('125.500', 'TND', 'booking-1');

        $this->assertSame(PaymentStatus::Authorised, $authorisation->status);
        $this->assertSame($authorisation->gatewayReference, $repeatedAuthorisation->gatewayReference);
        $this->assertSame('125.500', $authorisation->amount);

        $capture = $gateway->capture($authorisation->gatewayReference, 'capture-1');
        $this->assertSame(PaymentStatus::Captured, $capture->status);
        $this->assertSame('125.500', $capture->amount);

        $refund = $gateway->refund($authorisation->gatewayReference, '62.750', 'refund-1');
        $this->assertSame(PaymentStatus::Refunded, $refund->status);
        $this->assertSame('62.750', $refund->amount);
        $this->assertSame(PaymentStatus::Refunded, $gateway->status($authorisation->gatewayReference));
    }

    public function test_payment_gateway_fake_exposes_declines_timeouts_and_invalid_references(): void
    {
        $gateway = app(PaymentGateway::class);
        $this->assertInstanceOf(FakePaymentGateway::class, $gateway);

        $gateway->setMode(FakeGatewayMode::Decline);
        $this->assertSame(
            PaymentStatus::Failed,
            $gateway->authorise('20.000', 'TND', 'decline-1')->status,
        );

        $gateway->setMode(FakeGatewayMode::Timeout);
        $this->expectException(GatewayTimeoutException::class);
        $gateway->authorise('20.000', 'TND', 'timeout-1');
    }

    public function test_contract_bindings_and_scalar_domain_events_are_available(): void
    {
        $translator = app(Translator::class);
        $this->assertInstanceOf(NullTranslator::class, $translator);
        $this->assertSame('مرحبا', $translator->translate('مرحبا', 'ar', 'en'));
        $translator->setTranslations(['ar|en|مرحبا' => 'Hello']);
        $this->assertSame('Hello', $translator->translate('مرحبا', 'ar', 'en'));

        $event = new BookingStatusChanged(1, 'requested', 'confirmed', 4);
        $this->assertSame(1, $event->bookingId);
        $this->assertSame('confirmed', $event->newStatus);
        $this->assertSame([
            'bookingId' => 1,
            'oldStatus' => 'requested',
            'newStatus' => 'confirmed',
            'actorId' => 4,
        ], get_object_vars($event));
    }

    public function test_payment_gateway_fake_rejects_unknown_references(): void
    {
        $gateway = app(PaymentGateway::class);
        $this->assertInstanceOf(FakePaymentGateway::class, $gateway);

        $this->expectException(InvalidArgumentException::class);
        $gateway->status('missing');
    }

    public function test_host_and_alternative_fakes_can_be_configured(): void
    {
        $eligibility = app(HostEligibility::class);
        $availability = app(AvailabilityChecker::class);
        $alternatives = app(AlternativeHostFinder::class);

        $this->assertInstanceOf(FakeHostEligibility::class, $eligibility);
        $this->assertInstanceOf(FakeAvailabilityChecker::class, $availability);
        $this->assertInstanceOf(FakeAlternativeHostFinder::class, $alternatives);
        $this->assertFalse($eligibility->isBookable(1));
        $this->assertFalse($availability->isSlotAvailable(1, '2026-11-01', '09:00', '10:00'));

        $eligibility->setEligible(true);
        $availability->setAvailable(true);
        $alternatives->setHostUserIds(collect([7, 8]));

        $this->assertTrue($eligibility->isBookable(1));
        $this->assertTrue($availability->isSlotAvailable(1, '2026-11-01', '09:00', '10:00'));
        $this->assertSame([7, 8], $alternatives->find(new Booking)->all());
    }
}
