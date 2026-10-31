<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Auth\Enums\Role;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\BlockedDate;
use App\Modules\Experience\Models\Experience;
use App\Modules\Messaging\Models\Message;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\HostProfile;
use App\Modules\Verification\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseZeroFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_and_catalog_factories_create_related_records(): void
    {
        $host = User::factory()->verifiedHost()->create();
        $pendingHost = User::factory()->pendingHost()->create();
        $rejectedHost = User::factory()->rejectedHost()->create();
        $suspendedHost = User::factory()->suspendedHost()->create();
        $admin = User::factory()->admin()->create();
        $tourist = User::factory()->create();
        $tourist->fill(['role' => Role::Admin])->save();
        $unverifiedProfile = HostProfile::factory()->create();
        $unverifiedProfile->fill(['status' => HostStatus::Verified])->save();
        $host->languages()->create(['language' => 'fr']);
        $host->interests()->create(['interest' => 'photography']);
        $experience = Experience::factory()->published()->create([
            'host_profile_id' => $host->hostProfile->id,
        ]);
        $availability = Availability::factory()->create([
            'host_profile_id' => $host->hostProfile->id,
        ]);
        $blockedDate = BlockedDate::factory()->create([
            'host_profile_id' => $host->hostProfile->id,
        ]);
        $verification = VerificationRequest::factory()->create([
            'host_profile_id' => $host->hostProfile->id,
        ]);

        $this->assertSame(Role::Host, $host->role);
        $this->assertSame(HostStatus::Verified, $host->hostProfile->status);
        $this->assertSame(HostStatus::Pending, $pendingHost->hostProfile->status);
        $this->assertSame(HostStatus::Rejected, $rejectedHost->hostProfile->status);
        $this->assertSame(HostStatus::Suspended, $suspendedHost->hostProfile->status);
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertSame(Role::Tourist, $tourist->fresh()->role);
        $this->assertSame(HostStatus::Pending, $unverifiedProfile->fresh()->status);
        $this->assertSame($host->id, $experience->hostProfile->user_id);
        $this->assertNotEmpty($experience->interests);
        $this->assertSame($host->id, $availability->hostProfile->user_id);
        $this->assertSame($host->id, $blockedDate->hostProfile->user_id);
        $this->assertSame($host->id, $verification->hostProfile->user_id);
        $this->assertDatabaseHas('user_languages', ['user_id' => $host->id, 'language' => 'fr']);
        $this->assertDatabaseHas('user_interests', ['user_id' => $host->id, 'interest' => 'photography']);
        $this->assertTrue($experience->published);
    }

    public function test_booking_payment_message_notification_and_audit_schema_is_installed(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);
        $message = Message::factory()->create(['booking_id' => $booking->id]);
        $audit = AuditLog::factory()->create(['entity_id' => $booking->id]);

        $this->assertSame(BookingStatus::Requested, $booking->status);
        $this->assertSame($booking->experience->hostProfile->user_id, $booking->host_id);
        $this->assertSame(PaymentStatus::Authorised, $payment->status);
        $priceInThousandths = (int) str_replace('.', '', (string) $booking->experience->price_per_person);
        $totalInThousandths = $priceInThousandths * $booking->group_size;
        $expectedAmount = intdiv($totalInThousandths, 1000).'.'.str_pad(
            (string) ($totalInThousandths % 1000),
            3,
            '0',
            STR_PAD_LEFT,
        );
        $this->assertSame($expectedAmount, $payment->amount);
        $this->assertSame($booking->tourist_id, $message->sender_id);
        $this->assertSame($booking->host_id, $message->recipient_id);
        $this->assertSame('booking', $audit->entity);
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasColumns('notifications', ['id', 'type', 'notifiable_id', 'data', 'read_at']));
        $this->assertTrue(Schema::hasColumns('audit_log', ['entity', 'entity_id', 'old_status', 'new_status']));
        $this->assertTrue(Schema::hasIndex('host_profiles', 'host_profiles_city_status_index'));
        $this->assertTrue(Schema::hasIndex('experiences', ['price_per_person']));
        $this->assertTrue(Schema::hasIndex('user_languages', ['language']));
    }

    public function test_ratings_remain_nullable_until_reviews_are_in_scope(): void
    {
        $host = HostProfile::factory()->verified()->create(['rating_avg' => null]);

        $this->assertNull($host->fresh()->rating_avg);
    }

    public function test_lifecycle_statuses_are_not_mass_assignable(): void
    {
        $booking = Booking::factory()->create();
        $booking->fill(['status' => BookingStatus::Cancelled])->save();

        $payment = Payment::factory()->create(['booking_id' => $booking->id]);
        $payment->fill(['status' => PaymentStatus::Refunded])->save();

        $verification = VerificationRequest::factory()->create([
            'host_profile_id' => $booking->experience->host_profile_id,
        ]);
        $verification->fill(['status' => 'decided'])->save();

        $this->assertSame(BookingStatus::Requested, $booking->fresh()->status);
        $this->assertSame(PaymentStatus::Authorised, $payment->fresh()->status);
        $this->assertSame('submitted', $verification->fresh()->status->value);
    }
}
