<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Admin\Services\AdminStats;
use App\Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->admin()->create(['name' => 'Root Admin']);
        config(['ahla.commission_rate' => 0.10]);
    }

    private function paid(User $guide, string $amount, string $refunded = '0.000', ?string $capturedAt = null, string $status = 'completed'): Booking
    {
        $booking = Booking::factory()->create([
            'host_id' => $guide->id, 'experience_id' => $guide->hostProfile->experiences()->first()->id,
            'displayed_price' => $amount, 'status' => $status,
        ]);
        $payment = new Payment(['booking_id' => $booking->id, 'amount' => $amount, 'currency' => 'TND']);
        $payment->forceFill([
            'gateway_ref' => 'r'.$booking->id, 'status' => (float) $refunded > 0 ? PaymentStatus::Refunded : PaymentStatus::Captured,
            'refunded_amount' => $refunded, 'captured_at' => $capturedAt ?? now(), 'authorised_at' => now(),
        ])->save();

        return $booking;
    }

    public function test_only_admins_reach_the_admin_space(): void
    {
        $guide = $this->makeHost('A Guide');
        $tourist = User::factory()->tourist()->create();
        $urls = ['/admin', '/admin/users', '/admin/guides', '/admin/revenue', '/admin/revenue.csv', '/admin/bookings', '/admin/audit', '/admin/verifications'];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect('/login');
            $this->actingAs($tourist)->get($url)->assertForbidden();
            $this->actingAs($guide)->get($url)->assertForbidden();
            $this->actingAs($this->admin)->get($url)->assertOk();
            auth()->logout();
        }
    }

    public function test_demo_credentials_exist_and_work_and_are_shown_on_the_login_page(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $this->get('/login')->assertSee('Demo accounts')->assertSee('admin@ahla.test')->assertSee('amel@ahla.test')->assertSee('tourist@ahla.test');
        $this->post('/login', ['email' => 'admin@ahla.test', 'password' => 'password'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk()->assertSee('Platform overview');

        config(['ahla.show_demo_logins' => false]);
        auth()->logout();
        $this->get('/login')->assertDontSee('Demo accounts');
    }

    public function test_overview_numbers_follow_the_money_rules(): void
    {
        $a = $this->makeHost('Guide A');
        $b = $this->makeHost('Guide B', 'Sousse');
        $this->paid($a, '100.000');
        $this->paid($a, '60.000', '60.000');          // fully refunded
        $this->paid($b, '200.000', '50.000');         // partly refunded
        $reserved = new Payment(['booking_id' => Booking::factory()->create()->id, 'amount' => '999.000', 'currency' => 'TND']);
        $reserved->forceFill(['status' => PaymentStatus::Authorised])->save();   // only reserved: ignored

        $overview = app(AdminStats::class)->overview();

        $this->assertSame('360.000', $overview['money']['paid']);
        $this->assertSame('110.000', $overview['money']['refunded']);
        $this->assertSame('250.000', $overview['money']['volume']);
        $this->assertSame('25.000', $overview['money']['revenue'], '10% of the net volume, indicative only');

        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('250 TND')->assertSee('Indicative revenue 25 TND at 10%');
    }

    public function test_attention_list_flags_what_needs_a_decision(): void
    {
        $guide = $this->makeHost('Needs Help');
        $this->makeHost('Waiting Guide', status: HostStatus::Pending);
        VerificationRequest::factory()->create(['host_profile_id' => User::where('name', 'Waiting Guide')->first()->hostProfile->id, 'status' => 'submitted']);
        $cancelled = $this->paid($guide, '50.000', status: 'cancelled');   // captured but cancelled: refund still pending
        Booking::factory()->create(['host_id' => $guide->id, 'experience_id' => $guide->hostProfile->experiences()->first()->id, 'status' => 'requested', 'expires_at' => now()->addHour()]);

        $this->actingAs($this->admin);
        $attention = app(AdminStats::class)->attention();

        $this->assertSame(1, $attention['applications']);
        $this->assertSame(1, $attention['refunds_pending']);
        $this->assertSame(1, $attention['expiring']);
        $this->get('/admin')->assertSee('Needs attention')->assertSee('Guide applications waiting for a decision');
        $this->assertNotNull($cancelled);
    }

    public function test_users_page_searches_filters_and_shows_details(): void
    {
        $guide = $this->makeHost('Searchable Guide');
        $tourist = User::factory()->tourist()->create(['name' => 'Tess Tourist', 'email' => 'tess@example.test']);
        $locked = User::factory()->tourist()->create(['name' => 'Locked Larry']);
        $locked->forceFill(['disabled_at' => now(), 'disabled_reason' => 'spam'])->save();
        $this->actingAs($this->admin);

        $this->get('/admin/users')->assertOk()->assertSee('Tess Tourist')->assertSee('Searchable Guide');
        $this->get('/admin/users?q=tess@example')->assertSee('Tess Tourist')->assertDontSee('Searchable Guide');
        $this->get('/admin/users?role=host')->assertSee('Searchable Guide')->assertDontSee('Tess Tourist');
        $this->get('/admin/users?state=disabled')->assertSee('Locked Larry')->assertDontSee('Tess Tourist');
        $this->get('/admin/users?q=%25')->assertSee('No user matches', false);

        $this->get("/admin/users/{$tourist->id}")->assertOk()->assertSee('Tess Tourist')->assertSee('Disable account');
        $this->get("/admin/users/{$guide->id}")->assertOk()->assertSee('Public page');
    }

    public function test_disabling_an_account_logs_it_out_everywhere_and_is_audited(): void
    {
        $tourist = User::factory()->tourist()->create();
        DB::table('sessions')->insert(['id' => 's1', 'user_id' => $tourist->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->admin);

        $this->post("/admin/users/{$tourist->id}/disable", [])->assertSessionHasErrors('reason');
        $this->post("/admin/users/{$tourist->id}/disable", ['reason' => 'Fraud report'])->assertSessionHas('status');

        $this->assertNotNull($tourist->fresh()->disabled_at);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $tourist->id)->count());
        $log = AuditLog::where('entity', 'user')->latest('id')->first();
        $this->assertSame(['active', 'disabled', $this->admin->id, 'Fraud report'], [$log->old_status, $log->new_status, $log->actor_id, $log->reason]);

        // They cannot log in again ...
        auth()->logout();
        $this->post('/login', ['email' => $tourist->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // ... and a session that was already open is closed on the next request.
        $this->actingAs($tourist->fresh())->get('/profile')->assertRedirect('/login');
        $this->assertGuest();

        // Re-enabled, they are back.
        $this->actingAs($this->admin)->post("/admin/users/{$tourist->id}/enable")->assertSessionHas('status');
        auth()->logout();
        $this->post('/login', ['email' => $tourist->email, 'password' => 'password'])->assertRedirect('/');
    }

    public function test_admins_cannot_be_disabled_from_the_panel(): void
    {
        $other = User::factory()->admin()->create();

        $this->actingAs($this->admin)->post("/admin/users/{$other->id}/disable", ['reason' => 'x'])->assertSessionHasErrors('reason');
        $this->assertNull($other->fresh()->disabled_at);
    }

    public function test_guides_page_shows_each_guides_numbers_and_filters(): void
    {
        $a = $this->makeHost('Reliable Rita', 'Tunis', rating: 4.9);
        $b = $this->makeHost('Cancelling Carl', 'Sousse', status: HostStatus::Suspended);
        $this->paid($a, '100.000');
        $this->paid($b, '40.000', status: 'cancelled');
        $this->paid($b, '40.000', status: 'completed');

        $this->actingAs($this->admin)->get('/admin/guides')->assertOk()->assertSeeInOrder(['Reliable Rita', 'Cancelling Carl'])->assertSee('100 TND')->assertSee('50%');
        $this->get('/admin/guides?status=suspended')->assertSee('Cancelling Carl')->assertDontSee('Reliable Rita');
        $this->get('/admin/guides?city=Tunis')->assertSee('Reliable Rita')->assertDontSee('Cancelling Carl');
        $this->get('/admin/guides?q=carl')->assertSee('Cancelling Carl')->assertDontSee('Reliable Rita');

        // Suspend and reinstate work from this page through the audited route.
        $this->post("/admin/hosts/{$a->hostProfile->id}/status", ['status' => 'suspended', 'reason' => 'Complaint'])->assertSessionHas('status');
        $this->assertSame(HostStatus::Suspended, $a->hostProfile->fresh()->status);
    }

    public function test_revenue_page_months_breakdown_and_csv(): void
    {
        $a = $this->makeHost('Top Guide', 'Tunis');
        $b = $this->makeHost('Small Guide', 'Sousse');
        $this->paid($a, '300.000', '0.000', now()->toDateString());
        $this->paid($a, '100.000', '40.000', now()->startOfMonth()->subDay()->toDateString());
        $this->paid($b, '50.000', '0.000', now()->toDateString());

        $months = app(AdminStats::class)->months(12);
        $this->assertSame('350.000', end($months)['volume']);
        $this->assertSame('35.000', end($months)['revenue']);
        $this->assertSame('60.000', $months[10]['volume']);

        $breakdown = app(AdminStats::class)->breakdown();
        $this->assertSame(['Top Guide', 'Small Guide'], $breakdown['guides']->map(fn ($g) => $g['guide']->name)->all());
        $this->assertSame('Tunis', $breakdown['cities']->first()['name']);

        $this->actingAs($this->admin)->get('/admin/revenue')->assertOk()->assertSee('Top guides')->assertSee('Top Guide')->assertSee('410 TND');
        $csv = $this->get('/admin/revenue.csv')->assertOk()->getContent();
        $this->assertStringContainsString('volume_tnd', $csv);
        $this->assertSame(13, substr_count($csv, "\n"));
    }

    public function test_commission_rate_is_configurable_and_shown_as_indicative(): void
    {
        $guide = $this->makeHost('Rate Guide');
        $this->paid($guide, '1000.000');
        config(['ahla.commission_rate' => 0.15]);

        $this->assertSame('150.000', app(AdminStats::class)->overview()['money']['revenue']);
        $this->actingAs($this->admin)->get('/admin/revenue')->assertSee('planning figure');
    }

    public function test_admin_space_is_translated_and_right_to_left(): void
    {
        $this->actingAs($this->admin)->withSession(['locale' => 'ar'])->get('/admin')->assertOk()->assertSee('نظرة عامة على المنصة')->assertSee('dir="rtl"', false);
        $this->withSession(['locale' => 'fr'])->get('/admin/revenue')->assertSee('Revenus')->assertSee('Meilleurs guides');
    }
}
