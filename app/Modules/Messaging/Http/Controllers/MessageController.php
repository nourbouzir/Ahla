<?php

namespace App\Modules\Messaging\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Models\Booking;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function __construct(private readonly MessagingService $messaging) {}

    public function show(Request $request, Booking $booking): View
    {
        $this->authorize('chat', $booking);
        $booking->load(['experience', 'tourist', 'host.hostProfile']);
        $user = $request->user();
        $messages = $this->messaging->conversation($booking, $user);

        return view('messaging::chat', [
            'booking' => $booking,
            'messages' => $messages->map(fn ($m) => $this->messaging->presentation($m, $user)),
            'other' => $user->id === $booking->tourist_id ? ($booking->host->hostProfile?->display_name ?? $booking->host->name) : $booking->tourist->name,
            'canSend' => $this->messaging->canSend($user, $booking),
            'lastId' => $messages->max('id') ?? 0,
        ]);
    }

    /** Polling endpoint: messages after ?after=<id>. */
    public function index(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('chat', $booking);
        $after = max(0, (int) $request->query('after', 0));
        $user = $request->user();

        return response()->json([
            'messages' => $this->messaging->conversation($booking, $user, $after)->map(fn ($m) => $this->messaging->presentation($m, $user))->values(),
        ]);
    }

    public function store(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $this->authorize('chat', $booking);

        if (! $this->messaging->canSend($request->user(), $booking)) {
            abort(403, __('messaging::messages.closed'));
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $message = $this->messaging->send($booking, $request->user(), trim($data['body']));

        return $request->expectsJson()
            ? response()->json(['id' => $message->id], 201)
            : redirect()->route('messages.show', $booking);
    }
}
