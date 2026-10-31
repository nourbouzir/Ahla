<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tourist_can_register_and_is_logged_in(): void
    {
        $this->post('/register', [
            'name' => 'Lea Martin', 'email' => 'lea@example.test', 'role' => 'tourist',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/profile');

        $user = User::where('email', 'lea@example.test')->firstOrFail();
        $this->assertSame(Role::Tourist, $user->role);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame('secret-pass-1', $user->password);
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->post('/register', [
            'name' => 'Eve', 'email' => 'eve@example.test', 'role' => 'admin',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'eve@example.test']);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->post('/register', [
            'name' => '', 'email' => 'taken@example.test', 'role' => 'host',
            'password' => 'short', 'password_confirmation' => 'other',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_login_logout_flow(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass-1'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_and_unknown_email_give_the_same_message(): void
    {
        $user = User::factory()->create();

        $message = __('auth::messages.failed');

        $this->post('/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertSessionHasErrors(['email' => $message]);
        $this->post('/login', ['email' => 'ghost@example.test', 'password' => 'nope'])
            ->assertSessionHasErrors(['email' => $message]);
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // Even the right password is refused while throttled.
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        // Unknown emails get the same answer and no mail.
        $this->post('/forgot-password', ['email' => 'ghost@example.test'])->assertSessionHas('status');

        $token = Password::createToken($user);
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect('/login');

        $this->post('/login', ['email' => $user->email, 'password' => 'brand-new-pass']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_with_a_bad_token_fails(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'bad', 'email' => $user->email,
            'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('email');
    }

    public function test_pages_render_in_each_language_with_direction(): void
    {
        $this->get('/login')->assertOk()->assertSee('dir="ltr"', false);

        $this->post('/locale/ar')->assertRedirect();
        $this->get('/login')->assertOk()->assertSee('dir="rtl"', false)->assertSee('lang="ar"', false);

        $this->post('/locale/fr');
        $this->get('/')->assertOk()->assertSee('Passez quelques heures', false);

        $this->post('/locale/de')->assertNotFound();
    }

    public function test_choosing_a_language_while_logged_in_is_saved(): void
    {
        $user = User::factory()->create(['preferred_language' => 'en']);

        $this->actingAs($user)->post('/locale/fr');

        $this->assertSame('fr', $user->fresh()->preferred_language);
    }
}
