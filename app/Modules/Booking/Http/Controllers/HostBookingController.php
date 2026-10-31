<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\BookingRuleViolation;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HostBookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function index(Request $request): View
    {
        $all = Booking::query()->with(['experience', 'tourist'])
            ->where('host_id', $request->user()->id)->latest('date')->latest('id')->get();

        return view('booking::host-index', [
            'requests' => $all->where('status', BookingStatus::Requested),
            'others' => $all->where('status', '!=', BookingStatus::Requested),
        ]);
    }

    public function accept(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('view', $booking);

        try {
            $this->bookings->accept($booking, $request->user());
        } catch (BookingRuleViolation $e) {
            return back()->withErrors(['booking' => $e->userMessage()]);
        } catch (PaymentFailedException) {
            // Not captured = not confirmed. The booking stays requested so the host can retry.
            return back()->withErrors(['booking' => __('booking::messages.errors.payment_failed')]);
        }

        return back()->with('status', __('booking::messages.accepted'));
    }

    public function decline(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('view', $booking);

        try {
            $this->bookings->decline($booking, $request->user(), $request->input('reason'));
        } catch (BookingRuleViolation $e) {
            return back()->withErrors(['booking' => $e->userMessage()]);
        } catch (PaymentFailedException) {
            return back()->withErrors(['booking' => __('booking::messages.errors.payment_failed')]);
        }

        return back()->with('status', __('booking::messages.declined'));
    }
}
