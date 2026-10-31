<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bulk data for performance tests (issue #38). Development only, not part of the
 * normal seed:   php artisan db:seed --class=LoadTestSeeder
 * Creates AHLA_LOAD_HOSTS (default 400) verified hosts spread over all cities,
 * languages and interests, each with 1-2 published experiences and weekly hours,
 * plus 50 tourists. Uses plain inserts in chunks so it runs in a few seconds.
 */
class LoadTestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Refusing to load test data in production.');

            return;
        }

        $count = (int) (getenv('AHLA_LOAD_HOSTS') ?: 400);
        $cities = array_keys(config('ahla.cities'));
        $languages = config('ahla.languages');
        $interests = config('ahla.interests');

        DB::transaction(function () use ($count, $cities, $languages, $interests) {
            for ($i = 1; $i <= $count; $i++) {
                $city = $cities[$i % count($cities)];
                [$lat, $lng] = config('ahla.cities')[$city];

                $user = User::query()->create([
                    'name' => "Load Host $i", 'email' => "load-host-$i@load.test", 'password' => 'password',
                ]);
                $user->forceFill(['role' => Role::Host, 'preferred_language' => $languages[$i % 3]])->save();

                $user->languages()->createMany(collect([$languages[$i % 3], $languages[($i + 1) % 3]])->unique()->map(fn ($l) => ['language' => $l])->all());
                $user->interests()->createMany(collect([$interests[$i % 6], $interests[($i + 2) % 6]])->unique()->map(fn ($x) => ['interest' => $x])->all());

                $profile = new HostProfile(['user_id' => $user->id]);
                $profile->forceFill([
                    'display_name' => "Load Host $i", 'bio' => "Host number $i, showing guests around $city.", 'city' => $city,
                    'latitude' => $lat, 'longitude' => $lng, 'rating_avg' => $i % 5 === 0 ? null : 3 + (($i % 20) / 10),
                    'status' => HostStatus::Verified,
                ])->save();

                foreach (range(1, 1 + $i % 2) as $n) {
                    Experience::query()->create([
                        'host_profile_id' => $profile->id, 'title' => "Experience $n of host $i", 'category' => 'Load',
                        'description' => 'Generated for load tests.', 'duration_hours' => 2 + $n % 3, 'price_per_person' => (string) (20 + ($i * $n) % 100).'.000',
                        'max_group_size' => 4, 'photo_url' => '/images/experience-placeholder.svg', 'published' => true,
                        'interests' => [$interests[$i % 6]],
                    ]);
                }

                foreach (range(0, 6) as $day) {
                    Availability::query()->create(['host_profile_id' => $profile->id, 'day_of_week' => $day, 'starts_at' => '09:00:00', 'ends_at' => '19:00:00']);
                }
            }

            foreach (range(1, 50) as $t) {
                $user = User::query()->create(['name' => "Load Tourist $t", 'email' => "load-tourist-$t@load.test", 'password' => 'password']);
                $user->forceFill(['role' => Role::Tourist, 'preferred_language' => $languages[$t % 3]])->save();
                $user->languages()->create(['language' => $languages[$t % 3]]);
                $user->interests()->create(['interest' => $interests[$t % 6]]);
            }
        });

        $this->command?->info("Created $count hosts and 50 tourists.");
    }
}
