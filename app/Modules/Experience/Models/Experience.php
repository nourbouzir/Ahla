<?php

namespace App\Modules\Experience\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $fillable = [
        'host_profile_id',
        'title',
        'category',
        'description',
        'duration_hours',
        'price_per_person',
        'max_group_size',
        'photo_url',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'price_per_person' => 'decimal:3',
            'published' => 'boolean',
        ];
    }
}
