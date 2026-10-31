<?php

namespace App\Modules\Experience\Models;

use App\Modules\Verification\Models\HostProfile;
use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    use HasFactory;

    protected static string $factory = AvailabilityFactory::class;

    protected $fillable = ['host_profile_id', 'day_of_week', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    public function hostProfile(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class);
    }
}
