<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Enums\VerificationRequestStatus;
use App\Modules\Verification\Exceptions\InvalidHostTransition;
use App\Modules\Verification\Models\HostProfile;
use App\Modules\Verification\Models\VerificationRequest;
use App\Modules\Verification\Services\HostStatusService;
use App\Modules\Verification\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verification,
        private readonly HostStatusService $hosts,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'open');

        $requests = VerificationRequest::query()->with('hostProfile.user')
            ->when($status === 'open', fn ($q) => $q->where('status', '!=', VerificationRequestStatus::Decided->value))
            ->when($status === 'decided', fn ($q) => $q->where('status', VerificationRequestStatus::Decided->value))
            ->oldest()->paginate(20)->withQueryString();

        $hosts = HostProfile::query()->where('status', HostStatus::Verified)->orWhere('status', HostStatus::Suspended)
            ->orderBy('display_name')->get();

        return view('admin::verifications.index', compact('requests', 'status', 'hosts'));
    }

    public function show(VerificationRequest $verification): View
    {
        return view('admin::verifications.show', ['request' => $verification->load('hostProfile.user.languages')]);
    }

    public function evidence(VerificationRequest $verification): Response
    {
        $file = $this->verification->readEvidence($verification);
        $type = str_ends_with($file['name'], '.pdf') ? 'application/pdf' : (str_ends_with($file['name'], '.png') ? 'image/png' : 'image/jpeg');

        return response($file['content'], 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'attachment; filename="'.$file['name'].'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function schedule(Request $request, VerificationRequest $verification): RedirectResponse
    {
        $data = $request->validate([
            'video_call_at' => ['required', 'date', 'after:now'],
            'video_call_link' => ['required', 'url:https', 'max:255'],
        ]);

        try {
            $this->verification->schedule($verification, Carbon::parse($data['video_call_at']), $data['video_call_link'], $request->user());
        } catch (InvalidHostTransition $e) {
            return back()->withErrors(['video_call_at' => $e->getMessage()]);
        }

        return back()->with('status', __('admin::messages.scheduled'));
    }

    public function decide(Request $request, VerificationRequest $verification): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->verification->decide($verification, $data['decision'] === 'approve', $data['reason'] ?? null, $request->user());
        } catch (InvalidHostTransition $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()->route('admin.verifications.index')->with('status', __('admin::messages.decided'));
    }

    /** Suspend or reinstate an already verified host (manual in v1). */
    public function setHostStatus(Request $request, HostProfile $host): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:suspended,verified'],
            'reason' => ['required_if:status,suspended', 'nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->hosts->transition($host, HostStatus::from($data['status']), $request->user(), $data['reason'] ?? 'reinstated');
        } catch (InvalidHostTransition $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', __('admin::messages.host_updated'));
    }
}
