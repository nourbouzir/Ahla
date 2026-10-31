<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Services\AvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

class DesignTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    private function profile(User $user, array $extra = [])
    {
        return $this->actingAs($user)->put('/profile', ['name' => $user->name, 'preferred_language' => 'en'] + $extra);
    }

    public function test_an_uploaded_picture_becomes_a_small_square_and_replaces_the_old_one(): void
    {
        $user = User::factory()->tourist()->create();

        $this->profile($user, ['avatar' => UploadedFile::fake()->image('big.jpg', 1200, 600)])->assertSessionHasNoErrors();
        $first = $user->fresh()->avatar_path;
        Storage::disk('public')->assertExists($first);

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($first));
        $this->assertSame([256, 256], [$w, $h]);
        $this->assertLessThan(60_000, Storage::disk('public')->size($first));

        $this->profile($user->fresh(), ['avatar' => UploadedFile::fake()->image('new.png', 300, 300)]);
        $second = $user->fresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
        $this->assertStringContainsString('/storage/'.$second, $user->fresh()->avatarUrl());
    }

    public function test_a_picture_can_be_removed(): void
    {
        $user = User::factory()->tourist()->create();
        app(AvatarService::class)->store($user, UploadedFile::fake()->image('a.jpg', 200, 200));
        $path = $user->fresh()->avatar_path;

        $this->profile($user->fresh(), ['remove_avatar' => 1])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($user->fresh()->avatarUrl());
    }

    public function test_bad_pictures_are_refused(): void
    {
        $user = User::factory()->tourist()->create();

        $this->profile($user, ['avatar' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('avatar');
        $this->profile($user, ['avatar' => UploadedFile::fake()->create('big.png', 4000, 'image/png')])->assertSessionHasErrors('avatar');
        $this->profile($user, ['avatar' => UploadedFile::fake()->image('wide.png', 7000, 10)])->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_initials_and_colours_are_stable(): void
    {
        $user = User::factory()->make(['name' => 'amel ben youssef']);
        $this->assertSame('AB', $user->initials());
        $this->assertSame('نع', User::factory()->make(['name' => 'نور عمر'])->initials());
        $this->assertSame('?', User::factory()->make(['name' => ' '])->initials());
    }

    public function test_avatars_appear_on_cards_profiles_header_and_chat(): void
    {
        $host = $this->makeHost('Pictured Host');
        app(AvatarService::class)->store($host, UploadedFile::fake()->image('h.jpg', 400, 400));
        $url = $host->fresh()->avatarUrl();

        $this->get('/search')->assertSee($url, false);
        $this->get("/hosts/{$host->hostProfile->id}")->assertSee($url, false);

        // Picture-less users get coloured initials, and the decoration is hidden from screen readers.
        $plain = $this->makeHost('Plain Person');
        $this->get("/hosts/{$plain->hostProfile->id}")->assertSee('aria-hidden="true"', false)->assertSee('PP');

        $this->actingAs($host)->get('/')->assertSee($url, false);
    }

    public function test_home_page_has_cities_steps_and_both_calls_to_action(): void
    {
        $this->makeHost('Tunis Host', 'Tunis');
        $this->makeHost('Tunis Host 2', 'Tunis');
        $this->makeHost('Sfax Host', 'Sfax');

        $this->get('/')->assertOk()
            ->assertSee('How it works')->assertSee('Explore by city')
            ->assertSee('2 verified hosts')->assertSee('1 verified host')
            ->assertSee(route('search', ['city' => 'Tunis']), false)
            ->assertSee(route('register', ['role' => 'host']), false)
            ->assertSee('Become a host')->assertSee('Visiting Tunisia?');

        $this->get('/register?role=host')->assertSee('value="host" checked', false);
        $this->get('/register')->assertSee('value="tourist" checked', false);
    }

    public function test_home_page_is_translated_and_cities_are_localised(): void
    {
        $this->makeHost('Tunis Host', 'Tunis');

        $this->withSession(['locale' => 'ar'])->get('/')->assertSee('استكشف حسب المدينة')->assertSee('تونس')->assertSee('dir="rtl"', false);
        $this->withSession(['locale' => 'fr'])->get('/')->assertSee('Explorer par ville')->assertSee('1 hôte vérifié');
    }

    public function test_logo_favicon_and_theme_are_in_place(): void
    {
        $this->get('/')->assertSee('favicon.svg', false)->assertSee('theme-color', false);
        $this->assertFileExists(public_path('favicon.svg'));

        $css = file_get_contents(resource_path('css/app.css'));
        foreach (['--color-accent-600', '--color-accent-700', '--color-clay-700', '--color-gold-400', '--color-olive-700', '--color-wash:', '--font-sans'] as $token) {
            $this->assertStringContainsString($token, $css, "theme variable $token is defined once in app.css");
        }
        // Fonts come from npm packages bundled by Vite, never from another origin (the CSP allows only our own).
        $this->assertStringNotContainsString('fonts.googleapis', $css);
        $this->assertStringNotContainsString('http', preg_replace('/http:\/\/www\.w3\.org\/2000\/svg/', '', $css));
    }
}
