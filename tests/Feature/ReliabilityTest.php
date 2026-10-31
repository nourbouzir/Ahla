<?php

namespace Tests\Feature;

use App\Contracts\Fakes\FakePaymentGateway;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\GatewayTimeoutException;
use App\Contracts\Payments\PaymentGateway;
use App\Jobs\ReconcilePayments;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Gateways\SandboxPaymentGateway;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\RefundService;
use App\Modules\Verification\Services\VerifiedHostEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

class ReliabilityTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    private User $host;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->singleton(HostEligibility::class, VerifiedHostEligibility::class);
        $this->app->singleton(AvailabilityChecker::class, WeeklyAvailabilityChecker::class);
        $this->host = $this->makeHost('Reliable Host');
    }

    private function reconcile(): array
    {
        Booking::query()->with('payment')->get()->each(fn ($b) => $b->payment?->forceFill(['updated_at' => now()->subMinutes(5)])->saveQuietly());

        return (new ReconcilePayments)->handle(app(RefundService::class), app(PaymentService::class), app(BookingService::class));
    }

    private function requested(?User $tourist = null, string $start = '10:00'): Booking
    {
        return app(BookingService::class)->request(
            $tourist ?? User::factory()->tourist()->create(),
            $this->host->hostProfile->experiences()->first(),
            now()->addDays(3)->toDateString(),
            $start,
            2,
        );
    }

    public function test_a_lost_capture_answer_is_recovered_and_the_booking_confirmed(): void
    {
        $booking = $this->requested();
        /** @var FakePaymentGateway $gateway */
        $gateway = app(PaymentGateway::class);
        // The gateway captures, but our code never hears: the DB still says authorised.
        $gateway->capture($booking->payment->gateway_ref, 'lost-answer');
        $this->assertSame(PaymentStatus::Authorised, $booking->payment->fresh()->status);

        $this->assertSame(1, $this->reconcile()['recovered']);

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertSame(PaymentStatus::Captured, $booking->payment->fresh()->status);
        $this->assertSame(0, $this->reconcile()['recovered'], 'second run changes nothing');
    }

    public function test_a_recovered_capture_for_a_slot_that_is_gone_is_refunded_and_declined(): void
    {
        $first = $this->requested(start: '10:00');
        $second = $this->requested(start: '11:00');          // overlaps
        app(BookingService::class)->accept($first, $this->host);
        app(PaymentGateway::class)->capture($second->payment->gateway_ref, 'lost-answer-2');

        $this->reconcile();

        $this->assertSame(BookingStatus::Declined, $second->fresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $second->payment->fresh()->status);
        $this->assertSame('100.000', (string) $second->payment->fresh()->refunded_amount);
        $this->assertSame(BookingStatus::Confirmed, $first->fresh()->status, 'the confirmed booking is untouched');
    }

    public function test_a_request_whose_payment_is_still_only_reserved_is_left_alone(): void
    {
        $booking = $this->requested();

        $this->assertSame(0, $this->reconcile()['recovered']);
        $this->assertSame(BookingStatus::Requested, $booking->fresh()->status);
    }

    public function test_the_sandbox_gateway_can_inject_each_kind_of_fault(): void
    {
        Cache::flush();
        config(['ahla.sandbox.fault_rate' => 1.0]);
        $outcomes = [];

        foreach (range(1, 60) as $i) {
            try {
                $r = (new SandboxPaymentGateway)->authorise('10.000', 'TND', "k$i");
                $outcomes[$r->status->value] = true;
            } catch (GatewayTimeoutException $e) {
                $outcomes[str_contains($e->getMessage(), 'lost') ? 'lost' : 'timeout'] = true;
            }
        }

        $this->assertEqualsCanonicalizing(['failed', 'lost', 'timeout'], array_keys($outcomes), 'decline, lost answer and timeout all occur');
        config(['ahla.sandbox.fault_rate' => 0]);
        $this->assertSame(PaymentStatus::Authorised, (new SandboxPaymentGateway)->authorise('10.000', 'TND', 'fine')->status);
    }
}
