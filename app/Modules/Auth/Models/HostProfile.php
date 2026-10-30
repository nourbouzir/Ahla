<?php

namespace App\Modules\Auth\Models;

use App\Models\User;
use App\Modules\Auth\Database\Factories\HostProfileFactory;
use App\Modules\Auth\Enums\HostStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The host side of a user. The primary key is the user's id. `status` is only
 * stored here; the rules for changing it belong to the Verification module.
 *
 * Dependencies point one way: the Verification and Experience models belong to
 * a HostProfile, but this class knows nothing about them.
 */
class HostProfile extends Model
{
    /** @use HasFactory<HostProfileFactory> */
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /** `status` and `rating_avg` are not mass assignable: only the Verification module and reviews change them. */
    protected $fillable = ['user_id', 'bio', 'city', 'lat', 'lng', 'photo_path'];

    protected function casts(): array
    {
        return [
            'status' => HostStatus::class,
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'rating_avg' => 'decimal:2',
        ];
    }

    protected static function newFactory(): HostProfileFactory
    {
        return HostProfileFactory::new();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
