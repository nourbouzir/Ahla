<?php

namespace App\Modules\Verification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Exceptions\InvalidHostTransition;
use App\Modules\Verification\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(private readonly VerificationService $verification) {}

    public function show(Request $request): View
    {
        $profile = $request->user()->hostProfile;

        return view('verification::application', [
            'profile' => $profile,
            'open' => $profile ? $this->verification->open($profile) : null,
            'last' => $profile?->verificationRequests()->latest('id')->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->hostProfile;

        // The public profile must exist first: the admin reviews who the host says they are.
        if ($profile === null) {
            return redirect()->route('profile.edit')->withErrors(['evidence' => __('verification::messages.profile_first')]);
        }

        $request->validate([
            // Images or PDF, 5 MB at most. mimes checks the real content, not just the extension.
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        try {
            $this->verification->apply($profile, $request->file('evidence'), $request->user());
        } catch (InvalidHostTransition $e) {
            return back()->withErrors(['evidence' => __('verification::messages.cannot_apply')]);
        }

        return redirect()->route('host.verification.show')->with('status', __('verification::messages.submitted'));
    }
}
