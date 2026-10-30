<?php

namespace App\Modules\Experience\Database\Factories;

use App\Modules\Auth\Models\HostProfile;
use App\Modules\Experience\Models\BlockedDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedDate>
 */
class BlockedDateFactory extends Factory
{
    protected $model = BlockedDate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'host_id' => HostProfile::factory()->verified(),
            'blocked_on' => fake()->unique()->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
        ];
    }

    public function on(string $date): static
    {
        return $this->state(['blocked_on' => $date]);
    }
}
