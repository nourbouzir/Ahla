<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\BlockedDate;
use App\Modules\Verification\Enums\HostStatus;
use Database\Factories\HostProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostProfile extends Model
{
    use HasFactory;

    protected static string $factory = HostProfileFactory::class;

    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'city',
        'latitude',
        'longitude',
        'rating_avg',
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => HostStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }
}
