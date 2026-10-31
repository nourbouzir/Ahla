<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use Throwable;

/**
 * Writes everyday actions (logins, profile edits, messages sent ...) into the same audit log as the
 * status changes, so the admin sees one timeline. It never stores message text or passwords, and a
 * failure to log can never break the action itself.
 *
 * entity = what the action was about (session, profile, experience, availability, message)
 * new_status = the action word (logged_in, updated, sent ...), translated for display in admin::dash.activity
 */
class ActivityLogger
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function record(string $entity, string $action, ?User $actor, ?int $subjectId = null, ?string $detail = null): void
    {
        try {
            $this->audit->record($entity, $subjectId ?? $actor?->id ?? 0, null, $action, $actor, $detail);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
