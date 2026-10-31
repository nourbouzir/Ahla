<?php

namespace Tests\Feature;

use App\Contracts\Fakes\FakeGatewayMode;
use App\Contracts\Fakes\FakePaymentGateway;
use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\PaymentGateway;
use App\Events\BookingCancelled;
use App\Events\RefundRequested;
use App\Jobs\AutoComplete;
use App\Jobs\ExpireRequests;
use App\Jobs\PurgeIdDocuments;
use App\Jobs\ReconcilePayments;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Experience\Models\Experience;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use App\Modules\Matching\Services\MatchingAlternativeHostFinder;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\RefundService;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\VerificationRequest;
use App\Modules\Verification\Services\VerifiedHostEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

class CancellationAndJobsTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    private User $host;

    private User $tourist;

    private Experience $experience;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // The real rules and the real finder, not the fakes.
        $this->app->singleton(HostEligibility::class, VerifiedHostEligibility::class);
        $this->app->singleton(AvailabilityChecker::class, WeeklyAvailabilityChecker::class);
        $this->app->singleton(AlternativeHostFinder::class, MatchingAlternativeHostFinder::class);

        $this->host = $this->makeHost('Main Host', 'Tunis', ['en'], ['food']);
        $this->tourist = User::factory()->tourist()->create();
        $this->experience = $this->host->hostProfile->experiences()->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function confirmedAt(Carbon $start, ?User $host = null): Booking
    {
        $booking = Booking::factory()->confirmed()->create([
            'experience_id' => ($host ?? $this->host)->hostProfile->experiences()->first()->id,
            'host_id' => ($host ?? $this->host)->id, 'tourist_id' => $this->tourist->id,
            'date' => $start->toDateString(), 'start_time' => $start->format('H:i:s'), 'end_time' => $start->copy()->addHours(2)->format('H:i:s'),
            'displayed_price' => '90.000',
        ]);
        app(PaymentService::class)->capture(app(PaymentService::class)->authorise($booking));

        return $booking->fresh('payment');
    }

    // ---- real AlternativeHostFinder (#26) ---------------------------------

    public function test_finder_returns_same_city_verified_free_hosts_best_first_capped_at_three(): void
    {
        Carbon::setTestNow(now()->setTime(10, 0));
        $start = now()->addHours(5);
        $booking = $this->confirmedAt($start);

        $good = $this->makeHost('Good', 'Tunis', ['en'], ['food'], 4.8);
        $ok = $this->makeHost('Okay', 'Tunis', ['fr'], ['art'], 4.0);
        $third = $this->makeHost('Third', 'Tunis', ['ar'], ['art'], 3.9);
        $fourth = $this->makeHost('Fourth', 'Tunis', ['ar'], ['art'], 3.0);
        $this->makeHost('Other City', 'Sousse');
        $this->makeHost('Pending', 'Tunis', status: HostStatus::Pending);
        $this->tourist->languages()->create(['language' => 'en']);
        $this->tourist->interests()->create(['interest' => 'food']);

        $ids = app(AlternativeHostFinder::class)->find($booking->fresh());

        $this->assertSame([$good->id, $ok->id, $third->id], $ids->all(), 'ordered by match, capped at 3, no cancelling host');
        $this->assertNotContains($fourth->id, $ids->all());
        $this->assertNotContains($this->host->id, $ids->all());
    }

    public function test_finder_skips_hosts_who_are_busy_blocked_or_unavailable(): void
    {
        Carbon::setTestNow(now()->setTime(10, 0));
        $start = now()->addHours(5);
        $booking = $this->confirmedAt($start);

        $busy = $this->makeHost('Busy', 'Tunis');
        $this->confirmedAt($start, $busy);                      // overlapping confirmed booking
        $blocked = $this->makeHost('Blocked', 'Tunis');
        $blocked->hostProfile->blockedDates()->create(['date' => $start->toDateString()]);
        $closed = $this->makeHost('Closed', 'Tunis');
        $closed->hostProfile->availabilities()->delete();       // no weekly hours
        $free = $this->makeHost('Free', 'Tunis');

        $this->assertSame([$free->id], app(AlternativeHostFinder::class)->find($booking->fresh())->all());
    }

    public function test_finder_with_no_match_returns_empty(): void
    {
        $booking = $this->confirmedAt(now()->addHours(5));

        $this->assertTrue(app(AlternativeHostFinder::class)->find($booking)->isEmpty());
    }

    // ---- host cancellation (#27, #28) -------------------------------------

    public function test_cancelling_19_hours_before_refunds_fully_and_suggests_hosts(): void
    {
        Carbon::setTestNow(now()->setTime(15, 0));   // booking starts 19 h later, 10:00 next day
        Event::fake([BookingCancelled::class, RefundRequested::class]);
        $booking = $this->confirmedAt(now()->addHours(19));
        $alt = $this->makeHost('Alt', 'Tunis');

        $result = app(BookingService::class)->cancel($booking, $this->host, 'sick');

        $this->assertSame('90.000', $result['refund']);
        $this->assertSame([$alt->id], $result['alternatives']->all());
        $this->assertSame(PaymentStatus::Refunded, $booking->payment->fresh()->status);
        Event::assertDispatched(BookingCancelled::class);
        Event::assertDispatched(RefundRequested::class, fn ($e) => $e->amount === '90.000');

        // Tourist sees refund status and the suggested host with a link; the host does not.
        $this->actingAs($this->tourist)->get("/bookings/{$booking->id}")->assertOk()
            ->assertSee('Other verified hosts in the same city')->assertSee('Alt')->assertSee(route('hosts.show', $alt->hostProfile), false)
            ->assertSee('Refunded');
        $this->actingAs($this->host)->get("/bookings/{$booking->id}")->assertDontSee('Other verified hosts');
    }

    public function test_cancelling_more_than_24_hours_before_gives_full_refund_without_suggestions(): void
    {
        $booking = $this->confirmedAt(now()->addDays(3));
        $this->makeHost('Alt', 'Tunis');

        $result = app(BookingService::class)->cancel($booking, $this->host);

        $this->assertSame('90.000', $result['refund']);
        $this->assertTrue($result['alternatives']->isEmpty());
    }

    public function test_double_submission_never_refunds_twice(): void
    {
        Event::fake([RefundRequested::class]);
        $booking = $this->confirmedAt(now()->addDays(3));

        $this->actingAs($this->host)->post("/bookings/{$booking->id}/cancel")->assertRedirect();
        $this->post("/bookings/{$booking->id}/cancel")->assertSessionHasErrors('booking');

        Event::assertDispatchedTimes(RefundRequested::class, 1);
        $this->assertSame('90.000', (string) $booking->payment->fresh()->refunded_amount);
    }

    public function test_a_failed_refund_does_not_undo_the_cancellation_and_is_retried_by_the_job(): void
    {
        $booking = $this->confirmedAt(now()->addDays(3));
        /** @var FakePaymentGateway $gateway */
        $gateway = app(PaymentGateway::class);
        $gateway->setMode(FakeGatewayMode::Timeout);

        $this->actingAs($this->host)->post("/bookings/{$booking->id}/cancel")
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'retried'));

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(PaymentStatus::Captured, $booking->payment->fresh()->status);
        $this->actingAs($this->tourist)->get("/bookings/{$booking->id}")->assertSee('refund is being processed');

        // Gateway still down: the job tries and fails quietly, nothing changes.
        (new ReconcilePayments)->handle(app(RefundService::class), app(PaymentService::class));
        $this->assertSame(PaymentStatus::Captured, $booking->payment->fresh()->status);

        // Gateway back: the job completes the refund; running it again changes nothing.
        $gateway->setMode(FakeGatewayMode::Success);
        $first = (new ReconcilePayments)->handle(app(RefundService::class), app(PaymentService::class));
        $second = (new ReconcilePayments)->handle(app(RefundService::class), app(PaymentService::class));

        $this->assertSame(1, $first['refunds']);
        $this->assertSame(0, $second['refunds']);
        $this->assertSame(PaymentStatus::Refunded, $booking->payment->fresh()->status);
        $this->assertSame('90.000', (string) $booking->payment->fresh()->refunded_amount);
    }

    public function test_late_tourist_cancellation_is_not_retried_forever(): void
    {
        Event::fake([RefundRequested::class]);
        $booking = $this->confirmedAt(now()->addHours(5));
        app(BookingService::class)->cancel($booking, $this->tourist);

        $result = (new ReconcilePayments)->handle(app(RefundService::class), app(PaymentService::class));

        $this->assertSame(0, $result['refunds']);
        Event::assertNotDispatched(RefundRequested::class);
        $this->actingAs($this->tourist)->get("/bookings/{$booking->id}")->assertDontSee('refund is being processed');
    }

    // ---- lifecycle jobs (#29) ---------------------------------------------

    public function test_expiry_job_releases_the_hold_and_is_idempotent(): void
    {
        Carbon::setTestNow('2030-05-01 09:00:00');
        $booking = app(BookingService::class)->request($this->tourist, $this->experience, '2030-05-03', '10:00', 2);

        Carbon::setTestNow('2030-05-01 20:59:00');   // 11 h 59 min: still open
        $this->assertSame(0, (new ExpireRequests)->handle(app(BookingService::class)));
        $this->assertSame(BookingStatus::Requested, $booking->fresh()->status);

        Carbon::setTestNow('2030-05-01 21:01:00');   // past 12 h
        $this->assertSame(1, (new ExpireRequests)->handle(app(BookingService::class)));
        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
        $this->assertSame(PaymentStatus::Released, $booking->payment->fresh()->status);

        $this->assertSame(0, (new ExpireRequests)->handle(app(BookingService::class)), 'second run changes nothing');
    }

    public function test_auto_complete_job_runs_two_hours_after_the_end_and_is_idempotent(): void
    {
        Carbon::setTestNow('2030-05-03 13:00:00');
        $booking = $this->confirmedAt(Carbon::parse('2030-05-03 10:00:00'));       // ends 12:00

        $this->assertSame(0, (new AutoComplete)->handle(app(BookingService::class)), '13:00 is only 1 h after the end');

        Carbon::setTestNow('2030-05-03 14:00:01');
        $this->assertSame(1, (new AutoComplete)->handle(app(BookingService::class)));
        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
        $this->assertSame(0, (new AutoComplete)->handle(app(BookingService::class)));
    }

    public function test_reconcile_releases_a_reservation_held_for_a_closed_booking(): void
    {
        $booking = Booking::factory()->declined()->create([
            'experience_id' => $this->experience->id, 'host_id' => $this->host->id, 'displayed_price' => '30.000',
        ]);
        $payment = app(PaymentService::class)->authorise($booking);

        $result = (new ReconcilePayments)->handle(app(RefundService::class), app(PaymentService::class));

        $this->assertSame(1, $result['releases']);
        $this->assertSame(PaymentStatus::Released, $payment->fresh()->status);
    }

    public function test_purge_job_deletes_old_id_documents_only(): void
    {
        Storage::fake('local');
        $old = VerificationRequest::factory()->create(['status' => 'decided', 'decided_at' => now()->subDays(91), 'evidence_path' => 'verification/1/old.jpg.enc']);
        $recent = VerificationRequest::factory()->create(['status' => 'decided', 'decided_at' => now()->subDays(10), 'evidence_path' => 'verification/1/recent.jpg.enc']);
        $open = VerificationRequest::factory()->create(['status' => 'submitted', 'decided_at' => null, 'evidence_path' => 'verification/1/open.jpg.enc']);
        foreach ([$old, $recent, $open] as $r) {
            Storage::disk('local')->put($r->evidence_path, 'x');
        }

        $this->assertSame(1, (new PurgeIdDocuments)->handle());
        $this->assertSame(0, (new PurgeIdDocuments)->handle());

        Storage::disk('local')->assertMissing('verification/1/old.jpg.enc');
        Storage::disk('local')->assertExists('verification/1/recent.jpg.enc');
        Storage::disk('local')->assertExists('verification/1/open.jpg.enc');
        $this->assertSame('purged', $old->fresh()->evidence_path);
    }

    public function test_jobs_are_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('ExpireRequests')->assertExitCode(0);
    }
}
