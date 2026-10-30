<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Enums\HostStatus;
use App\Modules\Auth\Enums\Role;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\BlockedDate;
use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Models\VerificationRequest;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdentitySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_identity_table_has_its_columns(): void
    {
        $expected = [
            'users' => ['role', 'phone', 'preferred_language'],
            'user_languages' => ['user_id', 'language'],
            'user_interests' => ['user_id', 'interest'],
            'host_profiles' => ['user_id', 'status', 'bio', 'city', 'lat', 'lng', 'photo_path', 'rating_avg'],
            'verification_requests' => [
                'id', 'host_id', 'id_document_path', 'video_call_at', 'video_call_link',
                'decision', 'reason', 'decided_by', 'decided_at',
            ],
            'experiences' => [
                'id', 'host_id', 'title', 'description', 'duration_hours', 'price_per_person',
                'max_group_size', 'meeting_point', 'meeting_lat', 'meeting_lng', 'published',
            ],
            'availability' => ['id', 'host_id', 'weekday', 'start_time', 'end_time'],
            'blocked_dates' => ['id', 'host_id', 'blocked_on'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "table $table is missing");
            $this->assertTrue(Schema::hasColumns($table, $columns), "table $table lacks one of: ".implode(', ', $columns));
        }
    }

    public function test_a_new_user_is_a_tourist_who_speaks_english_by_default(): void
    {
        $id = DB::table('users')->insertGetId(['name' => 'Ines', 'email' => 'ines@example.test', 'password' => 'x']);

        $user = User::findOrFail($id);

        $this->assertSame(Role::Tourist, $user->role);
        $this->assertSame('en', $user->preferred_language);
    }

    public function test_a_host_profile_starts_pending(): void
    {
        $id = User::factory()->create()->id;
        DB::table('host_profiles')->insert(['user_id' => $id]);

        $this->assertSame('pending', DB::table('host_profiles')->where('user_id', $id)->value('status'));
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = new User(['name' => 'Eve', 'email' => 'eve@example.test', 'password' => 'secret', 'role' => 'admin']);

        $this->assertNull($user->role, 'a request must never be able to choose its own role');
    }

    public function test_emails_are_unique(): void
    {
        User::factory()->create(['email' => 'same@example.test']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'same@example.test']);
    }

    public function test_a_language_cannot_be_listed_twice_for_one_user(): void
    {
        $user = User::factory()->withLanguages(['fr'])->create();

        $this->expectException(QueryException::class);
        $user->languages()->create(['language' => 'fr']);
    }

    public function test_a_host_cannot_block_the_same_date_twice(): void
    {
        $host = User::factory()->verifiedHost()->create();
        BlockedDate::factory()->for($host->hostProfile, 'host')->on('2026-12-24')->create();

        $this->expectException(QueryException::class);
        BlockedDate::factory()->for($host->hostProfile, 'host')->on('2026-12-24')->create();
    }

    public function test_only_users_with_a_host_profile_can_own_host_data(): void
    {
        $tourist = User::factory()->create();

        $this->expectException(QueryException::class);
        Experience::factory()->create(['host_id' => $tourist->id]);
    }

    public function test_deleting_a_host_removes_everything_that_belongs_to_them(): void
    {
        $host = User::factory()->verifiedHost()->withLanguages(['fr'])->withInterests(['food'])->create();
        Experience::factory()->for($host->hostProfile, 'host')->create();
        Availability::factory()->for($host->hostProfile, 'host')->create();
        BlockedDate::factory()->for($host->hostProfile, 'host')->create();
        VerificationRequest::factory()->for($host->hostProfile, 'host')->create();

        $host->delete();

        foreach (['host_profiles', 'user_languages', 'user_interests', 'experiences', 'availability', 'blocked_dates', 'verification_requests'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "$table still has rows of the deleted host");
        }
    }

    public function test_prices_keep_three_decimals(): void
    {
        $experience = Experience::factory()->create(['price_per_person' => '45.5']);

        $this->assertSame('45.500', $experience->fresh()->price_per_person);
    }

    public function test_the_enums_hold_the_values_of_adr_0004(): void
    {
        $this->assertSame(['pending', 'verified', 'rejected', 'suspended'], array_column(HostStatus::cases(), 'value'));
        $this->assertSame(['tourist', 'host', 'admin'], array_column(Role::cases(), 'value'));
    }
}
