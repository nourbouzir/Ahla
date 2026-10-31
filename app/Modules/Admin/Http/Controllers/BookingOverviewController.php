<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only list of bookings with their payment status for admins (FR-16). */
class BookingOverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = BookingStatus::tryFrom((string) $request->query('status'));

        return view('admin::bookings', [
            'bookings' => Booking::query()->with(['experience', 'tourist', 'host.hostProfile', 'payment'])
                ->when($status, fn ($q) => $q->where('status', $status->value))
                ->latest('id')->paginate(30)->withQueryString(),
            'status' => $status?->value,
        ]);
    }
}
