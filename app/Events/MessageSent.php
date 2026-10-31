<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class MessageSent
{
    use Dispatchable;

    public function __construct(
        public int $messageId,
        public int $bookingId,
        public int $senderId,
        public int $recipientId,
    ) {}
}
