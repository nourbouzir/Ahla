<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'entity' => 'booking',
            'entity_id' => Booking::factory(),
            'old_status' => BookingStatus::Requested->value,
            'new_status' => BookingStatus::Confirmed->value,
            'actor_id' => User::factory(),
            'reason' => null,
            'created_at' => now(),
        ];
    }
}
