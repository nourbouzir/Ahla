<?php

namespace Database\Factories;

use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'gateway_ref' => 'fake-'.fake()->unique()->uuid(),
            'amount' => fn (array $attributes) => (string) Booking::query()
                ->findOrFail($attributes['booking_id'])
                ->displayed_price,
            'refunded_amount' => '0.000',
            'currency' => 'TND',
            'status' => PaymentStatus::Authorised,
            'authorised_at' => now(),
            'captured_at' => null,
            'released_at' => null,
            'refunded_at' => null,
        ];
    }

    public function captured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Captured,
            'captured_at' => now(),
        ]);
    }

    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Released,
            'released_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Refunded,
            'refunded_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
            'gateway_ref' => null,
            'authorised_at' => null,
        ]);
    }
}
