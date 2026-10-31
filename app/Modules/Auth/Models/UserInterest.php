<?php

namespace App\Modules\Auth\Models;

use App\Models\User;
use Database\Factories\UserInterestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInterest extends Model
{
    use HasFactory;

    protected static string $factory = UserInterestFactory::class;

    protected $fillable = ['user_id', 'interest'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
