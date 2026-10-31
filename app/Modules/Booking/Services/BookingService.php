<?php

namespace App\Modules\Booking\Services;

use App\Contracts\Bookings\HostSchedule;
use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRequested;
use App\Events\BookingStatusChanged;
use App\Models\User;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\BookingRuleViolation;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\States\BookingState;
use App\Modules\Experience\Models\Experience;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\RefundService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * THE place for booking rules: eligibility, availability, no overlap, at most
 * two confirmed bookings a day, status changes and their payment steps.
 * Controllers and jobs call these methods and nothing else.
 *
 * Concurrency (NFR-14): request, accept and cancel run in one transaction that
 * first locks the host's user row (SELECT ... FOR UPDATE). Everything that can
 * book the same host therefore queues up behind that lock, re-counts the
 * host's confirmed bookings and only then writes. Never check-then-insert
 * outside this transaction.
 */
class BookingService implements HostSchedule
{
    /** At most this many confirmed bookings per host per calendar day (FR-13). */
    public const DAILY_LIMIT = 2;

    public function __construct(
        private readonly HostEligibility $eligibility,
        private readonly AvailabilityChecker $availability,
        private readonly AlternativeHostFinder $alternatives,
        private readonly PaymentService $payments,
        private readonly RefundService $refunds,
        private readonly AuditLogger $audit,
    ) {}

    /** Tourist asks for a booking. The amount is authorised, not yet charged. */
    public function request(User $tourist, Experience $experience, string $date, string $startTime, int $groupSize): Booking
    {
        $experience->loadMissing('hostProfile');
        $hostId = $experience->hostProfile->user_id;

        if (! $experience->published) {
            throw new BookingRuleViolation('experience_unavailable');
        }

        if ($groupSize < 1 || $groupSize > $experience->max_group_size) {
            throw new BookingRuleViolation('group_size');
        }

        [$start, $end] = $this->interval($date, $startTime, (int) $experience->duration_hours);

        return DB::transaction(function () use ($tourist, $experience, $hostId, $date, $start, $end, $groupSize) {
            $this->lockHost($hostId);

            if ($hostId === $tourist->id || ! $this->eligibility->isBookable($hostId)) {
                throw new BookingRuleViolation('host_not_bookable');
            }

            if (! $this->availability->isSlotAvailable($hostId, $date, $start, $end)) {
                throw new BookingRuleViolation('outside_availability');
            }

            $this->assertSlotFree($hostId, $date, $start, $end, null);

            $booking = new Booking([
                'tourist_id' => $tourist->id,
                'host_id' => $hostId,
                'experience_id' => $experience->id,
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'group_size' => $groupSize,
                'location' => $experience->meeting_point,
                'displayed_price' => bcmul((string) $experience->price_per_person, (string) $groupSize, 3),
            ]);
            $booking->forceFill([
                'status' => BookingStatus::Requested,
                'expires_at' => now()->addHours((int) config('ahla.request_ttl_hours')),
            ])->save();

            // A failed or timed-out authorisation throws here and rolls the whole booking back.
            $this->payments->authorise($booking, $tourist);

            $this->audit->record('booking', $booking->id, null, BookingStatus::Requested, $tourist, 'requested');
            BookingRequested::dispatch($booking->id, $tourist->id, $hostId);
            BookingStatusChanged::dispatch($booking->id, '', BookingStatus::Requested->value, $tourist->id);

            return $booking;
        });
    }

    /** Host accepts: rules are re-checked under the lock, then the payment is captured. */
    public function accept(Booking $booking, User $host): Booking
    {
        return DB::transaction(function () use ($booking, $host) {
            $this->lockHost($booking->host_id);
            $locked = $this->lockBooking($booking);
            $this->assertActor($locked, $host->id === $locked->host_id);
            $this->assertCan($locked, BookingStatus::Confirmed);

            if ($locked->expires_at !== null && $locked->expires_at->isPast()) {
                throw new BookingRuleViolation('request_expired');
            }

            if (! $this->eligibility->isBookable($locked->host_id)) {
                throw new BookingRuleViolation('host_not_bookable');
            }

            $this->assertSlotFree($locked->host_id, $locked->date->toDateString(), $locked->start_time, $locked->end_time, $locked->id);

            // Capture first: not captured means not confirmed (FR-15). A throw rolls everything back.
            $this->payments->capture($locked->payment, $host);

            return $this->move($locked, BookingStatus::Confirmed, $host, 'accepted and paid');
        });
    }

