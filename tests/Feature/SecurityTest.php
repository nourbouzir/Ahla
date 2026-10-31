<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Support\Permissions;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every route must be classified here. A new route that is not listed makes
     * this test fail, so nobody can ship an endpoint without deciding who may use it.
     *
     *  public  - anyone, including guests
     *  guest   - only people who are NOT logged in (login, register ...)
     *  auth    - any logged-in user; the controller checks ownership / participation
     *            through a policy or a query scoped to the user
     *  can:*   - listed by Gate ability and compared with Permissions::MATRIX
     */
    private const ALLOWED_PUBLIC = [
        'home', 'locale.update', 'search', 'hosts.show', 'api.search', 'payments.webhook', 'api.health',
    ];

    private const ALLOWED_AUTH_ONLY = [
        'logout', 'profile.edit', 'profile.update', 'profiles.show',
        'bookings.show', 'bookings.cancel', 'messages.show', 'messages.poll', 'messages.store',
        'notifications.index', 'notifications.open', 'notifications.read-all', 'notifications.preferences',
    ];

    /** @return array<string, LaravelRoute> named application routes (framework routes excluded) */
    private function appRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            $uri = $route->uri();

            // Laravel's own health page and storage helper are not ours to classify.
            if (in_array($uri, ['up', 'storage/{path}'], true) || str_starts_with($uri, '_ignition') || str_starts_with($uri, 'sanctum')) {
                continue;
            }

            $this->assertNotNull($name, "Route [{$route->methods()[0]} /{$uri}] has no name; give it one so it can be classified.");
            $routes[$name] = $route;
        }

        return $routes;
    }

    /** @return list<string> */
    private function middlewareOf(LaravelRoute $route): array
    {
        return $route->gatherMiddleware();
    }

    public function test_every_route_is_classified_and_protected_accordingly(): void
    {
        foreach ($this->appRoutes() as $name => $route) {
            $middleware = $this->middlewareOf($route);
            $abilities = array_map(fn ($m) => substr($m, 4), array_filter($middleware, fn ($m) => is_string($m) && str_starts_with($m, 'can:')));
            $roles = array_filter($middleware, fn ($m) => is_string($m) && str_starts_with($m, 'role:'));
            $hasAuth = in_array('auth', $middleware, true);
            $isGuestOnly = in_array('guest', $middleware, true);

            if ($abilities || $roles) {
                $this->assertTrue($hasAuth, "[$name] uses can:/role: but not auth.");
                foreach ($abilities as $ability) {
                    $this->assertArrayHasKey($ability, Permissions::MATRIX, "[$name] uses unknown ability [$ability].");
                }
            } elseif ($isGuestOnly) {
                $this->assertFalse($hasAuth, "[$name] is both guest and auth.");
            } elseif ($hasAuth) {
                $this->assertContains($name, self::ALLOWED_AUTH_ONLY, "[$name] is auth-only without an ability: add it to the reviewed list or add a can: rule.");
            } else {
                $this->assertContains($name, self::ALLOWED_PUBLIC, "[$name] is reachable by guests: add it to the reviewed public list or protect it.");
            }
        }
    }

    public function test_the_reviewed_lists_contain_no_stale_names(): void
    {
        $names = array_keys($this->appRoutes());

        foreach (array_merge(self::ALLOWED_PUBLIC, self::ALLOWED_AUTH_ONLY) as $name) {
            $this->assertContains($name, $names, "Reviewed list mentions [$name] but no such route exists any more.");
        }
    }

    public function test_every_matrix_ability_is_used_by_a_route_or_a_policy(): void
    {
        $used = [];
        foreach ($this->appRoutes() as $route) {
            foreach ($this->middlewareOf($route) as $m) {
                if (is_string($m) && str_starts_with($m, 'can:')) {
                    $used[] = substr($m, 4);
                }
            }
        }

        // Abilities enforced elsewhere (views, services) are listed so the test stays honest.
        $elsewhere = ['profile.edit', 'booking.view', 'booking.cancel', 'message.send', 'admin.audit', 'admin.host-status', 'availability.manage'];

        foreach (array_keys(Permissions::MATRIX) as $ability) {
            $this->assertTrue(in_array($ability, $used, true) || in_array($ability, $elsewhere, true), "Ability [$ability] is defined but never enforced.");
        }
    }

    public function test_guests_are_turned_away_from_every_auth_route(): void
    {
        foreach ($this->appRoutes() as $name => $route) {
            if (! in_array('auth', $this->middlewareOf($route), true)) {
                continue;
            }

            // Parameters get a dummy value: the auth middleware runs before route-model binding.
            $uri = preg_replace('/\{[^}]+\}/', '1', $route->uri());
            $response = $this->call($route->methods()[0], '/'.$uri);

            $this->assertContains($response->getStatusCode(), [302, 401, 403], "[$name] answered a guest with {$response->getStatusCode()}");
            if ($response->getStatusCode() === 302) {
                $this->assertStringContainsString('/login', (string) $response->headers->get('Location'), "[$name] redirects a guest somewhere other than the login page");
            }
        }
    }

    public function test_security_headers_are_set(): void
    {
        $this->withoutVite();
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
    }

    public function test_csrf_protection_is_active_for_state_changing_forms(): void
    {
        // Laravel skips the token check while unit tests run, so assert it is part of the web stack
        // and that every route that changes state goes through that stack.
        $this->assertContains(ValidateCsrfToken::class, app(Kernel::class)->getMiddlewareGroups()['web']);

        foreach ($this->appRoutes() as $name => $route) {
            if (array_diff($route->methods(), ['GET', 'HEAD']) !== [] && ! str_starts_with($route->uri(), 'api/')) {
                $this->assertContains('web', $this->middlewareOf($route), "[$name] changes state outside the web (CSRF) stack");
            }
        }
    }

    public function test_session_cookie_settings(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertLessThanOrEqual(120, (int) config('session.lifetime'), 'sessions should be short');
    }

    public function test_no_card_data_is_stored_anywhere(): void
    {
        foreach (Schema::getTables() as $table) {
            foreach (Schema::getColumnListing($table['name']) as $column) {
                $this->assertDoesNotMatchRegularExpression('/(^|_)(card|pan|cvv|cvc|expiry|iban)(_|$)/i', $column, "{$table['name']}.{$column} looks like card data");
            }
        }

        // Only the provider's reference is kept on a payment.
        $this->assertTrue(Schema::hasColumn('payments', 'gateway_ref'));
    }

    public function test_id_evidence_is_never_on_a_public_disk_or_url(): void
    {
        $this->assertSame('local', config('filesystems.default'));
        $this->assertFalse(config('filesystems.disks.local.serve') ?? false, 'the private disk must not be served');
        $this->assertStringContainsString('private', config('filesystems.disks.local.root'));
        $this->assertArrayNotHasKey('storage/app/private', config('filesystems.links'));
    }

    public function test_debug_is_off_in_the_production_template_notes(): void
    {
        // .env.example is a development template; the production requirement is documented and enforced at deploy time.
        $this->assertStringContainsString('APP_DEBUG=false', file_get_contents(base_path('.env.example')));
    }
}
