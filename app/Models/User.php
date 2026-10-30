<?php

namespace App\Models;

use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Models\HostProfile;
use App\Modules\Auth\Models\UserInterest;
use App\Modules\Auth\Models\UserLanguage;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * `role` is left out on purpose: registration sets it explicitly (tourist or
     * host only), so a request can never promote itself to admin.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'preferred_language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /** @return HasMany<UserLanguage, $this> */
    public function languages(): HasMany
    {
        return $this->hasMany(UserLanguage::class);
    }

    /** @return HasMany<UserInterest, $this> */
    public function interests(): HasMany
    {
        return $this->hasMany(UserInterest::class);
    }

    /** @return HasOne<HostProfile, $this> */
    public function hostProfile(): HasOne
    {
        return $this->hasOne(HostProfile::class);
    }
}
