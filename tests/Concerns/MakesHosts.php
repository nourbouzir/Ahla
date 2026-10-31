<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Enums\HostStatus;

trait MakesHosts
{
    /**
     * A host with a profile, languages, interests, a published experience and
     * weekly hours every day 08:00-22:00.
     *
     * @param  list<string>  $languages
     * @param  list<string>  $interests
     */
    protected function makeHost(
        string $name,
        string $city = 'Tunis',
        array $languages = ['en'],
        array $interests = ['food'],
        ?float $rating = null,
        HostStatus $status = HostStatus::Verified,
        string $price = '50.000',
        bool $published = true,
    ): User {
        $user = User::factory()->host($status)->create(['name' => $name]);
        [$lat, $lng] = config('ahla.cities')[$city];
        $user->hostProfile->forceFill([
            'display_name' => $name, 'city' => $city, 'bio' => "About $name", 'rating_avg' => $rating,
            'latitude' => $lat, 'longitude' => $lng,
        ])->save();
        $user->languages()->createMany(array_map(fn ($l) => ['language' => $l], $languages));
        $user->interests()->createMany(array_map(fn ($i) => ['interest' => $i], $interests));

        Experience::factory()->create([
            'host_profile_id' => $user->hostProfile->id, 'title' => "$name experience", 'published' => $published,
            'price_per_person' => $price, 'duration_hours' => 2, 'max_group_size' => 4,
        ]);

        foreach (range(0, 6) as $day) {
            Availability::factory()->create(['host_profile_id' => $user->hostProfile->id, 'day_of_week' => $day, 'starts_at' => '08:00:00', 'ends_at' => '22:00:00']);
        }

        return $user->fresh(['hostProfile']);
    }
}
