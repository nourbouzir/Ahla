<?php

namespace App\Modules\Auth\Models;

use App\Models\User;
use Database\Factories\UserLanguageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLanguage extends Model
{
    use HasFactory;

    protected static string $factory = UserLanguageFactory::class;

    protected $fillable = ['user_id', 'language'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
