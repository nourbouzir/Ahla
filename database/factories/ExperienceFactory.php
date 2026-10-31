<?php

namespace Database\Factories;

use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    protected $model = Experience::class;

    public function definition(): array
    {
        return [
            'host_profile_id' => HostProfile::factory(),
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(['Culture', 'Food', 'Outdoors']),
            'interests' => ['food'],
            'description' => fake()->paragraph(),
            'duration_hours' => fake()->numberBetween(1, 6),
            'meeting_point' => fake()->city(),
            'price_per_person' => number_format(fake()->randomFloat(3, 10, 250), 3, '.', ''),
            'max_group_size' => fake()->numberBetween(1, 6),
            'photo_url' => fake()->imageUrl(),
            'published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['published' => true]);
    }
}
