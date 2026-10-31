<?php

namespace App\Modules\Booking\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Services\HostDashboard;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function __invoke(Request $request, HostDashboard $dashboard): View
    {
        $unreadOnly = $request->boolean('unread');

        return view('booking::host.inbox', [
            'threads' => $dashboard->inbox($request->user(), $unreadOnly),
            'unreadOnly' => $unreadOnly,
            'totalUnread' => $dashboard->inbox($request->user())->sum('unread'),
        ]);
    }
}