    public function decline(Booking $booking, User $host, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $host, $reason) {
            $locked = $this->lockBooking($booking);
            $this->assertActor($locked, $host->id === $locked->host_id);
            $this->assertCan($locked, BookingStatus::Declined);

            $this->payments->release($locked->payment, $host, 'request declined');

            return $this->move($locked, BookingStatus::Declined, $host, $reason ?? 'declined');
        });
    }

    /** Called by the scheduler for requests older than the 12 h window. Returns false if nothing to do. */
    public function expire(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $locked = $this->lockBooking($booking);

            if ($locked->status !== BookingStatus::Requested || $locked->expires_at === null || $locked->expires_at->isFuture()) {
                return false;
            }

            $this->payments->release($locked->payment, null, 'request expired');
            $this->move($locked, BookingStatus::Expired, null, 'not answered in time');

            return true;
        });
    }

    /** @return array{booking: Booking, refund: string, refund_pending: bool, alternatives: Collection<int, int>} */
    public function cancel(Booking $booking, User $actor, ?string $reason = null): array
    {
        return DB::transaction(function () use ($booking, $actor, $reason) {
            $this->lockHost($booking->host_id);
            $locked = $this->lockBooking($booking);
            $this->assertActor($locked, in_array($actor->id, [$locked->tourist_id, $locked->host_id], true));
            $this->assertCan($locked, BookingStatus::Cancelled);

            if ($this->startsAt($locked)->isPast()) {
                throw new BookingRuleViolation('already_started');
            }

            $locked->forceFill([
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => $reason,
            ]);
            // A refund that fails never undoes the cancellation: the booking stays cancelled and
            // the ReconcilePayments job retries the refund until it goes through.
            $refundPending = false;

            try {
                $refund = $this->refunds->refundCancelledBooking($locked->load('payment'), $actor);
            } catch (PaymentFailedException $e) {
                $refund = '0.000';
                $refundPending = true;
                report($e);
            }

            $this->move($locked, BookingStatus::Cancelled, $actor, $reason ?? 'cancelled');

            // A host cancelling less than 24 h before the start also gets up to 3 alternatives (FR-17).
            $late = $actor->id === $locked->host_id && now()->diffInHours($this->startsAt($locked), false) < 24;
            $alternatives = $late
                ? $this->alternatives->find($locked)->take((int) config('ahla.alternatives_max'))->values()
                : collect();

            BookingCancelled::dispatch($locked->id, $actor->id, $reason);

            return ['booking' => $locked, 'refund' => $refund, 'refund_pending' => $refundPending, 'alternatives' => $alternatives];
        });
    }

    /** Scheduler: a confirmed booking is completed 2 hours after it ends. */
    public function complete(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $locked = $this->lockBooking($booking);

            if ($locked->status !== BookingStatus::Confirmed || $this->endsAt($locked)->addHours(2)->isFuture()) {
                return false;
            }

            $this->move($locked, BookingStatus::Completed, null, 'auto-completed');

            return true;
        });
    }

    /** Read-only answer for other modules (e.g. the alternative-host finder); the real check still happens under the lock. */
    public function isFree(int $hostId, string $date, string $start, string $end): bool
    {
        try {
            $this->assertSlotFree($hostId, $date, $start, $end, null);

            return true;
        } catch (BookingRuleViolation) {
            return false;
        }
    }

    public function startsAt(Booking $booking): CarbonImmutable
    {
        return CarbonImmutable::parse($booking->date->toDateString().' '.$booking->start_time);
    }

    public function endsAt(Booking $booking): CarbonImmutable
    {
        return CarbonImmutable::parse($booking->date->toDateString().' '.$booking->end_time);
    }

    /**
     * Work out [start, end) as H:i:s and refuse anything in the past or crossing midnight.
     *
     * @return array{0: string, 1: string}
     */
    private function interval(string $date, string $startTime, int $hours): array
    {
        $start = CarbonImmutable::parse("{$date} {$startTime}");
        $end = $start->addHours($hours);

        if ($start->isPast()) {
            throw new BookingRuleViolation('in_the_past');
        }

        // No booking crosses midnight (ADR 0004), so "calendar day" is unambiguous.
        if (! $end->isSameDay($start)) {
            throw new BookingRuleViolation('crosses_midnight');
        }

        return [$start->format('H:i:s'), $end->format('H:i:s')];
    }

    /** Half-open overlap [start, end) among CONFIRMED bookings, plus the 2-per-day limit. */
    private function assertSlotFree(int $hostId, string $date, string $start, string $end, ?int $exceptId): void
    {
        $confirmed = Booking::query()
            ->where('host_id', $hostId)
            ->whereDate('date', $date)
            ->where('status', BookingStatus::Confirmed->value)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId));

        if ((clone $confirmed)->count() >= self::DAILY_LIMIT) {
            throw new BookingRuleViolation('daily_limit');
        }

        // Touching end-to-start is fine: existing.start < new.end AND existing.end > new.start.
        if ($confirmed->where('start_time', '<', $end)->where('end_time', '>', $start)->exists()) {
            throw new BookingRuleViolation('overlap');
        }
    }

    private function lockHost(int $hostId): void
    {
        User::query()->whereKey($hostId)->lockForUpdate()->first();
    }

    private function lockBooking(Booking $booking): Booking
    {
        return Booking::query()->with('payment')->whereKey($booking->id)->lockForUpdate()->firstOrFail();
    }

    private function assertActor(Booking $booking, bool $allowed): void
    {
        if (! $allowed) {
            throw new BookingRuleViolation('forbidden');
        }
    }

    private function assertCan(Booking $booking, BookingStatus $to): void
    {
        if (! BookingState::for($booking->status)->canTransitionTo($to)) {
            throw new BookingRuleViolation('invalid_state');
        }
    }

    private function move(Booking $booking, BookingStatus $to, ?User $actor, string $reason): Booking
    {
        $from = $booking->status;
        $booking->forceFill(['status' => $to])->save();
        $this->audit->record('booking', $booking->id, $from, $to, $actor, $reason);
        BookingStatusChanged::dispatch($booking->id, $from->value, $to->value, $actor?->id);

        if ($to === BookingStatus::Confirmed) {
            BookingConfirmed::dispatch($booking->id, $booking->tourist_id, $booking->host_id);
        }

        return $booking;
    }
}
