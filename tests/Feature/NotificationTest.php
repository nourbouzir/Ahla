<?php

namespace Tests\Feature;

use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Experience\Models\Experience;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use App\Modules\Notification\Notifications\EventMailNotification;
use App\Modules\Notification\Notifications\EventNotification;
use App\Modules\Verification\Services\VerifiedHostEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    private User $host;

    private User $tourist;

    private Experience $experience;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->singleton(HostEligibility::class, VerifiedHostEligibility::class);
        $this->app->singleton(AvailabilityChecker::class, WeeklyAvailabilityChecker::class);
        $this->host = $this->makeHost('Notified Host');
        $this->tourist = User::factory()->tourist()->create();
        $this->experience = $this->host->hostProfile->experiences()->first();
    }

    private function kinds(User $user): array
    {
        return $user->notifications()->orderBy('created_at')->get()->pluck('data.kind')->all();
    }

    private function request(): Booking
    {
        return app(BookingService::class)->request($this->tourist, $this->experience, now()->addDays(5)->toDateString(), '10:00', 2);
    }

    public function test_each_in_scope_event_notifies_the_right_user(): void
    {
        $booking = $this->request();
        $this->assertSame(['booking_requested'], $this->kinds($this->host));
        $this->assertSame([], $this->kinds($this->tourist), 'the requester is not notified of their own request');

        app(BookingService::class)->accept($booking, $this->host);
        $this->assertEqualsCanonicalizing(['booking_confirmed', 'payment_captured'], $this->kinds($this->tourist));

        app(BookingService::class)->cancel($booking->fresh(), $this->host, 'sick');
        $this->assertContains('booking_cancelled', $this->kinds($this->tourist), 'tourist is told the host cancelled');
        $this->assertContains('refund_issued', $this->kinds($this->tourist));
        $this->assertNotContains('booking_cancelled', $this->kinds($this->host), 'the canceller is not notified');
    }

    public function test_decline_expiry_and_messages(): void
    {
        $declined = $this->request();
        app(BookingService::class)->decline($declined, $this->host);
        $this->assertEqualsCanonicalizing(['booking_declined', 'payment_released'], $this->kinds($this->tourist));

        $expiring = app(BookingService::class)->request($this->tourist, $this->experience, now()->addDays(6)->toDateString(), '10:00', 1);
        $expiring->forceFill(['expires_at' => now()->subMinute()])->save();
        app(BookingService::class)->expire($expiring);
        $this->assertContains('booking_expired', $this->kinds($this->tourist));
        $this->assertContains('booking_expired', $this->kinds($this->host));

        $open = app(BookingService::class)->request($this->tourist, $this->experience, now()->addDays(8)->toDateString(), '10:00', 1);
        $this->actingAs($this->tourist)->post("/bookings/{$open->id}/messages", ['body' => 'hello']);
        $this->assertContains('message_received', $this->kinds($this->host));
    }

    public function test_mail_is_queued_and_respects_the_preference(): void
    {
        Notification::fake();
        $this->request();

        Notification::assertSentTo($this->host, EventNotification::class);
        Notification::assertSentTo($this->host, EventMailNotification::class);

        $notification = new EventMailNotification('booking_requested', 'https://x.test', ['experience' => 'E', 'name' => 'N', 'date' => 'D']);
        $this->assertSame(['mail'], $notification->via($this->host));
        $this->host->forceFill(['email_notifications' => false])->save();
        $this->assertSame([], $notification->via($this->host->fresh()), 'e-mail off: in-app only');
    }

    public function test_a_failing_mail_provider_never_breaks_the_original_action(): void
    {
        Event::listen(NotificationSending::class, function (NotificationSending $event) {
            if ($event->channel === 'mail') {
                throw new \RuntimeException('smtp down');
            }
        });

        $booking = $this->request();                       // must not throw

        $this->assertSame('requested', $booking->fresh()->status->value);
        $this->assertSame(['booking_requested'], $this->kinds($this->host), 'the in-app notification is still stored');
    }

    public function test_notifications_are_written_in_the_recipients_language(): void
    {
        $this->host->update(['preferred_language' => 'fr']);
        $this->request();
        $this->actingAs($this->host->fresh())->withSession(['locale' => 'fr'])->get('/notifications')
            ->assertOk()->assertSee('Nouvelle demande de réservation');
        $this->withSession(['locale' => 'ar'])->get('/notifications')->assertSee('طلب حجز جديد')->assertSee('dir="rtl"', false);
    }

    public function test_list_open_mark_read_and_unread_badge(): void
    {
        $this->request();
        $this->actingAs($this->host);

        $this->get('/')->assertSee('aria-label="1 New"', false);
        $id = $this->host->notifications()->first()->id;

        $this->get("/notifications/$id/open")->assertRedirect()->assertRedirectContains('/bookings/');
        $this->assertSame(0, $this->host->unreadNotifications()->count());

        $this->post('/notifications/read-all')->assertRedirect();
        $this->post('/notifications/preferences', [])->assertSessionHas('status');
        $this->assertFalse($this->host->fresh()->email_notifications);
    }

    public function test_users_cannot_open_each_others_notifications(): void
    {
        $this->request();
        $id = $this->host->notifications()->first()->id;

        $this->actingAs($this->tourist)->get("/notifications/$id/open")->assertNotFound();
        $this->get('/notifications')->assertOk()->assertDontSee('New booking request');
    }
}
