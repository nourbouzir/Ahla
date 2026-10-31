<?php

namespace App\Modules\Booking\Models;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Experience\Models\Experience;
use App\Modules\Messaging\Models\Message;
use App\Modules\Payment\Models\Payment;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected static string $factory = BookingFactory::class;

    protected $fillable = [
        'tourist_id',
        'host_id',
        'experience_id',
        'date',
        'start_time',
        'end_time',
        'group_size',
        'location',
        'displayed_price',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'displayed_price' => 'decimal:3',
            'status' => BookingStatus::class,
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tourist_id');
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
