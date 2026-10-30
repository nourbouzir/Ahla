<?php

namespace App\Modules\Experience\Database\Factories;

use App\Modules\Auth\Models\HostProfile;
use App\Modules\Experience\Models\Availability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    protected $model = Availability::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'host_id' => HostProfile::factory()->verified(),
            'weekday' => fake()->numberBetween(1, 7),
            'start_time' => '09:00',
            'end_time' => '17:00',
        ];
    }

    /** ISO-8601 weekday: 1 = Monday ... 7 = Sunday. */
    public function onWeekday(int $weekday): static
    {
        return $this->state(['weekday' => $weekday]);
    }

    public function between(string $start, string $end): static
    {
        return $this->state(['start_time' => $start, 'end_time' => $end]);
    }
}
