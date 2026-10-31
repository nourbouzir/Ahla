<?php

namespace App\Modules\Payment\Policies;

use App\Modules\Booking\Models\Booking;
use Carbon\CarbonInterface;

/**
 * Tourist cancels: 100% at 48 h or more before the start, a configured share
 * (default 50%) from 24 to 48 h, nothing under 24 h. Thresholds come from
 * config('ahla.refunds.tourist') because the final policy is still open (ADR 0004).
 * Money maths use bcmath strings so 3-decimal TND never drifts like a float.
 */
class TouristCancellationRefundPolicy implements RefundPolicy
{
    public function refundAmount(Booking $booking, string $paidAmount, CarbonInterface $now): string
    {
        $config = config('ahla.refunds.tourist');
        $start = $booking->date->copy()->setTimeFromTimeString($booking->start_time);
        // Whole hours until the start (negative once it has begun).
        $hours = $now->diffInHours($start, false);

        if ($hours >= $config['full_refund_hours']) {
            $percent = '100';
        } elseif ($hours >= $config['partial_refund_hours']) {
            $percent = (string) $config['partial_refund_percent'];
        } else {
            $percent = '0';
        }

        return bcdiv(bcmul($paidAmount, $percent, 6), '100', 3);
    }
}
