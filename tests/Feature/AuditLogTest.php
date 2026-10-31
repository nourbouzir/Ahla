<?php

namespace Tests\Feature;

use App\Events\BookingStatusChanged;
use App\Events\HostStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Support\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_events_are_recorded_with_actor_and_timestamp(): void
    {
        $actor = User::factory()->admin()->create();

        event(new HostStatusChanged(11, 'pending', 'verified', $actor->id));
        event(new BookingStatusChanged(12, 'requested', 'confirmed', $actor->id));
        event(new PaymentStatusChanged(13, 12, 'authorised', 'captured'));

        $this->assertDatabaseHas('audit_log', [
            'entity' => 'host_profile',
            'entity_id' => 11,
            'old_status' => 'pending',
            'new_status' => 'verified',
            'actor_id' => $actor->id,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'entity' => 'booking',
            'entity_id' => 12,
            'old_status' => 'requested',
            'new_status' => 'confirmed',
            'actor_id' => $actor->id,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'entity' => 'payment',
            'entity_id' => 13,
            'old_status' => 'authorised',
            'new_status' => 'captured',
            'actor_id' => null,
        ]);

        $this->assertDatabaseCount('audit_log', 3);
        $this->assertNotNull(
            AuditLog::query()->firstOrFail()->created_at,
        );
    }

    public function test_audit_logger_persists_reason_and_actor(): void
    {
        $actor = User::factory()->create();

        $entry = app(AuditLogger::class)->record(
            'booking',
            20,
            'confirmed',
            'cancelled',
            $actor->id,
            'Host cancelled the experience.',
        );

        $this->assertSame($actor->id, $entry->actor_id);
        $this->assertSame('Host cancelled the experience.', $entry->reason);
    }
}
