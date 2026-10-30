<?php

namespace App\Modules\Auth\Database\Factories;

use App\Models\User;
use App\Modules\Auth\Enums\HostStatus;
use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Models\HostProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HostProfile>
 */
class HostProfileFactory extends Factory
{
    protected $model = HostProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Host]),
            'status' => HostStatus::Pending,
            'bio' => fake()->paragraph(),
            'city' => fake()->randomElement(['Tunis', 'Sousse', 'Nabeul', 'Kerkennah', 'Djerba', 'Sfax']),
            'lat' => fake()->latitude(33.0, 37.3),
            'lng' => fake()->longitude(8.0, 11.5),
            'photo_path' => null,
            'rating_avg' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => HostStatus::Pending]);
    }

    public function verified(): static
    {
        return $this->state(['status' => HostStatus::Verified]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => HostStatus::Rejected]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => HostStatus::Suspended]);
    }

    public function rated(float $average): static
    {
        return $this->state(['rating_avg' => $average]);
    }
}
