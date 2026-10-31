<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Experience\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'tourist_id' => User::factory()->tourist(),
            'experience_id' => Experience::factory(),
            'host_id' => fn (array $attributes) => Experience::query()
                ->findOrFail($attributes['experience_id'])
                ->hostProfile
                ->user_id,
            'date' => fake()->dateTimeBetween('+2 days', '+3 months')->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'group_size' => 2,
            'location' => null,
            'displayed_price' => fn (array $attributes) => self::experiencePrice(
                Experience::query()->findOrFail($attributes['experience_id']),
                $attributes['group_size'],
            ),
            'status' => BookingStatus::Requested,
            'expires_at' => now()->addHours(12),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::Confirmed]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::Declined]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::Expired]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::Completed]);
    }

    private static function experiencePrice(Experience $experience, int $groupSize): string
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $experience->price_per_person, 2), 2, '000');
        $unitAmount = ((int) $whole * 1000) + (int) substr(str_pad($fraction, 3, '0'), 0, 3);
        $total = $unitAmount * $groupSize;

        return intdiv($total, 1000).'.'.str_pad((string) ($total % 1000), 3, '0', STR_PAD_LEFT);
    }
}
