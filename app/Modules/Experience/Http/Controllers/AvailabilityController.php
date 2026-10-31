<?php

namespace App\Modules\Experience\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\BlockedDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->hostProfile;

        return view('experience::availability', [
            'profile' => $profile,
            'slots' => $profile ? $profile->availabilities()->orderBy('day_of_week')->orderBy('starts_at')->get() : collect(),
            'blocked' => $profile ? $profile->blockedDates()->whereDate('date', '>=', today())->orderBy('date')->get() : collect(),
        ]);
    }

    public function storeSlot(Request $request): RedirectResponse
    {
        $profile = $request->user()->hostProfile;
        abort_if($profile === null, 403);

        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ]);

        // Two slots on the same day may not overlap.
        $overlaps = $profile->availabilities()->where('day_of_week', $data['day_of_week'])
            ->where('starts_at', '<', $data['ends_at'].':00')->where('ends_at', '>', $data['starts_at'].':00')->exists();

        if ($overlaps) {
            return back()->withErrors(['starts_at' => __('experience::messages.slot_overlap')])->withInput();
        }

        $profile->availabilities()->create([
            'day_of_week' => $data['day_of_week'],
            // Seconds included so time strings compare correctly on every database.
            'starts_at' => $data['starts_at'].':00',
            'ends_at' => $data['ends_at'].':00',
        ]);

        return back()->with('status', __('experience::messages.saved'));
    }

    public function destroySlot(Request $request, Availability $slot): RedirectResponse
    {
        abort_unless($slot->host_profile_id === $request->user()->hostProfile?->id, 404);
        $slot->delete();

        return back()->with('status', __('experience::messages.saved'));
    }

    public function storeBlocked(Request $request): RedirectResponse
    {
        $profile = $request->user()->hostProfile;
        abort_if($profile === null, 403);

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:160'],
        ]);

        $profile->blockedDates()->firstOrCreate(['date' => $data['date']], ['reason' => $data['reason'] ?? null]);

        return back()->with('status', __('experience::messages.saved'));
    }

    public function destroyBlocked(Request $request, BlockedDate $blocked): RedirectResponse
    {
        abort_unless($blocked->host_profile_id === $request->user()->hostProfile?->id, 404);
        $blocked->delete();

        return back()->with('status', __('experience::messages.saved'));
    }
}
