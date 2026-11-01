<?php

namespace Tests\Feature;

use App\Contracts\Translation\Translator;
use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use App\Modules\Booking\Models\Booking;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Services\LibreTranslateTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

/** Switching accounts, signing up, the activity timeline and chat without a translation service. */
class UsabilityTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function actions(string $entity): array
    {
        return AuditLog::where('entity', $entity)->orderBy('id')->pluck('new_status')->all();
    }

    public function test_login_and_signup_pages_stay_open_while_logged_in_and_say_who_is_logged_in(): void
    {
        $tourist = User::factory()->tourist()->create(['name' => 'Tess Tourist']);

        $this->actingAs($tourist)->get('/login')->assertOk()->assertSee('You are logged in as Tess Tourist (Tourist)')->assertSee('Log out');
        $this->get('/register')->assertOk()->assertSee('Tess Tourist');
        // Password reset is still only for people who are logged out.
        $this->get('/forgot-password')->assertRedirect('/');
    }

    public function test_logging_in_as_someone_else_switches_account_without_logging_out(): void
    {
        $tourist = User::factory()->tourist()->create();
        $guide = User::factory()->verifiedHost()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($tourist)->post('/login', ['email' => $guide->email, 'password' => 'password'])->assertRedirect('/host/dashboard');
        $this->assertAuthenticatedAs($guide);
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk();
    }

    public function test_signing_up_while_logged_in_leaves_the_old_account_and_enters_the_new_one(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post('/register', ['name' => 'Fresh Face', 'email' => 'fresh@test.dev', 'role' => 'tourist', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
            ->assertRedirect('/profile');

        $this->assertSame('fresh@test.dev', auth()->user()->email);
        $this->assertSame('tourist', auth()->user()->role->value);
        $this->get('/admin')->assertForbidden();
    }

    public function test_demo_accounts_log_in_with_one_click_and_new_accounts_can_be_created_repeatedly(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $html = $this->get('/login')->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'name="password" value="password"'), 'each demo account is its own form');

        foreach (range(1, 3) as $i) {
            $this->post('/register', ['name' => "Person $i", 'email' => "person$i@test.dev", 'role' => $i === 2 ? 'host' : 'tourist', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
                ->assertSessionHasNoErrors();
            auth()->logout();
        }

        $this->assertSame(3, User::where('email', 'like', 'person%@test.dev')->count());
        $this->post('/login', ['email' => 'person2@test.dev', 'password' => 'secret-pass-1'])->assertRedirect('/host/dashboard');
    }

    public function test_every_account_action_lands_in_the_admin_timeline(): void
    {
        $this->post('/register', ['name' => 'Timeline Tim', 'email' => 'tim@test.dev', 'role' => 'tourist', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1']);
        $this->post('/logout');
        $this->post('/login', ['email' => 'tim@test.dev', 'password' => 'wrong-password']);
        $this->post('/login', ['email' => 'ghost@test.dev', 'password' => 'whatever']);
        $this->post('/login', ['email' => 'tim@test.dev', 'password' => 'secret-pass-1']);

        $this->assertSame(['registered', 'logged_in', 'logged_out', 'login_failed', 'login_failed', 'logged_in'], $this->actions('session'));
        $failed = AuditLog::where('new_status', 'login_failed')->get();
        $this->assertSame(User::where('email', 'tim@test.dev')->value('id'), $failed[0]->entity_id, 'a failed attempt on a real account points at it');
        $this->assertSame(0, $failed[1]->entity_id, 'an unknown e-mail is still recorded');
        $this->assertStringNotContainsString('wrong-password', json_encode(AuditLog::all()), 'passwords are never logged');
        $this->assertStringNotContainsString('ghost@test.dev', json_encode(AuditLog::all()), 'NFR-12: a typed e-mail is not stored');
        $this->assertNull(AuditLog::where('new_status', 'logged_in')->first()->reason, 'NFR-12: successful logins store no IP');
    }

    public function test_the_refund_policy_is_shown_before_the_tourist_requests_a_booking(): void
    {
        $host = $this->makeHost('Policy Guide');
        $experience = $host->hostProfile->experiences()->first();

        $this->actingAs(User::factory()->tourist()->create())->get("/experiences/{$experience->id}/book")
            ->assertOk()->assertSee('48 h or more before the start', false)->assertSee('50% refund', false);
    }

    public function test_audit_rows_are_append_only(): void
    {
        $row = AuditLog::factory()->create();

        $this->expectException(\LogicException::class);
        $row->update(['reason' => 'edited']);
    }

    public function test_audit_rows_cannot_be_deleted(): void
    {
        $row = AuditLog::factory()->create();

        $this->expectException(\LogicException::class);
        $row->delete();
    }

    public function test_profile_experience_and_availability_changes_are_logged(): void
    {
        $guide = $this->makeHost('Logged Guide');
        $experience = $guide->hostProfile->experiences()->first();
        $this->actingAs($guide);

        $this->put('/profile', ['name' => 'Logged Guide', 'preferred_language' => 'en', 'display_name' => 'Logged Guide', 'bio' => 'b', 'city' => 'Tunis'])->assertSessionHasNoErrors();
        $this->post("/host/experiences/{$experience->id}/toggle");
        $this->post("/host/experiences/{$experience->id}/toggle");
        $this->post('/host/availability/slots', ['day_of_week' => 1, 'starts_at' => '23:00', 'ends_at' => '23:30']);
        $this->post('/host/availability/blocked', ['date' => now()->addDays(9)->toDateString()]);

        $this->assertSame(['updated'], $this->actions('profile'));
        $this->assertSame(['unpublished', 'published'], $this->actions('experience'));
        $this->assertSame(['hours_added', 'day_off_added'], $this->actions('availability'));
        $this->assertSame($guide->id, AuditLog::where('entity', 'experience')->first()->actor_id);
    }

    public function test_sending_a_message_is_logged_without_its_text(): void
    {
        $booking = Booking::factory()->create();
        $this->actingAs($booking->tourist)->post("/bookings/{$booking->id}/messages", ['body' => 'my secret plan: 12345']);

        $row = AuditLog::where('entity', 'message')->first();
        $this->assertSame('sent', $row->new_status);
        $this->assertSame($booking->tourist_id, $row->actor_id);
        $this->assertStringNotContainsString('secret', json_encode(AuditLog::all()));
    }

    public function test_the_timeline_reads_like_sentences_and_can_be_filtered(): void
    {
        $this->post('/register', ['name' => 'Reader Rita', 'email' => 'rita@test.dev', 'role' => 'tourist', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/audit')->assertOk()->assertSee('Reader Rita')->assertSee('created an account')->assertSee('logged in');
        $this->get('/admin/audit?entity=profile')->assertDontSee('created an account');
        $this->get('/admin')->assertSee('created an account');
        $this->withSession(['locale' => 'fr'])->get('/admin/audit?entity=session')->assertSee('a créé un compte');
    }

    public function test_without_a_translation_service_the_original_is_shown_with_a_clear_calm_status(): void
    {
        $this->app->singleton(Translator::class, LibreTranslateTranslator::class);
        config(['ahla.translation.url' => '']);
        $booking = Booking::factory()->create();
        $booking->tourist->update(['preferred_language' => 'fr']);
        $booking->host->update(['preferred_language' => 'en']);

        $this->actingAs($booking->tourist)->post("/bookings/{$booking->id}/messages", ['body' => 'Bonjour !'])->assertRedirect();

        $this->assertNull(Message::first()->translated_language, 'nothing to translate with, so nothing is queued');
        $html = $this->actingAs($booking->host)->get("/bookings/{$booking->id}/messages")->assertOk()->assertSee('Bonjour !')->getContent();
        // NFR-20 / SDD 6.3: the reader is told, calmly, that the original is shown.
        $this->assertMatchesRegularExpression('/<p class="mt-1 text-xs[^>]*>\s*Translation unavailable/', $html);

        // Once a service is configured the same pair is translated again.
        config(['ahla.translation.url' => 'https://translate.test']);
        $this->actingAs($booking->tourist)->post("/bookings/{$booking->id}/messages", ['body' => 'Merci']);
        $this->assertSame('en', Message::latest('id')->first()->translated_language);
    }
}
