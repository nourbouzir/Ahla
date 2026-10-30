<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use App\Modules\Auth\Enums\HostStatus;
use App\Modules\Auth\Models\HostProfile;
use App\Modules\Verification\Database\Factories\VerificationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One application to become a verified host (FR-05): ID evidence, video call, admin decision. */
class VerificationRequest extends Model
{
    /** @use HasFactory<VerificationRequestFactory> */
    use HasFactory;

    protected $fillable = ['host_id', 'id_document_path', 'video_call_at', 'video_call_link'];

    protected function casts(): array
    {
        return [
            'video_call_at' => 'datetime',
            'decided_at' => 'datetime',
            // Only Verified or Rejected; empty while the application is pending.
            'decision' => HostStatus::class,
        ];
    }

    protected static function newFactory(): VerificationRequestFactory
    {
        return VerificationRequestFactory::new();
    }

    /** @return BelongsTo<HostProfile, $this> */
    public function host(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class, 'host_id');
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
