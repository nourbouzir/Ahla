<?php

namespace App\Modules\Booking\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Support\ProfileVisibility;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\HostClientNote;
use App\Modules\Booking\Services\HostDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(private readonly HostDashboard $dashboard) {}

    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['recent', 'name', 'spent', 'bookings'], true) ? $request->query('sort') : 'recent';
        $stage = $request->query('stage');
        $all = $this->dashboard->clients($request->user(), null, $sort);
        $clients = $request->query('q') ? $this->dashboard->clients($request->user(), $request->query('q'), $sort) : $all;

        return view('booking::host.clients', [
            'clients' => $stage ? $clients->where('stage', $stage)->values() : $clients,
            'all' => $all,
            'sort' => $sort,
            'stage' => $stage,
            'q' => $request->query('q'),
        ]);
    }

    public function show(Request $request, User $tourist, ProfileVisibility $visibility): View
    {
        $host = $request->user();
        $bookings = Booking::query()->with(['experience', 'payment'])->where('host_id', $host->id)->where('tourist_id', $tourist->id)
            ->orderByDesc('date')->orderByDesc('start_time')->get();

        // Only people this guide has actually worked with; anybody else is a 404, not a hint that they exist.
        abort_if($bookings->isEmpty(), 404);

        return view('booking::host.client', [
            'tourist' => $tourist->load(['languages', 'interests']),
            'bookings' => $bookings,
            'note' => HostClientNote::where('host_id', $host->id)->where('tourist_id', $tourist->id)->value('body'),
            'row' => $this->dashboard->clients($host)->first(fn ($r) => $r['tourist']->id === $tourist->id),
            'canSeeContact' => $visibility->canSeeContact($host, $tourist),
            'canSeePhone' => $visibility->canSeePhone($host, $tourist),
            'chatBooking' => $bookings->first(fn ($b) => in_array($b->status, [BookingStatus::Requested, BookingStatus::Confirmed, BookingStatus::Completed], true)) ?? $bookings->first(),
        ]);
    }

    public function saveNote(Request $request, User $tourist): RedirectResponse
    {
        $host = $request->user();
        abort_unless(Booking::where('host_id', $host->id)->where('tourist_id', $tourist->id)->exists(), 404);

        $data = $request->validate(['body' => ['nullable', 'string', 'max:2000']]);

        if (blank($data['body'] ?? null)) {
            HostClientNote::where('host_id', $host->id)->where('tourist_id', $tourist->id)->delete();
        } else {
            HostClientNote::updateOrCreate(['host_id' => $host->id, 'tourist_id' => $tourist->id], ['body' => trim($data['body'])]);
        }

        return back()->with('status', __('booking::host.client.note_saved'));
    }
}
