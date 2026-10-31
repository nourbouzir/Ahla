<?php

namespace Database\Factories;

use App\Modules\Experience\Models\Availability;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    protected $model = Availability::class;

    public function definition(): array
    {
        return [
            'host_profile_id' => HostProfile::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'starts_at' => '09:00:00',
            'ends_at' => '12:00:00',
        ];
    }
}
