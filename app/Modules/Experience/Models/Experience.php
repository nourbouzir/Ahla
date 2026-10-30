<?php

namespace App\Modules\Experience\Models;

use App\Modules\Auth\Models\HostProfile;
use App\Modules\Experience\Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** What a host offers and what a tourist books: a short activity with a price per person. */
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory;

    // Business limits (ADR 0004, decision 13). Enforced when an experience is saved by the app.
    public const MIN_DURATION_HOURS = 1;

    public const MAX_DURATION_HOURS = 6;

    public const MAX_GROUP_SIZE = 6;

    protected $fillable = [
        'host_id',
        'title',
        'description',
        'duration_hours',
        'price_per_person',
        'max_group_size',
        'meeting_point',
        'meeting_lat',
        'meeting_lng',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'price_per_person' => 'decimal:3',
            'meeting_lat' => 'decimal:7',
            'meeting_lng' => 'decimal:7',
            'published' => 'boolean',
        ];
    }

    protected static function newFactory(): ExperienceFactory
    {
        return ExperienceFactory::new();
    }

    /** @return BelongsTo<HostProfile, $this> */
    public function host(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class, 'host_id');
    }
}
