<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Contracts\Hosts\AlternativeHostFinder;
use App\Http\Controllers\Controller;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\BookingRuleViolation;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Experience\Models\Experience;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function create(Experience $experience): View
    {
        $this->assertBookable($experience);

        return view('booking::create', [
            'experience' => $experience->load('hostProfile.availabilities'),
        ]);
    }

    public function store(Request $request, Experience $experience): RedirectResponse
    {
        $this->assertBookable($experience);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before:+1 year'],
            'start_time' => ['required', 'date_format:H:i'],
            'group_size' => ['required', 'integer', 'between:1,6'],
        ]);

        try {
            $booking = $this->bookings->request($request->user(), $experience, $data['date'], $data['start_time'], (int) $data['group_size']);
        } catch (BookingRuleViolation $e) {
            return back()->withInput()->withErrors(['booking' => $e->userMessage()]);
        } catch (PaymentFailedException) {
            // The whole booking was rolled back; nothing was charged or saved.
            return back()->withInput()->withErrors(['booking' => __('booking::messages.errors.payment_failed')]);
        }

        return redirect()->route('bookings.show', $booking)->with('status', __('booking::messages.requested'));
    }

    public function index(Request $request): View
    {
        return view('booking::index', [
            'bookings' => Booking::query()->with(['experience', 'host.hostProfile', 'payment'])
                ->where('tourist_id', $request->user()->id)->latest('date')->latest('id')->get(),
        ]);
    }

    public function show(Request $request, Booking $booking, AlternativeHostFinder $finder): View
    {
        $this->authorize('view', $booking);
        $booking->load(['experience', 'payment', 'tourist', 'host.hostProfile', 'canceller']);

        // After a late host cancellation the tourist is offered other verified hosts in the same city.
        $showAlternatives = $booking->status === BookingStatus::Cancelled
            && $booking->cancelled_by === $booking->host_id
            && $request->user()->id === $booking->tourist_id
            && $booking->cancelled_at->diffInHours($this->bookings->startsAt($booking), false) < 24;

        return view('booking::show', [
            'booking' => $booking,
            'viewerIsHost' => $request->user()->id === $booking->host_id,
            'alternatives' => $showAlternatives
                ? HostProfile::query()->whereIn('user_id', $finder->find($booking)->take((int) config('ahla.alternatives_max')))->get()
                : collect(),
        ]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('view', $booking);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $result = $this->bookings->cancel($booking, $request->user(), $data['reason'] ?? null);
        } catch (BookingRuleViolation $e) {
            return back()->withErrors(['booking' => $e->userMessage()]);
        } catch (PaymentFailedException) {
            return back()->withErrors(['booking' => __('booking::messages.errors.refund_failed')]);
        }

        return redirect()->route('bookings.show', $booking)
            ->with('status', __('booking::messages.cancelled', ['amount' => $result['refund']]));
    }

    /** Only published experiences of verified hosts exist for tourists. */
    private function assertBookable(Experience $experience): void
    {
        abort_unless(
            $experience->published && $experience->hostProfile->status === HostStatus::Verified,
            404,
        );
    }
}
