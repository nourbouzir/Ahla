<?php

namespace App\Modules\Verification\Services;

use App\Models\User;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Enums\VerificationRequestStatus;
use App\Modules\Verification\Exceptions\InvalidHostTransition;
use App\Modules\Verification\Models\HostProfile;
use App\Modules\Verification\Models\VerificationRequest;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Host verification (FR-06/07): the host sends identity evidence, an admin
 * schedules a video call and then approves or rejects. Evidence is encrypted
 * before it is written and lives on the private disk, never on a public URL.
 */
class VerificationService
{
    public function __construct(
        private readonly HostStatusService $hosts,
        private readonly AuditLogger $audit,
    ) {}

    public function open(HostProfile $profile): ?VerificationRequest
    {
        return $profile->verificationRequests()
            ->where('status', '!=', VerificationRequestStatus::Decided->value)
            ->latest('id')->first();
    }

    public function apply(HostProfile $profile, UploadedFile $evidence, User $actor): VerificationRequest
    {
        $profile->refresh();

        if (! in_array($profile->status, [HostStatus::Pending, HostStatus::Rejected], true)) {
            throw new InvalidHostTransition('Only pending or rejected hosts can apply.');
        }

        if ($this->open($profile)) {
            throw new InvalidHostTransition('An application is already under review.');
        }

        $path = 'verification/'.$profile->id.'/'.Str::uuid().'.'.$evidence->extension().'.enc';
        Storage::disk('local')->put($path, Crypt::encryptString(file_get_contents($evidence->getRealPath())));

        return DB::transaction(function () use ($profile, $path, $actor) {
            if ($profile->status === HostStatus::Rejected) {
                $this->hosts->transition($profile, HostStatus::Pending, $actor, 'new application');
            }

            $request = new VerificationRequest(['host_profile_id' => $profile->id, 'evidence_path' => $path]);
            $request->save();
            $this->audit->record('verification', $request->id, null, VerificationRequestStatus::Submitted, $actor, 'application submitted');

            return $request;
        });
    }

    public function schedule(VerificationRequest $request, CarbonInterface $at, string $link, User $admin): VerificationRequest
    {
        $this->assertOpen($request);

        return DB::transaction(function () use ($request, $at, $link, $admin) {
            $from = $request->status;
            $request->forceFill([
                'status' => VerificationRequestStatus::Scheduled,
                'video_call_at' => $at,
                'video_call_link' => $link,
            ])->save();
            $this->audit->record('verification', $request->id, $from, VerificationRequestStatus::Scheduled, $admin, 'video call scheduled');

            return $request;
        });
    }

    /** Approval needs a scheduled call (identity evidence AND video call); rejection does not. */
    public function decide(VerificationRequest $request, bool $approve, ?string $reason, User $admin): VerificationRequest
    {
        $this->assertOpen($request);

        if ($approve && $request->status !== VerificationRequestStatus::Scheduled) {
            throw new InvalidHostTransition('Schedule the video call before approving.');
        }

        return DB::transaction(function () use ($request, $approve, $reason, $admin) {
            $this->hosts->transition(
                $request->hostProfile,
                $approve ? HostStatus::Verified : HostStatus::Rejected,
                $admin,
                $reason,
            );

            $from = $request->status;
            $request->forceFill([
                'status' => VerificationRequestStatus::Decided,
                'decision' => $approve ? 'approved' : 'rejected',
                'decision_reason' => $reason,
                'decided_by' => $admin->id,
                'decided_at' => now(),
            ])->save();
            $this->audit->record('verification', $request->id, $from, VerificationRequestStatus::Decided, $admin, $reason);

            return $request;
        });
    }

    /** @return array{content: string, name: string} decrypted evidence for an admin download */
    public function readEvidence(VerificationRequest $request): array
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($request->evidence_path), 404);

        return [
            'content' => Crypt::decryptString($disk->get($request->evidence_path)),
            'name' => 'evidence-'.$request->id.'.'.pathinfo(str_replace('.enc', '', $request->evidence_path), PATHINFO_EXTENSION),
        ];
    }

    private function assertOpen(VerificationRequest $request): void
    {
        if ($request->status === VerificationRequestStatus::Decided) {
            throw new InvalidHostTransition('This application is already decided.');
        }
    }
}
