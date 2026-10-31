<?php

namespace Database\Factories;

use App\Modules\Booking\Models\Booking;
use App\Modules\Messaging\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'sender_id' => fn (array $attributes) => Booking::query()->findOrFail($attributes['booking_id'])->tourist_id,
            'recipient_id' => fn (array $attributes) => Booking::query()->findOrFail($attributes['booking_id'])->host_id,
            'original_text' => fake()->sentence(),
            'translated_text' => null,
            'language' => 'en',
            'translated_language' => null,
            'read_at' => null,
        ];
    }
}
