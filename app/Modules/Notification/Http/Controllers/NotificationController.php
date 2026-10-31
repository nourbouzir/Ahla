<?php

namespace App\Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notification::index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    /** Opens the notification's page and marks it read. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Only our own paths are followed, never an address stored in data.
        $path = parse_url((string) ($notification->data['url'] ?? ''), PHP_URL_PATH) ?: '/';

        return redirect($path);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function preferences(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['email_notifications' => $request->boolean('email_notifications')])->save();

        return back()->with('status', __('notification::messages.saved'));
    }
}
