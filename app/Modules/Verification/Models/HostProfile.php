<?php

namespace App\Modules\Verification\Models;

use Illuminate\Database\Eloquent\Model;

class HostProfile extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'display_name',
        'bio',
        'city',
        'languages',
        'interests',
        'rating_avg',
        'rating_count',
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'interests' => 'array',
            'rating_avg' => 'decimal:2',
        ];
    }
}
