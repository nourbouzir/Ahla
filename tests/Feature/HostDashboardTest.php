<?php

namespace Tests\Feature;

use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\HostClientNote;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Booking\Services\HostDashboard;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use App\Modules\Messaging\Models\Message;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
use App\Modules\Verification\Services\VerifiedHostEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

class HostDashboardTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    private User $host;

    private User $ana;

    private User $ben;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->host = $this->makeHost('Guide One');
        $this->ana = User::factory()->tourist()->create(['name' => 'Ana Client', 'phone' => '+216 11 111 111']);
        $this->ben = User::factory()->tourist()->create(['name' => 'Ben Client']);
    }

    private function booking(User $tourist, string $status = 'confirmed', ?string $date = null, string $price = '100.000', ?string $payment = 'captured', string $refunded = '0.000'): Booking
    {
        $booking = Booking::factory()->create([
            'tourist_id' => $tourist->id, 'host_id' => $this->host->id,
            'experience_id' => $this->host->hostProfile->experiences()->first()->id,
            'date' => $date ?? now()->addDays(2)->toDateString(), 'displayed_price' => $price,
            'status' => $status, 'expires_at' => $status === 'requested' ? now()->addHours(5) : null,
        ]);

        if ($payment) {
            $p = new Payment(['booking_id' => $booking->id, 'amount' => $price, 'currency' => 'TND']);
            $p->forceFill(['gateway_ref' => 'ref-'.$booking->id, 'status' => PaymentStatus::from($payment), 'refunded_amount' => $refunded, 'authorised_at' => now(), 'captured_at' => now()])->save();
        }

        return $booking;
    }

    public function test_only_guides_can_open_the_guide_space(): void
    {
        foreach (['/host/dashboard', '/host/clients', '/host/inbox', '/host/calendar', '/host/earnings'] as $url) {
            $this->get($url)->assertRedirect('/login');
            $this->actingAs($this->ana)->get($url)->assertForbidden();
            $this->actingAs(User::factory()->admin()->create())->get($url)->assertForbidden();
            $this->actingAs($this->host)->get($url)->assertOk();
            auth()->logout();
        }
    }

    public function test_people_land_in_their_own_space_after_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $this->host->email, 'password' => 'password'])->assertRedirect('/host/dashboard');
        auth()->logout();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin');
        auth()->logout();
        $this->post('/login', ['email' => $this->ana->email, 'password' => 'password'])->assertRedirect('/');
    }

    public function test_overview_shows_requests_agenda_earnings_and_checklist(): void
    {
        $this->booking($this->ana, 'requested', payment: 'authorised');
        $this->booking($this->ben, 'confirmed', now()->addDay()->toDateString(), '80.000');
        $this->booking($this->ben, 'completed', now()->startOfMonth()->toDateString(), '60.000', 'captured', '10.000');

        $page = $this->actingAs($this->host)->get('/host/dashboard')->assertOk();

        $page->assertSee('Waiting for your answer')->assertSee('Ana Client')->assertSee('Accept')->assertSee('data-countdown', false)
            ->assertSee('Next 7 days')->assertSee('Ben Client')
            ->assertSee('Get ready for bookings')
            // this month: 60 - 10 refunded (the confirmed one is paid but dated later; counted in its own month)
            ->assertSee('Earned this month');
        $overview = app(HostDashboard::class)->overview($this->host);
        $this->assertCount(1, $overview['pending']);
        $this->assertCount(1, $overview['upcoming'] ?? []);
        $this->assertSame(2, $overview['clients']);
        $this->assertSame(1, $overview['completed']);
    }

    public function test_requests_can_be_answered_from_the_dashboard(): void
    {
        $booking = $this->booking($this->ana, 'requested', payment: null);
        // a real authorised payment is needed to accept: use the real flow instead of the shortcut above
        $booking->delete();
        $this->app->singleton(HostEligibility::class, VerifiedHostEligibility::class);
        $this->app->singleton(AvailabilityChecker::class, WeeklyAvailabilityChecker::class);
        $real = app(BookingService::class)->request($this->ana, $this->host->hostProfile->experiences()->first(), now()->addDays(3)->toDateString(), '10:00', 2);

        $this->actingAs($this->host)->post("/host/bookings/{$real->id}/accept")->assertSessionHasNoErrors();

        $this->assertSame('confirmed', $real->fresh()->status->value);
        $this->get('/host/dashboard')->assertSee('Ana Client');
    }

    public function test_clients_list_search_sort_and_stage_filter_only_show_own_clients(): void
    {
        $this->booking($this->ana, 'completed', now()->subDays(20)->toDateString(), '100.000');
        $this->booking($this->ana, 'completed', now()->subDays(10)->toDateString(), '50.000');
        $this->booking($this->ben, 'confirmed', now()->addDays(3)->toDateString(), '200.000');
        $stranger = User::factory()->tourist()->create(['name' => 'Zed Stranger']);
        Booking::factory()->create(['tourist_id' => $stranger->id]);   // belongs to another guide

        $this->actingAs($this->host)->get('/host/clients')->assertOk()->assertSee('Ana Client')->assertSee('Ben Client')->assertDontSee('Zed Stranger');
        $this->get('/host/clients?q=ben')->assertSee('Ben Client')->assertDontSee('Ana Client');
        $this->get('/host/clients?stage=returning')->assertSee('Ana Client')->assertDontSee('Ben Client');
        $this->get('/host/clients?stage=upcoming')->assertSee('Ben Client')->assertDontSee('Ana Client');
        $this->get('/host/clients?sort=spent')->assertSeeInOrder(['Ben Client', 'Ana Client']);
        $this->get('/host/clients?sort=name')->assertSeeInOrder(['Ana Client', 'Ben Client']);
        $this->get('/host/clients?q=nobody')->assertSee('No client matches');
    }

    public function test_client_page_history_private_note_and_contact_rules(): void
    {
        $this->booking($this->ana, 'requested', payment: 'authorised');
        $this->actingAs($this->host);

        // Before any confirmed booking the phone and e-mail stay hidden.
        $this->get("/host/clients/{$this->ana->id}")->assertOk()->assertSee('Booking history')->assertDontSee('+216 11 111 111')->assertDontSee($this->ana->email);

        $this->booking($this->ana, 'confirmed', now()->addDays(5)->toDateString());
        $this->get("/host/clients/{$this->ana->id}")->assertSee('+216 11 111 111')->assertSee($this->ana->email);

        // The client can switch the phone off.
        $this->ana->update(['share_phone' => false]);
        $this->get("/host/clients/{$this->ana->id}")->assertDontSee('+216 11 111 111')->assertSee($this->ana->email);

        // Private note: saved, shown, cleared, and invisible to other guides.
        $this->put("/host/clients/{$this->ana->id}/note", ['body' => 'Vegetarian, loves photos'])->assertSessionHas('status');
        $this->get("/host/clients/{$this->ana->id}")->assertSee('Vegetarian, loves photos');
        $other = $this->makeHost('Guide Two');
        $this->actingAs($other)->get("/host/clients/{$this->ana->id}")->assertNotFound();
        $this->actingAs($other)->put("/host/clients/{$this->ana->id}/note", ['body' => 'hacked'])->assertNotFound();
        $this->assertSame('Vegetarian, loves photos', HostClientNote::where('host_id', $this->host->id)->value('body'));

        $this->actingAs($this->host)->put("/host/clients/{$this->ana->id}/note", ['body' => ''])->assertSessionHasNoErrors();
        $this->assertSame(0, HostClientNote::count());
        $this->put("/host/clients/{$this->ana->id}/note", ['body' => str_repeat('x', 2001)])->assertSessionHasErrors('body');
        $this->get("/host/clients/{$this->ben->id}")->assertNotFound();
    }

    public function test_inbox_lists_conversations_with_unread_counts_and_marks_read_when_opened(): void
    {
        $booking = $this->booking($this->ana, 'confirmed');
        Message::create(['booking_id' => $booking->id, 'sender_id' => $this->ana->id, 'recipient_id' => $this->host->id, 'original_text' => 'Hello guide, any tips?', 'language' => 'en']);
        Message::create(['booking_id' => $booking->id, 'sender_id' => $this->ana->id, 'recipient_id' => $this->host->id, 'original_text' => 'Second message', 'language' => 'en']);
        $this->booking($this->ben, 'declined', payment: 'released');   // closed and silent: not listed

        $this->actingAs($this->host)->get('/host/inbox')->assertOk()->assertSee('2 unread')->assertSee('Ana Client')->assertSee('Second message')->assertDontSee('Ben Client');
        $this->getJson('/host/dashboard/summary')->assertJson(['host.inbox' => 2, 'host.bookings.index' => 0]);
        $this->get('/host/inbox?unread=1')->assertSee('Ana Client');

        $this->get("/bookings/{$booking->id}/messages")->assertOk();
        $this->get('/host/inbox?unread=1')->assertSee('No unread messages');
        $this->getJson('/host/dashboard/summary')->assertJson(['host.inbox' => 0]);
    }

    public function test_calendar_shows_bookings_days_off_and_survives_bad_input(): void
    {
        $date = now()->addDays(2);
        $this->booking($this->ana, 'confirmed', $date->toDateString());
        $this->host->hostProfile->blockedDates()->create(['date' => $date->copy()->addDay()->toDateString()]);
        $this->actingAs($this->host);

        $this->get('/host/calendar?month='.$date->format('Y-m'))->assertOk()->assertSee('Ana Client')->assertSee('Off');
        $this->get('/host/calendar?month=garbage')->assertOk()->assertSee(now()->translatedFormat('F Y'));
        $this->get('/host/calendar?month=9999-01')->assertOk()->assertSee(now()->translatedFormat('F Y'));
    }

    public function test_earnings_are_net_of_refunds_and_exportable(): void
    {
        $this->booking($this->ana, 'completed', now()->subDays(40)->toDateString(), '100.000', 'captured');
        $this->booking($this->ben, 'cancelled', now()->subDays(40)->toDateString(), '60.000', 'refunded', '60.000');
        $this->booking($this->ana, 'completed', now()->subDays(2)->toDateString(), '90.000', 'captured', '30.000');
        $this->booking($this->ben, 'requested', payment: 'authorised');   // not captured: never counted

        $page = $this->actingAs($this->host)->get('/host/earnings')->assertOk();
        $page->assertSee('250.000 TND')->assertSee('90.000 TND')->assertSee('160.000 TND');

        $month = now()->subDays(2)->format('Y-m');
        $this->get("/host/earnings?month=$month")->assertSee('60.000 TND');

        $csv = $this->get('/host/earnings.csv')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('gross_tnd', $csv->getContent());
        $this->assertSame(4, substr_count($csv->getContent(), "\n"));   // header + 3 paid bookings
    }

    public function test_csv_neutralises_spreadsheet_formulas(): void
    {
        $this->host->hostProfile->experiences()->first()->update(['title' => '=HYPERLINK("http://evil.test")']);
        $this->booking($this->ana, 'completed', now()->subDay()->toDateString());

        $csv = $this->actingAs($this->host)->get('/host/earnings.csv')->getContent();

        $this->assertStringContainsString("\"'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',"=HYPERLINK', $csv);
    }

    public function test_guide_space_menu_and_other_host_pages_use_the_shell(): void
    {
        $this->actingAs($this->host)->get('/host/experiences')->assertOk()->assertSee('Guide space')->assertSee('Clients')->assertSee('Earnings');
        $this->get('/host/availability')->assertOk()->assertSee('Overview');
        $this->get('/host/bookings')->assertOk()->assertSee('Messages');
        $this->get('/')->assertSee(route('host.dashboard'), false);
    }

    public function test_dashboard_is_translated_and_right_to_left(): void
    {
        $this->actingAs($this->host)->withSession(['locale' => 'ar'])->get('/host/dashboard')->assertOk()->assertSee('مساحة المرشد')->assertSee('dir="rtl"', false);
        $this->withSession(['locale' => 'fr'])->get('/host/clients')->assertSee('Aucun client pour le moment');
    }
}
