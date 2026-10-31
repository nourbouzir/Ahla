<?php

namespace Tests\Feature;

use App\Contracts\Fakes\FakeAlternativeHostFinder;
use App\Contracts\Fakes\FakeGatewayMode;
use App\Contracts\Fakes\FakePaymentGateway;
use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\PaymentGateway;
use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\BookingRuleViolation;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Booking\States\BookingState;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\Experience;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Verification\Services\VerifiedHostEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    private User $tourist;

    private Experience $experience;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Use the REAL eligibility and availability rules, not the fakes.
        $this->app->singleton(HostEligibility::class, VerifiedHostEligibility::class);
        $this->app->singleton(AvailabilityChecker::class, WeeklyAvailabilityChecker::class);

        $this->host = User::factory()->verifiedHost()->create();
        $this->tourist = User::factory()->tourist()->create();
        foreach (range(0, 6) as $day) {
            Availability::factory()->create(['host_profile_id' => $this->host->hostProfile->id, 'day_of_week' => $day, 'starts_at' => '08:00:00', 'ends_at' => '22:00:00']);
        }
        $this->experience = Experience::factory()->published()->create([
            'host_profile_id' => $this->host->hostProfile->id, 'duration_hours' => 2, 'price_per_person' => '50.000', 'max_group_size' => 4,
        ]);
        $this->date = now()->addDays(5)->toDateString();
    }

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    private function request(string $start = '10:00', int $group = 2, ?string $date = null, ?User $tourist = null): Booking
    {
        return $this->service()->request($tourist ?? $this->tourist, $this->experience, $date ?? $this->date, $start, $group);
    }

    /** A booking that is already confirmed, made directly so rule tests stay short. */
    private function confirmed(string $start, string $end, ?string $date = null): Booking
    {
        return Booking::factory()->confirmed()->create([
            'experience_id' => $this->experience->id, 'host_id' => $this->host->id, 'date' => $date ?? $this->date,
            'start_time' => $start, 'end_time' => $end,
        ]);
    }

    private function violation(callable $call): string
    {
        try {
            $call();
        } catch (BookingRuleViolation $e) {
            return $e->rule;
        }

        return 'none';
    }

    public function test_request_creates_requested_booking_with_authorised_payment(): void
    {
        $booking = $this->request('10:00', 2);

        $this->assertSame(BookingStatus::Requested, $booking->status);
        $this->assertSame('100.000', (string) $booking->displayed_price);
        $this->assertSame('12:00:00', $booking->end_time);
        $this->assertEqualsWithDelta(12 * 3600, now()->diffInSeconds($booking->expires_at, false), 5);
        $this->assertSame(PaymentStatus::Authorised, $booking->payment->status);
        $this->assertSame('100.000', (string) $booking->payment->amount);
        $this->assertSame('booking', AuditLog::where('new_status', 'requested')->value('entity'));
    }

    public function test_request_rules(): void
    {
        $this->assertSame('in_the_past', $this->violation(fn () => $this->request('10:00', 2, now()->subDay()->toDateString())));
        $this->assertSame('group_size', $this->violation(fn () => $this->request('10:00', 5)));
        $this->assertSame('group_size', $this->violation(fn () => $this->request('10:00', 0)));
        $this->assertSame('crosses_midnight', $this->violation(fn () => $this->request('23:00')));
        $this->assertSame('outside_availability', $this->violation(fn () => $this->request('21:00')));
        $this->assertSame('outside_availability', $this->violation(fn () => $this->request('06:00')));
        $this->assertSame('host_not_bookable', $this->violation(fn () => $this->service()->request($this->host, $this->experience, $this->date, '10:00', 1)));

        $this->experience->update(['published' => false]);
        $this->assertSame('experience_unavailable', $this->violation(fn () => $this->request()));
        $this->experience->update(['published' => true]);

        $this->host->hostProfile->forceFill(['status' => 'suspended'])->save();
        $this->assertSame('host_not_bookable', $this->violation(fn () => $this->request()));

        $this->assertSame(0, Booking::count());
    }

    public function test_declined_payment_rolls_the_whole_booking_back(): void
    {
        app(PaymentGateway::class)->setMode(FakeGatewayMode::Decline);

        $this->expectException(PaymentFailedException::class);
        try {
            $this->request();
        } finally {
            $this->assertSame(0, Booking::count());
            $this->assertDatabaseCount('payments', 0);
        }
    }

    public function test_accept_captures_payment_then_confirms(): void
    {
        $booking = $this->request();

        $confirmed = $this->service()->accept($booking, $this->host);

        $this->assertSame(BookingStatus::Confirmed, $confirmed->status);
        $this->assertSame(PaymentStatus::Captured, $confirmed->payment->fresh()->status);
        $this->assertSame(['requested', 'confirmed'], AuditLog::where('entity', 'booking')->orderBy('id')->pluck('new_status')->all());
    }

    public function test_booking_is_never_confirmed_when_capture_fails_or_times_out(): void
    {
        $booking = $this->request();
        /** @var FakePaymentGateway $gateway */
        $gateway = app(PaymentGateway::class);

        foreach ([FakeGatewayMode::Decline, FakeGatewayMode::Timeout] as $mode) {
            $gateway->setMode($mode);
            try {
                $this->service()->accept($booking, $this->host);
                $this->fail("accept must fail on {$mode->value}");
            } catch (PaymentFailedException) {
                $this->assertSame(BookingStatus::Requested, $booking->fresh()->status);
                $this->assertSame(PaymentStatus::Authorised, $booking->payment->fresh()->status);
            }
        }

        // Once the gateway recovers the same request can still be accepted.
        $gateway->setMode(FakeGatewayMode::Success);
        $this->assertSame(BookingStatus::Confirmed, $this->service()->accept($booking, $this->host)->status);
    }

    public function test_only_the_host_can_accept_and_only_once(): void
    {
        $booking = $this->request();

        $this->assertSame('forbidden', $this->violation(fn () => $this->service()->accept($booking, $this->tourist)));
        $this->assertSame('forbidden', $this->violation(fn () => $this->service()->accept($booking, User::factory()->verifiedHost()->create())));

        $this->service()->accept($booking, $this->host);
        $this->assertSame('invalid_state', $this->violation(fn () => $this->service()->accept($booking, $this->host)));
    }

    public function test_expired_request_cannot_be_accepted(): void
    {
        $booking = $this->request();
        $booking->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->assertSame('request_expired', $this->violation(fn () => $this->service()->accept($booking, $this->host)));
    }

    public function test_overlap_is_checked_among_confirmed_bookings_with_half_open_intervals(): void
    {
        $this->confirmed('10:00:00', '12:00:00');

        $this->assertSame('overlap', $this->violation(fn () => $this->request('11:00')));
        $this->assertSame('overlap', $this->violation(fn () => $this->request('09:00')));
        $this->assertSame('overlap', $this->violation(fn () => $this->request('10:00')));
        // Touching at 12:00 is allowed (end-to-start), as is the free morning.
        $this->assertSame(BookingStatus::Requested, $this->request('12:00')->status);
        $this->assertSame(BookingStatus::Requested, $this->request('08:00')->status);
    }

    public function test_accept_rechecks_overlap_for_requests_made_before_the_other_was_confirmed(): void
    {
        // Two overlapping requests are fine while nothing is confirmed ...
        $first = $this->request('10:00');
        $second = $this->request('11:00', 1, null, User::factory()->tourist()->create());

        // ... but only one of them can be accepted.
        $this->service()->accept($first, $this->host);
        $this->assertSame('overlap', $this->violation(fn () => $this->service()->accept($second, $this->host)));
        $this->assertSame(BookingStatus::Requested, $second->fresh()->status);
        $this->assertSame(PaymentStatus::Authorised, $second->payment->fresh()->status);
    }

    public function test_at_most_two_confirmed_bookings_per_day(): void
    {
        $a = $this->request('08:00');
        $b = $this->request('11:00', 1, null, User::factory()->tourist()->create());
        $c = $this->request('15:00', 1, null, User::factory()->tourist()->create());

        $this->service()->accept($a, $this->host);
        $this->service()->accept($b, $this->host);

        $this->assertSame('daily_limit', $this->violation(fn () => $this->service()->accept($c, $this->host)));
        $this->assertSame('daily_limit', $this->violation(fn () => $this->request('18:00')));

        // Another day is unaffected.
        $this->assertSame(BookingStatus::Requested, $this->request('18:00', 1, now()->addDays(6)->toDateString())->status);
        // A cancelled booking frees the slot.
        $this->service()->cancel($a->fresh(), $this->host);
        $this->assertSame(BookingStatus::Confirmed, $this->service()->accept($c, $this->host)->status);
    }

    public function test_decline_and_expiry_release_the_reservation(): void
    {
        $declined = $this->request('08:00');
        $this->service()->decline($declined, $this->host, 'busy');
        $this->assertSame(BookingStatus::Declined, $declined->fresh()->status);
        $this->assertSame(PaymentStatus::Released, $declined->payment->fresh()->status);

        $expiring = $this->request('11:00');
        $this->assertFalse($this->service()->expire($expiring), 'not due yet');
        $expiring->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->assertTrue($this->service()->expire($expiring));
        $this->assertSame(BookingStatus::Expired, $expiring->fresh()->status);
        $this->assertSame(PaymentStatus::Released, $expiring->payment->fresh()->status);
        $this->assertFalse($this->service()->expire($expiring), 'second run does nothing');
    }

    public function test_tourist_cancellation_follows_the_refund_tiers(): void
    {
        $far = $this->request('10:00', 2, now()->addDays(5)->toDateString());
        $this->service()->accept($far, $this->host);
        $result = $this->service()->cancel($far, $this->tourist, 'plans changed');

        $this->assertSame('100.000', $result['refund']);
        $this->assertSame(BookingStatus::Cancelled, $far->fresh()->status);
        $this->assertSame($this->tourist->id, $far->fresh()->cancelled_by);
        $this->assertSame(PaymentStatus::Refunded, $far->payment->fresh()->status);
        $this->assertTrue($result['alternatives']->isEmpty(), 'alternatives are for late host cancellations only');
    }

    public function test_late_host_cancellation_refunds_in_full_and_returns_alternatives(): void
    {
        $other = User::factory()->verifiedHost()->create();
        app(AlternativeHostFinder::class)->setHostUserIds(collect([$other->id]));
        // A booking tomorrow morning is "late" (< 24 h) when it starts in 10 hours.
        $start = now()->addHours(10);
        $booking = Booking::factory()->confirmed()->create([
            'experience_id' => $this->experience->id, 'host_id' => $this->host->id, 'tourist_id' => $this->tourist->id,
            'date' => $start->toDateString(), 'start_time' => $start->format('H:i:s'), 'end_time' => $start->copy()->addHours(2)->format('H:i:s'),
            'displayed_price' => '80.000',
        ]);
        app(PaymentService::class)->capture(app(PaymentService::class)->authorise($booking));

        $result = $this->service()->cancel($booking, $this->host, 'sick');

        $this->assertSame('80.000', $result['refund']);
        $this->assertSame([$other->id], $result['alternatives']->all());
        $this->assertSame(PaymentStatus::Refunded, $booking->payment->fresh()->status);
    }

    public function test_late_tourist_cancellation_gets_nothing_back(): void
    {
        $start = now()->addHours(5);
        $booking = Booking::factory()->confirmed()->create([
            'experience_id' => $this->experience->id, 'host_id' => $this->host->id, 'tourist_id' => $this->tourist->id,
            'date' => $start->toDateString(), 'start_time' => $start->format('H:i:s'), 'end_time' => $start->copy()->addHours(2)->format('H:i:s'),
        ]);
        app(PaymentService::class)->capture(app(PaymentService::class)->authorise($booking));

        $this->assertSame('0.000', $this->service()->cancel($booking, $this->tourist)['refund']);
    }

    public function test_cancel_rules(): void
    {
        $requested = $this->request();
        $this->assertSame('invalid_state', $this->violation(fn () => $this->service()->cancel($requested, $this->tourist)));

        $confirmed = $this->service()->accept($requested, $this->host);
        $this->assertSame('forbidden', $this->violation(fn () => $this->service()->cancel($confirmed, User::factory()->tourist()->create())));

        $confirmed->forceFill(['date' => now()->subDay()->toDateString()])->save();
        $this->assertSame('already_started', $this->violation(fn () => $this->service()->cancel($confirmed, $this->tourist)));
    }

    public function test_confirmed_bookings_complete_two_hours_after_the_end(): void
    {
        $recent = $this->confirmed('08:00:00', '10:00:00', now()->toDateString());
        $recent->forceFill(['end_time' => now()->subHour()->format('H:i:s')])->save();
        $old = $this->confirmed('08:00:00', '10:00:00', now()->subDays(2)->toDateString());

        $this->assertFalse($this->service()->complete($recent->fresh()));
        $this->assertTrue($this->service()->complete($old));
        $this->assertSame(BookingStatus::Completed, $old->fresh()->status);
    }

    public function test_state_table(): void
    {
        $allowed = [
            'requested' => ['confirmed', 'declined', 'expired'],
            'confirmed' => ['cancelled', 'completed'],
            'declined' => [], 'expired' => [], 'cancelled' => [], 'completed' => [],
        ];

        foreach (BookingStatus::cases() as $from) {
            foreach (BookingStatus::cases() as $to) {
                $this->assertSame(in_array($to->value, $allowed[$from->value], true), BookingState::for($from)->canTransitionTo($to), "{$from->value}->{$to->value}");
            }
        }
    }

    public function test_web_flow_request_accept_and_role_based_views(): void
    {
        app(AlternativeHostFinder::class); // real binding is the test fake; irrelevant here

        $this->actingAs($this->tourist)->get("/experiences/{$this->experience->id}/book")->assertOk()->assertSee($this->experience->title);
        $this->post("/experiences/{$this->experience->id}/book", ['date' => $this->date, 'start_time' => '10:00', 'group_size' => 2])
            ->assertRedirect();
        $booking = Booking::firstOrFail();

        // Tourist sees status but no contact details yet.
        $this->get("/bookings/{$booking->id}")->assertOk()->assertSee('Requested')->assertDontSee($this->host->email);
        $this->get('/bookings')->assertOk()->assertSee($this->experience->title);

        // The host sees the request and accepts it.
        $this->actingAs($this->host)->get('/host/bookings')->assertOk()->assertSee($this->tourist->name);
        $this->post("/host/bookings/{$booking->id}/accept")->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        // Confirmed: both sides now see the other's contact details.
        $this->get("/bookings/{$booking->id}")->assertSee($this->tourist->email);
        $this->actingAs($this->tourist)->get("/bookings/{$booking->id}")->assertSee($this->host->email)->assertSee('Paid');

        // Strangers cannot open it.
        $this->actingAs(User::factory()->tourist()->create())->get("/bookings/{$booking->id}")->assertForbidden();
    }

    public function test_web_validation_and_permissions(): void
    {
        $this->actingAs($this->tourist)->post("/experiences/{$this->experience->id}/book", ['date' => 'tomorrow', 'start_time' => '25:00', 'group_size' => 99])
            ->assertSessionHasErrors(['date', 'start_time', 'group_size']);

        // A host cannot request bookings; a tourist cannot answer them.
        $this->actingAs($this->host)->get("/experiences/{$this->experience->id}/book")->assertForbidden();
        $booking = $this->request();
        $this->actingAs($this->tourist)->post("/host/bookings/{$booking->id}/accept")->assertForbidden();
        $this->get('/host/bookings')->assertForbidden();

        // Unpublished experiences and unverified hosts are not even visible.
        $this->experience->update(['published' => false]);
        $this->get("/experiences/{$this->experience->id}/book")->assertNotFound();
    }

    public function test_web_shows_rule_errors_in_the_users_language(): void
    {
        $this->confirmed('10:00:00', '12:00:00');

        $this->actingAs($this->tourist)->withSession(['locale' => 'fr'])
            ->post("/experiences/{$this->experience->id}/book", ['date' => $this->date, 'start_time' => '11:00', 'group_size' => 1])
            ->assertSessionHasErrors(['booking' => 'L’hôte a déjà une réservation confirmée à ce moment.']);
    }

    public function test_alternatives_screen_shows_after_a_late_host_cancellation(): void
    {
        $other = User::factory()->verifiedHost()->create();
        app(AlternativeHostFinder::class)->setHostUserIds(collect([$other->id]));
        $start = now()->addHours(6);
        $booking = Booking::factory()->cancelled()->create([
            'experience_id' => $this->experience->id, 'host_id' => $this->host->id, 'tourist_id' => $this->tourist->id,
            'date' => $start->toDateString(), 'start_time' => $start->format('H:i:s'), 'end_time' => $start->copy()->addHours(2)->format('H:i:s'),
            'cancelled_by' => $this->host->id, 'cancelled_at' => now(),
        ]);

        $this->actingAs($this->tourist)->get("/bookings/{$booking->id}")->assertOk()
            ->assertSee('Other verified hosts in the same city')->assertSee($other->hostProfile->display_name);
        $this->actingAs($this->host)->get("/bookings/{$booking->id}")->assertDontSee('Other verified hosts');
    }

    public function test_fake_alternative_finder_is_the_default_in_tests(): void
    {
        $this->assertInstanceOf(FakeAlternativeHostFinder::class, app(AlternativeHostFinder::class));
        $this->assertSame(0, DB::table('bookings')->count());
    }
}
