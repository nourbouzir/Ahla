<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Exceptions\InvalidHostTransition;
use App\Modules\Verification\Models\VerificationRequest;
use App\Modules\Verification\Services\HostStatusService;
use App\Modules\Verification\Services\VerifiedHostEligibility;
use App\Modules\Verification\States\HostState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_state_table_allows_only_documented_moves(): void
    {
        $allowed = [
            'pending' => ['verified', 'rejected'],
            'verified' => ['suspended'],
            'rejected' => ['pending'],
            'suspended' => ['verified', 'rejected'],
        ];

        foreach (HostStatus::cases() as $from) {
            foreach (HostStatus::cases() as $to) {
                $this->assertSame(
                    in_array($to->value, $allowed[$from->value], true),
                    HostState::for($from)->canTransitionTo($to),
                    "{$from->value} -> {$to->value}",
                );
            }
        }

        $this->assertTrue(HostState::for(HostStatus::Verified)->isBookable());
        $this->assertFalse(HostState::for(HostStatus::Suspended)->isBookable());
    }

    public function test_only_a_verified_host_is_bookable(): void
    {
        $eligibility = new VerifiedHostEligibility;

        foreach (['verifiedHost' => true, 'pendingHost' => false, 'rejectedHost' => false, 'suspendedHost' => false] as $state => $expected) {
            $this->assertSame($expected, $eligibility->isBookable(User::factory()->{$state}()->create()->id), $state);
        }

        $this->assertFalse($eligibility->isBookable(User::factory()->tourist()->create()->id));
        $this->assertFalse($eligibility->isBookable(999999));
    }

    public function test_status_service_requires_reason_and_writes_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = User::factory()->verifiedHost()->create()->hostProfile;
        $service = app(HostStatusService::class);

        try {
            $service->transition($profile, HostStatus::Suspended, $admin, '');
            $this->fail('reason is required');
        } catch (InvalidHostTransition) {
            $this->assertSame(HostStatus::Verified, $profile->fresh()->status);
        }

        $service->transition($profile, HostStatus::Suspended, $admin, 'complaint');
        $log = AuditLog::where('entity', 'host')->latest('id')->first();
        $this->assertSame(['verified', 'suspended', $admin->id, 'complaint'], [$log->old_status, $log->new_status, $log->actor_id, $log->reason]);

        $this->expectException(InvalidHostTransition::class);
        $service->transition($profile->fresh(), HostStatus::Pending, $admin);
    }

    public function test_host_uploads_evidence_encrypted_and_only_once(): void
    {
        $host = User::factory()->pendingHost()->create();
        $file = UploadedFile::fake()->image('id.jpg');

        $this->actingAs($host)->post('/host/verification', ['evidence' => $file])->assertRedirect('/host/verification');

        $request = VerificationRequest::firstOrFail();
        Storage::disk('local')->assertExists($request->evidence_path);
        $this->assertStringEndsWith('.enc', $request->evidence_path);
        $this->assertStringNotContainsString('JFIF', Storage::disk('local')->get($request->evidence_path));

        // A second application while one is open is refused.
        $this->actingAs($host)->post('/host/verification', ['evidence' => UploadedFile::fake()->image('id2.jpg')])
            ->assertSessionHasErrors('evidence');
        $this->assertSame(1, VerificationRequest::count());
    }

    public function test_evidence_must_be_an_image_or_pdf_under_5mb(): void
    {
        $host = User::factory()->pendingHost()->create();

        $this->actingAs($host)->post('/host/verification', ['evidence' => UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('evidence');
        $this->actingAs($host)->post('/host/verification', ['evidence' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('evidence');
        $this->assertSame(0, VerificationRequest::count());
    }

    public function test_tourist_cannot_apply_or_open_admin_pages(): void
    {
        $tourist = User::factory()->tourist()->create();

        $this->actingAs($tourist)->get('/host/verification')->assertForbidden();
        $this->actingAs($tourist)->get('/admin/verifications')->assertForbidden();
        $this->actingAs(User::factory()->verifiedHost()->create())->get('/admin/verifications')->assertForbidden();
    }

    public function test_admin_full_review_flow_verifies_the_host(): void
    {
        $admin = User::factory()->admin()->create();
        $host = User::factory()->pendingHost()->create();
        $this->actingAs($host)->post('/host/verification', ['evidence' => UploadedFile::fake()->image('id.png')]);
        $request = VerificationRequest::firstOrFail();

        $this->actingAs($admin)->get('/admin/verifications')->assertOk()->assertSee($host->hostProfile->display_name);
        $this->get("/admin/verifications/{$request->id}")->assertOk();

        // Approving before the call is scheduled is refused.
        $this->post("/admin/verifications/{$request->id}/decide", ['decision' => 'approve'])->assertSessionHasErrors('decision');
        $this->assertSame(HostStatus::Pending, $host->hostProfile->fresh()->status);

        $this->post("/admin/verifications/{$request->id}/schedule", [
            'video_call_at' => now()->addDay()->format('Y-m-d\TH:i'), 'video_call_link' => 'https://meet.jit.si/ahla-test',
        ])->assertSessionHasNoErrors();
        $this->actingAs($host)->get('/host/verification')->assertSee('meet.jit.si');

        // The evidence downloads decrypted and is never inlined.
        $response = $this->actingAs($admin)->get("/admin/verifications/{$request->id}/evidence");
        $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $this->post("/admin/verifications/{$request->id}/decide", ['decision' => 'approve'])->assertRedirect('/admin/verifications');
        $this->assertSame(HostStatus::Verified, $host->hostProfile->fresh()->status);
        $this->assertSame('approved', $request->fresh()->decision);
        $this->assertSame(['pending->verified'], AuditLog::where('entity', 'host')->get()->map(fn ($l) => "{$l->old_status}->{$l->new_status}")->all());
    }

    public function test_rejection_needs_a_reason_and_host_can_reapply(): void
    {
        $admin = User::factory()->admin()->create();
        $host = User::factory()->pendingHost()->create();
        $this->actingAs($host)->post('/host/verification', ['evidence' => UploadedFile::fake()->image('id.png')]);
        $request = VerificationRequest::firstOrFail();

        $this->actingAs($admin)->post("/admin/verifications/{$request->id}/decide", ['decision' => 'reject'])->assertSessionHasErrors('reason');
        $this->post("/admin/verifications/{$request->id}/decide", ['decision' => 'reject', 'reason' => 'Blurry photo']);
        $this->assertSame(HostStatus::Rejected, $host->hostProfile->fresh()->status);

        $this->actingAs($host)->get('/host/verification')->assertSee('Blurry photo');
        $this->actingAs($host)->post('/host/verification', ['evidence' => UploadedFile::fake()->image('id2.png')])->assertSessionHasNoErrors();
        $this->assertSame(HostStatus::Pending, $host->hostProfile->fresh()->status);
        $this->assertSame(2, VerificationRequest::count());
    }

    public function test_admin_can_suspend_and_reinstate(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = User::factory()->verifiedHost()->create()->hostProfile;

        $this->actingAs($admin)->post("/admin/hosts/{$profile->id}/status", ['status' => 'suspended'])->assertSessionHasErrors('reason');
        $this->post("/admin/hosts/{$profile->id}/status", ['status' => 'suspended', 'reason' => 'Safety report']);
        $this->assertSame(HostStatus::Suspended, $profile->fresh()->status);

        $this->post("/admin/hosts/{$profile->id}/status", ['status' => 'verified']);
        $this->assertSame(HostStatus::Verified, $profile->fresh()->status);
        $this->get('/admin/audit')->assertOk()->assertSee('Safety report');
    }
}
