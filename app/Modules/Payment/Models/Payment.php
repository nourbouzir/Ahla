<?php

namespace App\Modules\Payment\Models;

use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected static string $factory = PaymentFactory::class;

    protected $fillable = [
        'booking_id',
        'amount',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:3',
            'refunded_amount' => 'decimal:3',
            'status' => PaymentStatus::class,
            'authorised_at' => 'datetime',
            'captured_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
