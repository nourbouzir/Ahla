<?php

namespace Database\Factories;

use App\Modules\Experience\Models\BlockedDate;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedDate>
 */
class BlockedDateFactory extends Factory
{
    protected $model = BlockedDate::class;

    public function definition(): array
    {
        return [
            'host_profile_id' => HostProfile::factory(),
            'date' => fake()->unique()->dateTimeBetween('+1 day', '+1 year')->format('Y-m-d'),
            'reason' => null,
        ];
    }
}
