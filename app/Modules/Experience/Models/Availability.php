<?php

namespace App\Modules\Experience\Models;

use App\Modules\Auth\Models\HostProfile;
use App\Modules\Experience\Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A weekly slot when a host can take bookings. `weekday` is ISO-8601: 1 = Monday ... 7 = Sunday. */
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    // The table is named "availability" (singular), unlike Laravel's default plural.
    protected $table = 'availability';

    protected $fillable = ['host_id', 'weekday', 'start_time', 'end_time'];

    protected static function newFactory(): AvailabilityFactory
    {
        return AvailabilityFactory::new();
    }

    /** @return BelongsTo<HostProfile, $this> */
    public function host(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class, 'host_id');
    }
}
