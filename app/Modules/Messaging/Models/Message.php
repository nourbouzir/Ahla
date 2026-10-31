<?php

namespace App\Modules\Messaging\Models;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected static string $factory = MessageFactory::class;

    protected $fillable = [
        'booking_id',
        'sender_id',
        'recipient_id',
        'original_text',
        'translated_text',
        'language',
        'translated_language',
        'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
