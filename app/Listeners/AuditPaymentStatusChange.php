<?php

namespace App\Listeners;

use App\Events\PaymentStatusChanged;
use App\Support\Audit\AuditLogger;

class AuditPaymentStatusChange
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(PaymentStatusChanged $event): void
    {
        $this->auditLogger->record(
            'payment',
            $event->paymentId,
            $event->oldStatus,
            $event->newStatus,
            null,
        );
    }
}
