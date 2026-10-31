<?php

namespace App\Jobs;

use App\Contracts\Translation\Translator;
use App\Modules\Messaging\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Translates one chat message on the queue. Only the message text and the two
 * language codes go to the provider - no names, no contact details (privacy).
 * If the provider keeps failing the message simply stays untranslated: the
 * original is always shown, nothing is ever lost.
 */
class TranslateMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @return list<int> seconds to wait between attempts */
    public function backoff(): array
    {
        return [5, 30];
    }

    public function __construct(public int $messageId) {}

    public function handle(Translator $translator): void
    {
        $message = Message::find($this->messageId);

        if ($message === null || $message->translated_text !== null || $message->translated_language === null) {
            return;
        }

        $message->forceFill([
            'translated_text' => $translator->translate($message->original_text, $message->language, $message->translated_language),
        ])->save();
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
