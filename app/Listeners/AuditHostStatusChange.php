<?php

namespace App\Listeners;

use App\Events\HostStatusChanged;
use App\Support\Audit\AuditLogger;

class AuditHostStatusChange
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(HostStatusChanged $event): void
    {
        $this->auditLogger->record(
            'host_profile',
            $event->hostProfileId,
            $event->oldStatus,
            $event->newStatus,
            $event->actorId,
        );
    }
}
