<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * "Something worth remembering happened" (profile saved, experience published ...). Any module can
 * fire it; the Admin module writes it to the audit timeline. Never put secrets or message text in $detail.
 */
final readonly class ActivityRecorded
{
    use Dispatchable;

    public function __construct(
        public string $entity,
        public string $action,
        public ?int $actorId,
        public ?int $subjectId = null,
        public ?string $detail = null,
    ) {}
}
