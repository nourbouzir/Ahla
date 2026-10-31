<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use BackedEnum;

/**
 * The single writer of audit_log. Every host, booking and payment status
 * change calls record() with who did it and why (ADR 0004, FR-20 audit rule).
 * Call it inside the same DB transaction as the change, so the two commit or
 * roll back together.
 */
class AuditLogger
{
    public function record(
        string $entity,
        int $entityId,
        BackedEnum|string|null $oldStatus,
        BackedEnum|string $newStatus,
        ?User $actor = null,
        ?string $reason = null,
    ): AuditLog {
        return AuditLog::create([
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_status' => $oldStatus instanceof BackedEnum ? $oldStatus->value : $oldStatus,
            'new_status' => $newStatus instanceof BackedEnum ? $newStatus->value : $newStatus,
            'actor_id' => $actor?->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
