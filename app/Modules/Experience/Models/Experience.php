<?php

namespace App\Modules\Experience\Models;

use App\Modules\Verification\Models\HostProfile;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    use HasFactory;

    protected static string $factory = ExperienceFactory::class;

    protected $fillable = [
        'host_profile_id',
        'title',
        'category',
        'interests',
        'description',
        'duration_hours',
        'meeting_point',
        'price_per_person',
        'max_group_size',
        'photo_url',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'price_per_person' => 'decimal:3',
            'interests' => 'array',
            'published' => 'boolean',
        ];
    }

    public function hostProfile(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class);
    }
}
