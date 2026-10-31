<?php

namespace App\Modules\Notification\Services;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRequested;
use App\Events\BookingStatusChanged;
use App\Events\MessageSent;
use App\Events\PaymentStatusChanged;
use App\Events\RefundRequested;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Notification\Notifications\EventMailNotification;
use App\Modules\Notification\Notifications\EventNotification;
use Throwable;

/**
 * Turns domain events (ADR 0004 scope: booking, payment, cancellation, refund,
 * message) into notifications for the right person. Every handler swallows and
 * reports its own errors: a notification problem must never break the booking
 * or payment that caused the event.
 */
class NotificationDispatcher
{
    public function bookingRequested(BookingRequested $e): void
    {
        $this->guard(fn () => $this->notify($e->hostId, 'booking_requested', $e->bookingId));
    }

    public function bookingConfirmed(BookingConfirmed $e): void
    {
        $this->guard(fn () => $this->notify($e->touristId, 'booking_confirmed', $e->bookingId));
    }

    public function bookingStatusChanged(BookingStatusChanged $e): void
    {
        $this->guard(function () use ($e) {
            $booking = Booking::find($e->bookingId);

            match ($e->newStatus) {
                'declined' => $booking && $this->notify($booking->tourist_id, 'booking_declined', $booking->id),
                'expired' => $booking && $this->both($booking, 'booking_expired'),
                default => null,
            };
        });
    }

    public function bookingCancelled(BookingCancelled $e): void
    {
        $this->guard(function () use ($e) {
            $booking = Booking::find($e->bookingId);

            if ($booking) {
                // The person who did not cancel is told.
                $this->notify($e->cancelledBy === $booking->host_id ? $booking->tourist_id : $booking->host_id, 'booking_cancelled', $booking->id);
            }
        });
    }

    public function paymentChanged(PaymentStatusChanged $e): void
    {
        $this->guard(function () use ($e) {
            $booking = Booking::find($e->bookingId);
            $kind = ['captured' => 'payment_captured', 'released' => 'payment_released', 'failed' => 'payment_failed'][$e->newStatus] ?? null;

            if ($booking && $kind) {
                $this->notify($booking->tourist_id, $kind, $booking->id, ['amount' => number_format((float) $booking->displayed_price, 3)]);
            }
        });
    }

    public function refundRequested(RefundRequested $e): void
    {
        $this->guard(function () use ($e) {
            $booking = Booking::find($e->bookingId);

            if ($booking) {
                $this->notify($booking->tourist_id, 'refund_issued', $booking->id, ['amount' => $e->amount]);
            }
        });
    }

    public function messageSent(MessageSent $e): void
    {
        $this->guard(function () use ($e) {
            $sender = User::find($e->senderId);
            $booking = Booking::find($e->bookingId);

            if ($sender && $booking) {
                $this->notify($e->recipientId, 'message_received', $booking->id, [], route('messages.show', $booking));
            }
        });
    }

    private function both(Booking $booking, string $kind): void
    {
        $this->notify($booking->tourist_id, $kind, $booking->id);
        $this->notify($booking->host_id, $kind, $booking->id);
    }

    /** @param array<string, scalar> $extra */
    private function notify(int $userId, string $kind, int $bookingId, array $extra = [], ?string $url = null): void
    {
        $user = User::find($userId);
        $booking = Booking::with(['experience', 'tourist', 'host.hostProfile'])->find($bookingId);

        if ($user === null || $booking === null) {
            return;
        }

        $other = $user->id === $booking->host_id ? $booking->tourist->name : ($booking->host->hostProfile?->display_name ?? $booking->host->name);
        $vars = $extra + [
            'experience' => $booking->experience->title,
            'name' => $other,
            'date' => $booking->date->format('Y-m-d').' '.substr($booking->start_time, 0, 5),
        ];
        $url ??= route('bookings.show', $booking);

        // In-app first and synchronous, then the queued e-mail.
        $user->notify(new EventNotification($kind, $bookingId, $url, $vars));
        $user->notify(new EventMailNotification($kind, $url, $vars));
    }

    private function guard(callable $work): void
    {
        try {
            $work();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
