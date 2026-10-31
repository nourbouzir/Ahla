<?php

namespace App\Modules\Payment\Policies;

use App\Modules\Booking\Models\Booking;
use Carbon\CarbonInterface;

/** A host cancelling always returns everything, whatever the timing (ADR 0004). */
class HostCancellationRefundPolicy implements RefundPolicy
{
    public function refundAmount(Booking $booking, string $paidAmount, CarbonInterface $now): string
    {
        return bcadd($paidAmount, '0', 3);
    }
}
