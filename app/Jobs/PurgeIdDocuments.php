<?php

namespace App\Jobs;

use App\Modules\Verification\Enums\VerificationRequestStatus;
use App\Modules\Verification\Models\VerificationRequest;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes identity documents once the retention period after the decision has
 * passed (default 90 days, AHLA_EVIDENCE_RETENTION_DAYS; final value is an open
 * decision in ADR 0004). The request row stays for the audit trail.
 */
class PurgeIdDocuments implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const PURGED = 'purged';

    public function handle(): int
    {
        $count = 0;
        $cutoff = now()->subDays((int) config('ahla.evidence_retention_days'));

        VerificationRequest::query()
            ->where('status', VerificationRequestStatus::Decided->value)
            ->where('decided_at', '<=', $cutoff)
            ->where('evidence_path', '!=', self::PURGED)
            ->each(function (VerificationRequest $request) use (&$count) {
                Storage::disk('local')->delete($request->evidence_path);
                $request->forceFill(['evidence_path' => self::PURGED])->save();
                $count++;
            });

        return $count;
    }
}
