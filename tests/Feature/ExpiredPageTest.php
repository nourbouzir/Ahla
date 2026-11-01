<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** An expired form is explained and sent back, never shown as a bare "419 Page Expired". */
class ExpiredPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // The test client skips the real token check, so make the form routes fail the way a stale page does.
        Route::middleware('web')->post('/_expire', fn () => throw new TokenMismatchException)->name('expire.test');
    }

    public function test_a_stale_form_goes_back_with_a_friendly_message_and_keeps_typing_but_not_the_password(): void
    {
        $this->from('/login')->post('/_expire', ['email' => 'lea@example.test', 'password' => 'secret-pass'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('page')
            ->assertSessionHasInput('email', 'lea@example.test')
            ->assertSessionMissing('_old_input.password');

        $this->get('/login')->assertSee('Your page expired, so nothing was sent');
    }

    public function test_it_is_translated(): void
    {
        $this->withSession(['locale' => 'fr'])->from('/login')->post('/_expire');

        $this->get('/login')->assertSee('Votre page a expiré');
    }

    public function test_logging_out_with_an_old_page_just_goes_home(): void
    {
        Route::middleware('web')->post('/logout-test', fn () => throw new TokenMismatchException)->name('logout');

        $this->actingAs(User::factory()->tourist()->create())->post('/logout-test')->assertRedirect('/');
    }

    public function test_forms_with_one_time_tokens_are_never_cached_by_the_browser(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $url) {
            $this->get($url)->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        }

        $this->assertStringNotContainsString('no-store', (string) $this->get('/search')->assertOk()->headers->get('Cache-Control'));
    }

    public function test_idle_sessions_end_after_30_minutes_as_decided_in_adr_0004(): void
    {
        $this->assertSame(30, (int) config('session.lifetime'));
    }
}
