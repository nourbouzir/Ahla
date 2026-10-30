<?php

namespace App\Modules\Experience\Models;

use App\Modules\Auth\Models\HostProfile;
use App\Modules\Experience\Database\Factories\BlockedDateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A day a host is unavailable even though a weekly slot would cover it. */
class BlockedDate extends Model
{
    /** @use HasFactory<BlockedDateFactory> */
    use HasFactory;

    protected $fillable = ['host_id', 'blocked_on'];

    protected function casts(): array
    {
        return [
            'blocked_on' => 'date',
        ];
    }

    protected static function newFactory(): BlockedDateFactory
    {
        return BlockedDateFactory::new();
    }

    /** @return BelongsTo<HostProfile, $this> */
    public function host(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class, 'host_id');
    }
}
