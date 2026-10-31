<?php

namespace App\Modules\Booking\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Services\HostDashboard;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CalendarController extends Controller
{
    public function __invoke(Request $request, HostDashboard $dashboard): View
    {
        try {
            $month = CarbonImmutable::createFromFormat('!Y-m', (string) $request->query('month', now()->format('Y-m')));
        } catch (Throwable) {
            $month = CarbonImmutable::today()->startOfMonth();
        }

        // Stay inside a sensible window so nobody pages through the year 9999.
        if (! $month->between(now()->subYears(2)->startOfMonth(), now()->addYears(2)->endOfMonth())) {
            $month = CarbonImmutable::today()->startOfMonth();
        }

        return view('booking::host.calendar', [
            'month' => $month,
            'prev' => $month->subMonth()->format('Y-m'),
            'next' => $month->addMonth()->format('Y-m'),
        ] + $dashboard->month($request->user(), $month));
    }
}
