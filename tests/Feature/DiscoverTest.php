<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_shows_filtered_verified_listings_alongside_social_discovery(): void
    {
        $this->withoutVite();
        $this->seed();

        $pendingHost = User::factory()->create(['name' => 'Hidden Pending Host']);
        $profile = HostProfile::create([
            'user_id' => $pendingHost->id,
            'status' => 'pending',
            'display_name' => 'Hidden Pending Host',
            'bio' => 'Not approved yet.',
            'city' => 'Tunis',
            'languages' => ['en'],
            'interests' => ['food'],
            'rating_avg' => '5.00',
            'rating_count' => 1,
        ]);
        Experience::create([
            'host_profile_id' => $profile->id,
            'title' => 'Not bookable yet',
            'category' => 'Food',
            'description' => 'A pending host listing.',
            'duration_hours' => 2,
            'price_per_person' => '20.000',
            'max_group_size' => 2,
            'photo_url' => 'https://example.test/experience.jpg',
            'published' => true,
        ]);

        $this->get('/?city=Tunis&language=en&interest=food&max_price=70&min_rating=4.8')
            ->assertOk()
            ->assertSee('Come for Tunisia.')
            ->assertSee('The medina, behind the blue doors')
            ->assertSee('A market morning with Amel')
            ->assertDontSee('Lunch like a Tunisian family')
            ->assertDontSee('Not bookable yet');
    }

    public function test_json_discovery_returns_ranked_matches_with_explanations(): void
    {
        $this->seed();

        $response = $this->getJson('/api/experiences?city=Tunis&language=en&interest=food&max_price=70');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.host', 'Amel Ben Youssef')
            ->assertJsonPath('data.0.why_this_host.0', 'Speaks English')
            ->assertJsonPath('data.0.why_this_host.1', 'Loves Food');
    }

    public function test_social_discovery_route_accepts_the_existing_locale_parameter(): void
    {
        $this->withoutVite();

        $this->get('/?lang=fr')
            ->assertOk()
            ->assertSee('Come for Tunisia.')
            ->assertSee('data-connect-local', false);
    }

    public function test_homepage_introduces_the_social_discovery_demo(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('Come for Tunisia.')
            ->assertSee('Stay for the people.')
            ->assertSee('Yasmine Ben Amor')
            ->assertSee('data-connect-local', false)
            ->assertSee('map-markers')
            ->assertSee('chat-dialog')
            ->assertSee('Sidi Bou Said');
    }

    public function test_homepage_includes_the_traveller_profile_mobile_navigation_and_geographic_map(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-view="profile"', false)
            ->assertSee('data-edit-profile', false)
            ->assertSee('data-edit-profile-form', false)
            ->assertSee('mobile-bottom-nav', false)
            ->assertSee('aria-label="Map of Tunisia based on Natural Earth geography', false)
            ->assertSee('viewBox="0 0 365 670"', false)
            ->assertSee('longitude')
            ->assertSee('Natural Earth · Public domain');
    }
}
