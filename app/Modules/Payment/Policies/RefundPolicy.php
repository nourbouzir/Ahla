<?php

namespace App\Modules\Payment\Policies;

use App\Modules\Booking\Models\Booking;
use Carbon\CarbonInterface;

/** Decides how much of a captured payment goes back. Amounts live only in policy classes. */
interface RefundPolicy
{
    /** @return string Amount to refund, TND with 3 decimals (never a float). */
    public function refundAmount(Booking $booking, string $paidAmount, CarbonInterface $now): string;
}
