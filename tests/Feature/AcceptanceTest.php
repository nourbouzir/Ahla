<?php

namespace Tests\Feature;

use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\PaymentGateway;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use App\Modules\Matching\Services\MatchingAlternativeHostFinder;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Gateways\SandboxPaymentGateway;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\VerificationRequest;
use App\Modules\Verification\Services\VerifiedHostEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

/**
 * Acceptance tests named AT-FR-xx after the SRS FV requirement they prove
 * (see docs/traceability.md). They run with the REAL host eligibility,
 * availability checker, alternative-host finder and the sandbox payment gateway.
 */
class AcceptanceTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Cache::flush();

        $this->app->singleton(HostEligibility::class, VerifiedHostEligibility::class);
        $this->app->singleton(AvailabilityChecker::class, WeeklyAvailabilityChecker::class);
        $this->app->singleton(AlternativeHostFinder::class, MatchingAlternativeHostFinder::class);
        $this->app->singleton(PaymentGateway::class, SandboxPaymentGateway::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function logout(): void
    {
        auth()->logout();
        $this->flushSession();
    }

    /**
     * AT-FR-01..03, 05..11, 14..17, 20 in one story: register -> verify -> publish ->
     * search -> request and pay -> confirmed -> host cancels late -> refund + alternative.
     */
    public function test_at_main_journey_from_registration_to_late_host_cancellation(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        // FR-01, FR-03: a tourist registers and fills the profile.
        $this->post('/register', ['name' => 'Lea Martin', 'email' => 'lea@example.test', 'role' => 'tourist', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
            ->assertRedirect('/profile');
        $tourist = User::where('email', 'lea@example.test')->firstOrFail();
        $this->put('/profile', ['name' => 'Lea Martin', 'preferred_language' => 'en', 'languages' => ['fr', 'en'], 'interests' => ['food']])->assertSessionHasNoErrors();
        $this->logout();

        // FR-01, FR-03: a host registers and fills the host profile (starts Pending: FR-06).
        $this->post('/register', ['name' => 'Amel B', 'email' => 'amel@example.test', 'role' => 'host', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1']);
        $host = User::where('email', 'amel@example.test')->firstOrFail();
        $this->put('/profile', ['name' => 'Amel B', 'preferred_language' => 'en', 'languages' => ['fr', 'ar'], 'interests' => ['food', 'history'],
            'display_name' => 'Amel Ben Youssef', 'bio' => 'Medina storyteller', 'city' => 'Tunis'])->assertSessionHasNoErrors();
        $this->assertSame(HostStatus::Pending, $host->fresh()->hostProfile->status);

        // (One PHP process serves every request in a test, so reload the user like a new browser request would.)
        $this->actingAs($host->fresh());

        // FR-07: a pending host cannot be found or booked.
        $this->getJson('/api/search?city=Tunis')->assertJsonCount(0, 'data');

        // FR-05: identity evidence, then FR-11: experience and weekly hours (hidden until verified).
        $this->post('/host/verification', ['evidence' => UploadedFile::fake()->image('id.png')])->assertSessionHasNoErrors();
        $this->post('/host/experiences', ['title' => 'Medina walk', 'category' => 'Culture', 'description' => 'A slow walk.', 'duration_hours' => 2,
            'price_per_person' => '40', 'max_group_size' => 4, 'meeting_point' => 'Bab Bhar', 'interests' => ['food'], 'published' => 1])->assertSessionHasNoErrors();
        $this->post('/host/availability/slots', ['day_of_week' => now()->dayOfWeek, 'starts_at' => '08:00', 'ends_at' => '20:00'])->assertSessionHasNoErrors();
        $this->logout();

        // FR-05, FR-06: the admin schedules the video call and verifies the host.
        $admin = User::factory()->admin()->create();
        $request = VerificationRequest::firstOrFail();
        $this->actingAs($admin)->post("/admin/verifications/{$request->id}/schedule", ['video_call_at' => now()->addDay()->format('Y-m-d\TH:i'), 'video_call_link' => 'https://meet.jit.si/ahla'])->assertSessionHasNoErrors();
        $this->post("/admin/verifications/{$request->id}/decide", ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame(HostStatus::Verified, $host->fresh()->hostProfile->status);
        $this->logout();

        // A second verified host in the same city, free at the same time: the alternative.
        $alternative = $this->makeHost('Backup Host', 'Tunis', ['fr'], ['food'], 4.7);

        // FR-08, FR-09, FR-10: the tourist searches and sees WHY.
        $this->actingAs($tourist);
        $found = collect($this->getJson('/api/search?city=Tunis&language=fr')->assertOk()->json('data'));
        $amel = $found->firstWhere('name', 'Amel Ben Youssef');
        $this->assertNotNull($amel, 'the verified host appears');
        $this->assertNotEmpty($amel['why_this_host']);
        $this->get('/search?city=Tunis')->assertSee('Amel Ben Youssef')->assertSee('Why this host')->assertSee('Verified');

        // FR-14: request for today 14:00 (5 h away) with host, date, time, location and price.
        $experience = $host->hostProfile->experiences()->firstOrFail();
        $this->post("/experiences/{$experience->id}/book", ['date' => today()->toDateString(), 'start_time' => '14:00', 'group_size' => 2])->assertSessionHasNoErrors();
        $booking = Booking::firstOrFail();
        $this->assertSame('80.000', (string) $booking->displayed_price);
        $this->assertSame('Bab Bhar', $booking->location);
        $this->assertSame('requested', $booking->status->value);

        // FR-15: still not confirmed - only authorised. Then the host accepts and the capture succeeds.
        $this->assertSame(PaymentStatus::Authorised, $booking->payment->status);
        $this->actingAs($host)->post("/host/bookings/{$booking->id}/accept")->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $booking->fresh()->status->value);
        $this->assertSame(PaymentStatus::Captured, $booking->payment->fresh()->status);

        // FR-16: tourist, host and admin each see the statuses.
        $this->actingAs($tourist)->get("/bookings/{$booking->id}")->assertSee('Confirmed')->assertSee('Paid');
        $this->actingAs($host)->get("/bookings/{$booking->id}")->assertSee('Confirmed');
        $this->actingAs($admin)->get('/admin/bookings')->assertSee('Medina walk')->assertSee('Paid');

        // FR-17: the host cancels 5 h before: full refund and an available host in the same city.
        $this->actingAs($host)->post("/bookings/{$booking->id}/cancel", ['reason' => 'sick'])->assertSessionHasNoErrors();
        $this->assertSame(PaymentStatus::Refunded, $booking->payment->fresh()->status);
        $this->assertSame('80.000', (string) $booking->payment->fresh()->refunded_amount);
        $this->actingAs($tourist)->get("/bookings/{$booking->id}")
            ->assertSee('Cancelled')->assertSee('Refunded')->assertSee('Other verified hosts in the same city')->assertSee($alternative->hostProfile->display_name);

        // FR-20: the tourist was told about the cancellation and the refund.
        $kinds = $tourist->notifications->pluck('data.kind')->all();
        $this->assertContains('booking_confirmed', $kinds);
        $this->assertContains('booking_cancelled', $kinds);
        $this->assertContains('refund_issued', $kinds);
    }

    public function test_at_fr_04_a_user_controls_whether_the_phone_is_shared(): void
    {
        $booking = Booking::factory()->confirmed()->create();
        $tourist = $booking->tourist;
        $host = $booking->host;
        $host->forceFill(['phone' => '+216 98 765 432', 'share_phone' => true])->save();
        $host->hostProfile->forceFill(['status' => HostStatus::Verified])->save();

        $this->actingAs($tourist)->get("/profiles/{$host->id}")->assertSee('+216 98 765 432')->assertSee($host->email);

        // The host switches the phone off: the e-mail stays (needed to contact), the number disappears.
        $this->actingAs($host)->put('/profile', ['name' => $host->name, 'preferred_language' => 'en', 'phone' => '+216 98 765 432',
            'display_name' => 'H', 'bio' => 'b', 'city' => 'Tunis'])->assertSessionHasNoErrors();
        $this->assertFalse($host->fresh()->share_phone);

        $this->actingAs($tourist)->get("/profiles/{$host->id}")->assertDontSee('+216 98 765 432')->assertSee($host->email);
        $this->get("/bookings/{$booking->id}")->assertDontSee('+216 98 765 432');
        $this->actingAs($host)->get("/profiles/{$host->id}")->assertSee('+216 98 765 432');
    }

    public function test_at_fr_16_only_admins_may_open_the_booking_overview(): void
    {
        $booking = Booking::factory()->create();

        $this->actingAs($booking->tourist)->get('/admin/bookings')->assertForbidden();
        $this->actingAs($booking->host)->get('/admin/bookings')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/bookings?status=requested')->assertOk()->assertSee('#'.$booking->id);
    }
}
