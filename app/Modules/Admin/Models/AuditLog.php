<?php

namespace App\Modules\Admin\Models;

use App\Models\User;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected static string $factory = AuditLogFactory::class;

    protected $table = 'audit_log';

    public const UPDATED_AT = null;

    protected $fillable = [
        'entity',
        'entity_id',
        'old_status',
        'new_status',
        'actor_id',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** Append-only (NFR-19 tamper protection): rows are never edited or deleted through the application. */
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('audit_log rows are append-only.'));
        static::deleting(fn () => throw new \LogicException('audit_log rows are append-only.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
