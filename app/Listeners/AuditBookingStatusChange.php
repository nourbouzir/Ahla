<?php

namespace App\Listeners;

use App\Events\BookingStatusChanged;
use App\Support\Audit\AuditLogger;

class AuditBookingStatusChange
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(BookingStatusChanged $event): void
    {
        $this->auditLogger->record(
            'booking',
            $event->bookingId,
            $event->oldStatus,
            $event->newStatus,
            $event->actorId,
        );
    }
}
