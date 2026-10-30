<?php

namespace App\Modules\Experience\Database\Factories;

use App\Modules\Auth\Models\HostProfile;
use App\Modules\Experience\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    protected $model = Experience::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'host_id' => HostProfile::factory()->verified(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'duration_hours' => fake()->numberBetween(Experience::MIN_DURATION_HOURS, Experience::MAX_DURATION_HOURS),
            // A string with 3 decimals: money is never a float.
            'price_per_person' => number_format(fake()->randomFloat(3, 15, 250), 3, '.', ''),
            'max_group_size' => fake()->numberBetween(1, Experience::MAX_GROUP_SIZE),
            'meeting_point' => fake()->streetAddress(),
            'meeting_lat' => fake()->latitude(33.0, 37.3),
            'meeting_lng' => fake()->longitude(8.0, 11.5),
            'published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['published' => true]);
    }
}
