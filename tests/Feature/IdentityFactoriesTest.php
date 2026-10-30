<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Enums\HostStatus;
use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Models\HostProfile;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\BlockedDate;
use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_states_set_the_role(): void
    {
        $this->assertSame(Role::Tourist, User::factory()->create()->role);
        $this->assertSame(Role::Tourist, User::factory()->tourist()->create()->role);
        $this->assertSame(Role::Admin, User::factory()->admin()->create()->role);
        $this->assertSame(Role::Host, User::factory()->host()->create()->role);
    }

    public function test_each_host_state_creates_a_profile_with_the_matching_status(): void
    {
        $cases = [
            [User::factory()->host(), HostStatus::Pending],
            [User::factory()->pendingHost(), HostStatus::Pending],
            [User::factory()->verifiedHost(), HostStatus::Verified],
            [User::factory()->rejectedHost(), HostStatus::Rejected],
            [User::factory()->suspendedHost(), HostStatus::Suspended],
        ];

        foreach ($cases as [$factory, $status]) {
            $host = $factory->create();

            $this->assertSame(Role::Host, $host->role);
            $this->assertSame($status, $host->hostProfile->status);
        }
    }

    public function test_tourists_and_admins_have_no_host_profile(): void
    {
        $this->assertNull(User::factory()->create()->hostProfile);
        $this->assertNull(User::factory()->admin()->create()->hostProfile);
    }

    public function test_building_a_host_does_not_create_stray_users(): void
    {
        User::factory()->verifiedHost()->create();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('host_profiles', 1);
    }

    public function test_a_host_profile_factory_brings_its_own_host_user(): void
    {
        $profile = HostProfile::factory()->verified()->create();

        $this->assertSame(Role::Host, $profile->user->role);
        $this->assertSame(HostStatus::Verified, $profile->status);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_languages_and_interests_are_saved_for_the_user(): void
    {
        $user = User::factory()->withLanguages(['fr', 'ar'])->withInterests(['food', 'crafts'])->create();

        $this->assertEqualsCanonicalizing(['fr', 'ar'], $user->languages()->pluck('language')->all());
        $this->assertEqualsCanonicalizing(['food', 'crafts'], $user->interests()->pluck('interest')->all());
    }

    public function test_a_rated_host_keeps_the_average(): void
    {
        $profile = HostProfile::factory()->verified()->rated(4.5)->create();

        $this->assertSame('4.50', $profile->fresh()->rating_avg);
    }

    public function test_experience_factory_respects_the_business_limits(): void
    {
        $experience = Experience::factory()->published()->create();

        $this->assertTrue($experience->published);
        $this->assertSame(HostStatus::Verified, $experience->host->status);
        $this->assertGreaterThanOrEqual(Experience::MIN_DURATION_HOURS, $experience->duration_hours);
        $this->assertLessThanOrEqual(Experience::MAX_DURATION_HOURS, $experience->duration_hours);
        $this->assertLessThanOrEqual(Experience::MAX_GROUP_SIZE, $experience->max_group_size);
        $this->assertMatchesRegularExpression('/^\d+\.\d{3}$/', $experience->fresh()->price_per_person);
    }

    public function test_experiences_start_unpublished(): void
    {
        $this->assertFalse(Experience::factory()->create()->fresh()->published);
    }

    public function test_availability_and_blocked_date_factories(): void
    {
        $slot = Availability::factory()->onWeekday(3)->between('10:00', '12:30')->create()->fresh();
        $blocked = BlockedDate::factory()->on('2026-12-24')->create()->fresh();

        $this->assertSame(3, $slot->weekday);
        $this->assertStringStartsWith('10:00', $slot->start_time);
        $this->assertStringStartsWith('12:30', $slot->end_time);
        $this->assertSame('2026-12-24', $blocked->blocked_on->toDateString());
    }

    public function test_verification_request_factory_states(): void
    {
        $pending = VerificationRequest::factory()->create();
        $verified = VerificationRequest::factory()->verified()->create();
        $rejected = VerificationRequest::factory()->rejected()->create();

        $this->assertNull($pending->decision);
        $this->assertStringStartsWith('id-documents/', $pending->id_document_path);

        $this->assertSame(HostStatus::Verified, $verified->decision);
        $this->assertSame(Role::Admin, $verified->decidedBy->role);
        $this->assertNotNull($verified->video_call_link);

        $this->assertSame(HostStatus::Rejected, $rejected->decision);
        $this->assertNotEmpty($rejected->reason);
    }
}
