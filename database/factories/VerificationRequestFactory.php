<?php

namespace Database\Factories;

use App\Modules\Verification\Enums\VerificationRequestStatus;
use App\Modules\Verification\Models\HostProfile;
use App\Modules\Verification\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationRequest>
 */
class VerificationRequestFactory extends Factory
{
    protected $model = VerificationRequest::class;

    public function definition(): array
    {
        return [
            'host_profile_id' => HostProfile::factory(),
            'status' => VerificationRequestStatus::Submitted,
            'evidence_path' => 'verification/'.fake()->uuid().'.mp4',
            'video_call_at' => null,
            'video_call_link' => null,
            'decision' => null,
            'decision_reason' => null,
            'decided_by' => null,
            'decided_at' => null,
        ];
    }
}
