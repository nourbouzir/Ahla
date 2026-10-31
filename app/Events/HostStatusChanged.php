<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class HostStatusChanged
{
    use Dispatchable;

    public function __construct(
        public int $hostProfileId,
        public string $oldStatus,
        public string $newStatus,
        public ?int $actorId,
    ) {}
}
