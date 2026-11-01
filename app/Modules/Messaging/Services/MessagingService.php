<?php

namespace App\Modules\Messaging\Services;

use App\Contracts\Translation\Translator;
use App\Events\MessageSent;
use App\Jobs\TranslateMessage;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Messaging\Models\Message;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

/**
 * One conversation per booking, between that booking's tourist and host
 * (ADR 0004). It exists from the moment the booking is `requested` and stays
 * readable afterwards; only open bookings accept new messages.
 */
class MessagingService
{
    /** Bookings in these states can still receive messages. */
    private const OPEN = [BookingStatus::Requested, BookingStatus::Confirmed, BookingStatus::Completed];

    /**
     * Is a translation service actually set up? Without one (the default on a fresh install) no job is
     * queued and the reader gets a calm "translation unavailable, showing the original" status (NFR-20).
     */
    public function translationAvailable(): bool
    {
        return ! app(Translator::class) instanceof LibreTranslateTranslator || (string) config('ahla.translation.url') !== '';
    }

    public function isParticipant(User $user, Booking $booking): bool
    {
        return in_array($user->id, [$booking->tourist_id, $booking->host_id], true);
    }

    public function canSend(User $user, Booking $booking): bool
    {
        return $this->isParticipant($user, $booking) && in_array($booking->status, self::OPEN, true);
    }

    /** The original text is stored first and untouched; translation fills translated_text later, on the queue. */
    public function send(Booking $booking, User $sender, string $body): Message
    {
        if (! $this->canSend($sender, $booking)) {
            throw new InvalidArgumentException('This user cannot send messages on this booking.');
        }

        $recipient = $sender->id === $booking->tourist_id ? $booking->host : $booking->tourist;
        $from = $sender->preferred_language;
        $to = $recipient->preferred_language;

        $message = new Message([
            'booking_id' => $booking->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'original_text' => $body,
            'language' => $from,
            // Only set when a translation is actually needed.
            'translated_language' => $from !== $to && $this->translationAvailable() ? $to : null,
        ]);
        $message->save();

        MessageSent::dispatch($message->id, $booking->id, $sender->id, $recipient->id);

        if ($message->translated_language !== null) {
            // Translation never blocks sending: it runs on the queue and may fail without losing the message.
            try {
                TranslateMessage::dispatch($message->id);
            } catch (Throwable $e) {
                // With a synchronous queue a provider error would surface here; the message is already saved.
                report($e);
            }
        }

        return $message;
    }

    /**
     * Messages of the conversation after $afterId, oldest first. The viewer's
     * unread incoming messages are marked as read.
     *
     * @return Collection<int, Message>
     */
    public function conversation(Booking $booking, User $viewer, int $afterId = 0): Collection
    {
        $messages = Message::query()->with('sender:id,name,avatar_path')
            ->where('booking_id', $booking->id)->where('id', '>', $afterId)
            ->orderBy('id')->get();

        Message::query()->where('booking_id', $booking->id)->where('recipient_id', $viewer->id)
            ->whereNull('read_at')->update(['read_at' => now()]);

        return $messages;
    }

    /** What the viewer reads: the translation of someone else's message when there is one, else the original. */
    public function presentation(Message $message, User $viewer): array
    {
        $incoming = $message->sender_id !== $viewer->id;
        $translated = $incoming && $message->translated_text !== null && $message->translated_text !== $message->original_text;
        $noService = $incoming && $message->translated_language === null && $message->language !== $viewer->preferred_language
            && ! $this->translationAvailable();
        $failed = $noService || ($incoming && $message->translated_language !== null && $message->translated_text === null
            && $message->created_at->lt(now()->subSeconds(30)));
        $pending = $incoming && $message->translated_language !== null && $message->translated_text === null && ! $failed;

        return [
            'id' => $message->id,
            'mine' => ! $incoming,
            'sender' => $message->sender->name,
            'avatar' => $message->sender->avatarUrl(),
            'initials' => $message->sender->initials(),
            'text' => $translated ? $message->translated_text : $message->original_text,
            'original' => $translated ? $message->original_text : null,
            'language' => $message->language,
            'status' => $translated ? 'translated' : ($failed ? 'unavailable' : ($pending ? 'pending' : 'original')),
            'at' => $message->created_at->format('Y-m-d H:i'),
        ];
    }
}
