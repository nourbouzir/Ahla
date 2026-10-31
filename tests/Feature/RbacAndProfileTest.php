<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Support\Permissions;
use App\Modules\Booking\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RbacAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_every_gate_ability_follows_the_matrix(): void
    {
        $users = [
            Role::Tourist->value => User::factory()->tourist()->create(),
            Role::Host->value => User::factory()->verifiedHost()->create(),
            Role::Admin->value => User::factory()->admin()->create(),
        ];

        foreach (Permissions::MATRIX as $ability => $roles) {
            foreach ($users as $roleName => $user) {
                $expected = in_array(Role::from($roleName), $roles, true);
                $this->assertSame($expected, Gate::forUser($user)->allows($ability), "$roleName / $ability");
            }
        }
    }

    public function test_role_middleware_blocks_other_roles(): void
    {
        Route::middleware(['web', 'auth', 'role:admin'])->get('/_admin-only', fn () => 'ok');

        $this->get('/_admin-only')->assertRedirect('/login');
        $this->actingAs(User::factory()->tourist()->create())->get('/_admin-only')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/_admin-only')->assertOk();
    }

    public function test_tourist_updates_profile_with_languages_and_interests(): void
    {
        $user = User::factory()->tourist()->create();

        $this->actingAs($user)->put('/profile', [
            'name' => 'New Name', 'phone' => '+216 20 123 456', 'preferred_language' => 'fr',
            'languages' => ['en', 'fr'], 'interests' => ['food', 'art'],
        ])->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('fr', $user->preferred_language);
        $this->assertEqualsCanonicalizing(['en', 'fr'], $user->languages()->pluck('language')->all());
        $this->assertEqualsCanonicalizing(['food', 'art'], $user->interests()->pluck('interest')->all());
        $this->get('/profile')->assertOk()->assertSee('New Name');
    }

    public function test_profile_rejects_unknown_language_and_bad_phone(): void
    {
        $this->actingAs(User::factory()->tourist()->create())->put('/profile', [
            'name' => 'X', 'phone' => 'call me', 'preferred_language' => 'xx', 'languages' => ['zz'],
        ])->assertSessionHasErrors(['phone', 'preferred_language', 'languages.0']);
    }

    public function test_host_profile_is_created_pending_and_status_cannot_be_forged(): void
    {
        $host = User::factory()->create(['role' => Role::Host]);

        $this->actingAs($host)->put('/profile', [
            'name' => 'Hosty', 'preferred_language' => 'en', 'display_name' => 'Hosty H', 'bio' => 'Hello',
            'city' => 'Tunis', 'status' => 'verified',
        ])->assertRedirect('/profile');

        $profile = $host->fresh()->hostProfile;
        $this->assertSame('pending', $profile->status->value);
        $this->assertSame('Tunis', $profile->city);
        $this->assertNotNull($profile->latitude);
    }

    public function test_host_must_fill_the_host_fields(): void
    {
        $host = User::factory()->create(['role' => Role::Host]);

        $this->actingAs($host)->put('/profile', ['name' => 'H', 'preferred_language' => 'en'])
            ->assertSessionHasErrors(['display_name', 'bio', 'city']);
    }

    public function test_contact_details_are_hidden_until_the_booking_is_confirmed(): void
    {
        $booking = Booking::factory()->create();
        $tourist = $booking->tourist;
        $host = $booking->host;
        $host->update(['phone' => '+216 99 999 999']);
        $host->hostProfile->forceFill(['status' => 'verified'])->save();

        // Requested: the host's profile is visible (verified) but phone and email are not.
        $this->actingAs($tourist)->get("/profiles/{$host->id}")->assertOk()
            ->assertDontSee('+216 99 999 999')->assertDontSee($host->email);

        $booking->forceFill(['status' => 'confirmed'])->save();
        $this->actingAs($tourist)->get("/profiles/{$host->id}")->assertSee('+216 99 999 999')->assertSee($host->email);

        // Owner and admin always see their own / any contact.
        $this->actingAs($host)->get("/profiles/{$host->id}")->assertSee('+216 99 999 999');
        $this->actingAs(User::factory()->admin()->create())->get("/profiles/{$host->id}")->assertSee('+216 99 999 999');
    }

    public function test_strangers_cannot_open_a_tourist_or_an_unverified_host_profile(): void
    {
        $tourist = User::factory()->tourist()->create();
        $pendingHost = User::factory()->pendingHost()->create();
        $other = User::factory()->tourist()->create();

        $this->actingAs($other)->get("/profiles/{$tourist->id}")->assertForbidden();
        $this->actingAs($other)->get("/profiles/{$pendingHost->id}")->assertForbidden();
        $this->actingAs($tourist)->get("/profiles/{$tourist->id}")->assertOk();
    }
}
