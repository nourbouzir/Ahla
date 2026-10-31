<?php

namespace Tests\Feature;

use App\Contracts\Fakes\FakeGatewayMode;
use App\Contracts\Fakes\FakePaymentGateway;
use App\Contracts\Payments\PaymentGateway;
use App\Events\PaymentStatusChanged;
use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use App\Modules\Payment\Gateways\SandboxPaymentGateway;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PaymentEngineTest extends TestCase
{
    use RefreshDatabase;

    private function booking(string $price = '120.000'): Booking
    {
        return Booking::factory()->create(['displayed_price' => $price]);
    }

    private function gateway(): FakePaymentGateway
    {
        return app(PaymentGateway::class);
    }

    public function test_authorise_then_capture_writes_status_and_audit(): void
    {
        Event::fake([PaymentStatusChanged::class]);
        $booking = $this->booking();
        $service = app(PaymentService::class);

        $payment = $service->authorise($booking);
        $this->assertSame(PaymentStatus::Authorised, $payment->status);
        $this->assertSame('120.000', (string) $payment->amount);
        $this->assertNotNull($payment->gateway_ref);

        $payment = $service->capture($payment);
        $this->assertSame(PaymentStatus::Captured, $payment->status);
        $this->assertNotNull($payment->captured_at);

        $this->assertSame(
            ['authorised', 'captured'],
            AuditLog::where('entity', 'payment')->orderBy('id')->pluck('new_status')->all(),
        );
        Event::assertDispatched(PaymentStatusChanged::class, 2);
    }

    public function test_capture_is_idempotent(): void
    {
        $service = app(PaymentService::class);
        $payment = $service->capture($service->authorise($this->booking()));

        $service->capture($payment);

        $this->assertSame(2, AuditLog::where('entity', 'payment')->count());
    }

    public function test_declined_authorisation_throws_and_stores_nothing(): void
    {
        $this->gateway()->setMode(FakeGatewayMode::Decline);

        $this->expectException(PaymentFailedException::class);
        try {
            app(PaymentService::class)->authorise($this->booking());
        } finally {
            $this->assertDatabaseCount('payments', 0);
        }
    }

    public function test_timeout_is_never_success(): void
    {
        $service = app(PaymentService::class);
        $payment = $service->authorise($this->booking());

        $this->gateway()->setMode(FakeGatewayMode::Timeout);

        $this->expectException(PaymentFailedException::class);
        try {
            $service->capture($payment);
        } finally {
            $this->assertSame(PaymentStatus::Authorised, $payment->fresh()->status);
        }
    }

    public function test_release_after_authorisation(): void
    {
        $service = app(PaymentService::class);
        $payment = $service->release($service->authorise($this->booking()));

        $this->assertSame(PaymentStatus::Released, $payment->status);
        $this->assertNotNull($payment->released_at);
    }

    public function test_cannot_refund_before_capture_or_more_than_paid(): void
    {
        $service = app(PaymentService::class);
        $payment = $service->authorise($this->booking());

        try {
            $service->refund($payment, '10.000');
            $this->fail('refund before capture should fail');
        } catch (PaymentFailedException) {
            $this->assertTrue(true);
        }

        $payment = $service->capture($payment);
        $this->expectException(PaymentFailedException::class);
        $service->refund($payment, '120.001');
    }

    public function test_host_cancellation_refunds_everything_whatever_the_timing(): void
    {
        $booking = $this->booking();
        $booking->forceFill(['date' => now()->addHours(2)->toDateString(), 'start_time' => now()->addHours(3)->format('H:i:s')])->save();
        $service = app(PaymentService::class);
        $service->capture($service->authorise($booking));

        $refunded = app(RefundService::class)->refundCancelledBooking($booking->fresh(['payment']), $booking->host);

        $this->assertSame('120.000', $refunded);
        $this->assertSame(PaymentStatus::Refunded, $booking->payment()->first()->status);
        $this->assertSame('120.000', (string) $booking->payment()->first()->refunded_amount);
    }

    public function test_tourist_refund_tiers_follow_the_policy(): void
    {
        $now = now()->setTime(8, 0);
        $cases = [
            'exactly 48 h' => [$now->copy()->addHours(48), '100.000'],
            'just under 48 h' => [$now->copy()->addHours(47)->addMinutes(59), '50.000'],
            '24 h' => [$now->copy()->addHours(24), '50.000'],
            'just under 24 h' => [$now->copy()->addHours(23)->addMinutes(59), '0.000'],
            'already started' => [$now->copy()->subHour(), '0.000'],
        ];

        foreach ($cases as $label => [$start, $expected]) {
            $booking = $this->booking('100.000');
            $booking->forceFill(['date' => $start->toDateString(), 'start_time' => $start->format('H:i:s')])->save();
            $booking = $booking->fresh();

            $amount = app(RefundService::class)->policyFor($booking, $booking->tourist)
                ->refundAmount($booking, '100.000', $now);

            $this->assertSame($expected, $amount, $label);
        }
    }

    public function test_partial_refund_keeps_three_decimals_without_float_drift(): void
    {
        $booking = $this->booking('33.333');
        $booking->forceFill(['date' => now()->addHours(30)->toDateString(), 'start_time' => now()->addHours(30)->format('H:i:s')])->save();
        $booking = $booking->fresh();

        $amount = app(RefundService::class)->policyFor($booking, $booking->tourist)
            ->refundAmount($booking, '33.333', now());

        $this->assertSame('16.666', $amount); // 50% rounded down, never above what was owed
    }

    public function test_late_tourist_cancellation_refunds_nothing_and_keeps_payment_captured(): void
    {
        $booking = $this->booking();
        $booking->forceFill(['date' => now()->addHours(5)->toDateString(), 'start_time' => now()->addHours(5)->format('H:i:s')])->save();
        $service = app(PaymentService::class);
        $service->capture($service->authorise($booking));

        $refunded = app(RefundService::class)->refundCancelledBooking($booking->fresh(['payment']), $booking->tourist);

        $this->assertSame('0.000', $refunded);
        $this->assertSame(PaymentStatus::Captured, $booking->payment()->first()->status);
    }

    public function test_webhook_requires_a_valid_signature(): void
    {
        config(['ahla.payment.webhook_secret' => 'topsecret']);
        $service = app(PaymentService::class);
        $payment = $service->authorise($this->booking());
        $body = json_encode(['reference' => $payment->gateway_ref, 'status' => 'captured']);
        $sign = fn (string $b) => hash_hmac('sha256', $b, 'topsecret');
        $post = fn (string $signature, ?string $payload = null) => $this->call('POST', '/api/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_AHLA_SIGNATURE' => $signature,
        ], $payload ?? $body);

        $post('forged')->assertUnauthorized();
        $this->assertSame(PaymentStatus::Authorised, $payment->fresh()->status);

        $post($sign($body))->assertOk()->assertJson(['status' => 'captured']);
        $this->assertSame(PaymentStatus::Captured, $payment->fresh()->status);

        // Replaying the same event changes nothing and adds no audit row.
        $before = AuditLog::count();
        $post($sign($body))->assertOk();
        $this->assertSame($before, AuditLog::count());

        // A backwards move is refused.
        $back = json_encode(['reference' => $payment->gateway_ref, 'status' => 'authorised']);
        $post($sign($back), $back)->assertStatus(409);
    }

    public function test_webhook_is_disabled_without_a_secret(): void
    {
        config(['ahla.payment.webhook_secret' => '']);

        $this->postJson('/api/payments/webhook', ['reference' => 'x', 'status' => 'captured'])->assertStatus(503);
    }

    public function test_sandbox_gateway_remembers_state_between_instances(): void
    {
        Cache::flush();
        $first = new SandboxPaymentGateway;
        $reference = $first->authorise('10.000', 'TND', 'k1')->gatewayReference;

        $second = new SandboxPaymentGateway; // a "later request"
        $this->assertSame(PaymentStatus::Captured, $second->capture($reference, 'k2')->status);
        $this->assertSame(PaymentStatus::Captured, (new SandboxPaymentGateway)->status($reference));
    }

    public function test_audit_logger_stores_actor_reason_and_enum_values(): void
    {
        $actor = User::factory()->admin()->create();

        $row = app(AuditLogger::class)->record('host', 5, 'pending', PaymentStatus::Captured, $actor, 'because');

        $this->assertSame('captured', $row->new_status);
        $this->assertSame($actor->id, $row->actor_id);
        $this->assertSame('because', $row->reason);
        $this->assertNotNull($row->created_at);
    }
}
