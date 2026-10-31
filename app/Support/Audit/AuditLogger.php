<?php

namespace App\Support\Audit;

use App\Modules\Admin\Models\AuditLog;

class AuditLogger
{
    public function record(
        string $entity,
        int $id,
        ?string $oldStatus,
        string $newStatus,
        ?int $actorId,
        ?string $reason = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'entity' => $entity,
            'entity_id' => $id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'actor_id' => $actorId,
            'reason' => $reason,
        ]);
    }
}
