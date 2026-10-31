<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use App\Modules\Verification\Enums\VerificationRequestStatus;
use Database\Factories\VerificationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRequest extends Model
{
    use HasFactory;

    protected static string $factory = VerificationRequestFactory::class;

    protected $fillable = [
        'host_profile_id',
        'evidence_path',
        'video_call_at',
        'video_call_link',
    ];

    protected function casts(): array
    {
        return [
            'status' => VerificationRequestStatus::class,
            'video_call_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function hostProfile(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class);
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
