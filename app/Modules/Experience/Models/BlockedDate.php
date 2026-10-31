<?php

namespace App\Modules\Experience\Models;

use App\Modules\Verification\Models\HostProfile;
use Database\Factories\BlockedDateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedDate extends Model
{
    use HasFactory;

    protected static string $factory = BlockedDateFactory::class;

    protected $fillable = ['host_profile_id', 'date', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function hostProfile(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class);
    }
}
