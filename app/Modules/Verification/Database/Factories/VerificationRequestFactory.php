<?php

namespace App\Modules\Verification\Database\Factories;

use App\Models\User;
use App\Modules\Auth\Enums\HostStatus;
use App\Modules\Auth\Models\HostProfile;
use App\Modules\Verification\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds the request only: it does not change the host's status. The status
 * change belongs to the Verification module's own code.
 *
 * @extends Factory<VerificationRequest>
 */
class VerificationRequestFactory extends Factory
{
    protected $model = VerificationRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'host_id' => HostProfile::factory(),
            'id_document_path' => 'id-documents/'.fake()->uuid().'.jpg',
            'video_call_at' => null,
            'video_call_link' => null,
            'decision' => null,
            'reason' => null,
            'decided_by' => null,
            'decided_at' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state([
            'video_call_at' => now()->addDays(2),
            'video_call_link' => 'https://meet.jit.si/ahla-'.fake()->uuid(),
        ]);
    }

    public function verified(): static
    {
        return $this->scheduled()->state([
            'decision' => HostStatus::Verified,
            'decided_by' => User::factory()->admin(),
            'decided_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->scheduled()->state([
            'decision' => HostStatus::Rejected,
            'reason' => fake()->sentence(),
            'decided_by' => User::factory()->admin(),
            'decided_at' => now(),
        ]);
    }
}
