<?php

namespace App\Modules\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The e-mail copy. Queued with retries and kept separate from the in-app one:
 * if the mail provider is down the queue retries this job only, the in-app
 * notification is already stored and the original action was never affected.
 * The mail is written in the recipient's language (User::preferredLocale).
 */
class EventMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array<string, scalar> $vars */
    public function __construct(public string $kind, public string $url, public array $vars = []) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 300];
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $notifiable->email_notifications ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__("notification::messages.kinds.{$this->kind}.title", $this->vars))
            ->line(__("notification::messages.kinds.{$this->kind}.body", $this->vars))
            ->action(__('notification::messages.open'), $this->url);
    }
}
