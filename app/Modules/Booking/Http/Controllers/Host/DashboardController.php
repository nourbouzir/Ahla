<?php

namespace App\Modules\Booking\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\HostDashboard;
use App\Modules\Messaging\Models\Message;
use App\Modules\Verification\Enums\HostStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly HostDashboard $dashboard) {}

    public function index(Request $request): View
    {
        $host = $request->user();
        $profile = $host->hostProfile;

        // What is still missing before the guide can really take bookings.
        $checklist = [
            'profile' => $profile !== null,
            'verified' => $profile?->status === HostStatus::Verified,
            'experience' => $profile?->experiences()->where('published', true)->exists() ?? false,
            'hours' => $profile?->availabilities()->exists() ?? false,
            'photo' => $host->avatar_path !== null,
        ];

        return view('booking::host.dashboard', $this->dashboard->overview($host) + [
            'host' => $host,
            'profile' => $profile,
            'checklist' => $checklist,
            'progress' => (int) round(count(array_filter($checklist)) / count($checklist) * 100),
            'recent' => $this->dashboard->inbox($host)->filter(fn ($r) => $r['last'])->take(4),
        ]);
    }

    /** Polled every 30 s so the menu badges stay fresh without reloading. */
    public function summary(Request $request): JsonResponse
    {
        $id = $request->user()->id;

        return response()->json([
            'host.bookings.index' => Booking::where('host_id', $id)->where('status', 'requested')->count(),
            'host.inbox' => Message::where('recipient_id', $id)->whereNull('read_at')->count(),
        ]);
    }
}
