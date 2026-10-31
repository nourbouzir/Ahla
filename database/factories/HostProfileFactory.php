<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HostProfile>
 */
class HostProfileFactory extends Factory
{
    protected $model = HostProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Host]),
            'status' => HostStatus::Pending,
            'display_name' => fake()->name(),
            'bio' => fake()->paragraph(),
            'city' => fake()->randomElement(['Tunis', 'Sousse', 'Djerba', 'Tozeur']),
            'latitude' => null,
            'longitude' => null,
            'rating_avg' => null,
            'photo_url' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => ['status' => HostStatus::Verified]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => HostStatus::Rejected]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => HostStatus::Suspended]);
    }
}
