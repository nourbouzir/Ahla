<?php

namespace App\Modules\Notification\Notifications;

use Illuminate\Notifications\Notification;

/**
 * The in-app notification. Only the kind and a few plain values are stored;
 * the sentence is built when it is read, in the reader's language
 * (notification::messages.<kind>), so a user who changes language sees old
 * notifications in the new one. Stored synchronously so it can never be lost
 * because of a mail problem.
 */
class EventNotification extends Notification
{
    /** @param array<string, scalar> $vars values for the sentence (experience, name, amount ...) */
    public function __construct(public string $kind, public ?int $bookingId, public string $url, public array $vars = []) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'booking_id' => $this->bookingId, 'url' => $this->url, 'vars' => $this->vars];
    }
}
