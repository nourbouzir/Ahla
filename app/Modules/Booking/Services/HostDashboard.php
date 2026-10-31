<?php

namespace App\Modules\Booking\Services;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Messaging\Models\Message;
use App\Modules\Payment\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Read-only numbers for the guide's dashboard. It never changes a booking or a
 * payment: those stay in BookingService and PaymentService.
 *
 * "Earned" = money actually captured for the guide's bookings minus what was
 * refunded, counted in the month of the experience. There is no commission in v1 (ADR 0004).
 */
class HostDashboard
{
    /** Net money of one booking (0 unless the payment was captured or refunded). */
    private function net(Booking $booking): string
    {
        $payment = $booking->payment;

        if ($payment === null || ! in_array($payment->status, [PaymentStatus::Captured, PaymentStatus::Refunded], true)) {
            return '0.000';
        }

        return bcsub((string) $payment->amount, (string) $payment->refunded_amount, 3);
    }

    /** @return array<string, mixed> */
    public function overview(User $host): array
    {
        $today = CarbonImmutable::today();
        $pending = Booking::query()->with(['experience', 'tourist'])
            ->where('host_id', $host->id)->where('status', BookingStatus::Requested->value)
            ->orderBy('expires_at')->get();

        $upcoming = Booking::query()->with(['experience', 'tourist'])
            ->where('host_id', $host->id)->where('status', BookingStatus::Confirmed->value)
            ->whereDate('date', '>=', $today->toDateString())
            ->orderBy('date')->orderBy('start_time')->get();

        $monthRows = Booking::query()->with('payment')->where('host_id', $host->id)
            ->whereBetween('date', [$today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString()])->get();

        return [
            'pending' => $pending,
            'upcoming' => $upcoming,
            'next' => $upcoming->first(),
            'agenda' => $upcoming->filter(fn ($b) => $b->date->lte($today->addDays(6)))->groupBy(fn ($b) => $b->date->toDateString()),
            'earned_month' => $monthRows->reduce(fn ($sum, $b) => bcadd($sum, $this->net($b), 3), '0.000'),
            'clients' => Booking::query()->where('host_id', $host->id)->distinct('tourist_id')->count('tourist_id'),
            'unread' => Message::query()->where('recipient_id', $host->id)->whereNull('read_at')->count(),
            'completed' => Booking::query()->where('host_id', $host->id)->where('status', BookingStatus::Completed->value)->count(),
            'months' => $this->months($host, 6),
        ];
    }

    /** @return list<array{label: string, month: string, net: string}> oldest first */
    public function months(User $host, int $count): array
    {
        // First day of this month first: subtracting months from the 31st would overflow.
        $end = CarbonImmutable::today()->endOfMonth();
        $start = CarbonImmutable::today()->startOfMonth()->subMonths($count - 1);
        $buckets = [];

        for ($i = 0; $i < $count; $i++) {
            $buckets[$start->addMonths($i)->format('Y-m')] = '0.000';
        }

        Booking::query()->with('payment')->where('host_id', $host->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get()
            ->each(function (Booking $b) use (&$buckets) {
                $key = $b->date->format('Y-m');
                $buckets[$key] = bcadd($buckets[$key], $this->net($b), 3);
            });

        return collect($buckets)->map(fn ($net, $month) => ['month' => $month, 'label' => CarbonImmutable::parse("$month-01")->translatedFormat('M'), 'net' => $net])->values()->all();
    }

    /**
     * One row per client the guide has ever had a booking with.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function clients(User $host, ?string $search = null, string $sort = 'recent'): Collection
    {
        $bookings = Booking::query()->with(['payment', 'tourist'])->where('host_id', $host->id)->get();

        $rows = $bookings->groupBy('tourist_id')->map(function (Collection $group) {
            $tourist = $group->first()->tourist;
            $today = CarbonImmutable::today();
            $next = $group->where('status', BookingStatus::Confirmed)->filter(fn ($b) => $b->date->gte($today))->sortBy('date')->first();
            $open = $group->firstWhere('status', BookingStatus::Requested);
            $last = $group->sortByDesc(fn ($b) => $b->date->toDateString().$b->start_time)->first();

            return [
                'tourist' => $tourist,
                'bookings' => $group->count(),
                'completed' => $group->where('status', BookingStatus::Completed)->count(),
                'spent' => $group->reduce(fn ($sum, $b) => bcadd($sum, $this->net($b), 3), '0.000'),
                'last' => $last->date,
                'next' => $next?->date,
                // Where this client is in the journey with this guide.
                'stage' => $open ? 'awaiting' : ($next ? 'upcoming' : ($group->where('status', BookingStatus::Completed)->count() > 1 ? 'returning' : ($group->where('status', BookingStatus::Completed)->isNotEmpty() ? 'past' : 'closed'))),
            ];
        });

        if ($search !== null && trim($search) !== '') {
            $needle = mb_strtolower(trim($search));
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['tourist']->name), $needle));
        }

        return match ($sort) {
            'name' => $rows->sortBy(fn ($r) => mb_strtolower($r['tourist']->name))->values(),
            'spent' => $rows->sortByDesc(fn ($r) => (float) $r['spent'])->values(),
            'bookings' => $rows->sortByDesc('bookings')->values(),
            default => $rows->sortByDesc(fn ($r) => $r['last']->timestamp)->values(),
        };
    }

    /**
     * Conversations of the guide: every booking that has messages or is still open.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function inbox(User $host, bool $unreadOnly = false): Collection
    {
        $bookings = Booking::query()->with(['experience', 'tourist'])->where('host_id', $host->id)->get();
        $messages = Message::query()->whereIn('booking_id', $bookings->pluck('id'))->orderBy('id')->get()->groupBy('booking_id');

        $rows = $bookings->map(function (Booking $booking) use ($messages, $host) {
            $thread = $messages->get($booking->id, collect());
            $last = $thread->last();

            return [
                'booking' => $booking,
                'last' => $last,
                'unread' => $thread->where('recipient_id', $host->id)->whereNull('read_at')->count(),
                'count' => $thread->count(),
                'open' => in_array($booking->status, [BookingStatus::Requested, BookingStatus::Confirmed, BookingStatus::Completed], true),
                'at' => $last?->created_at ?? $booking->created_at,
            ];
        })->filter(fn ($r) => $r['count'] > 0 || in_array($r['booking']->status, [BookingStatus::Requested, BookingStatus::Confirmed], true));

        if ($unreadOnly) {
            $rows = $rows->filter(fn ($r) => $r['unread'] > 0);
        }

        return $rows->sortByDesc(fn ($r) => $r['at']->timestamp)->values();
    }

    /**
     * Bookings and days off of one month for the calendar.
     *
     * @return array{bookings: Collection<string, Collection<int, Booking>>, off: Collection<int, string>}
     */
    public function month(User $host, CarbonImmutable $month): array
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();

        return [
            'bookings' => Booking::query()->with(['experience', 'tourist'])->where('host_id', $host->id)
                ->whereIn('status', [BookingStatus::Requested->value, BookingStatus::Confirmed->value, BookingStatus::Completed->value])
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('start_time')->get()->groupBy(fn ($b) => $b->date->toDateString()),
            'off' => $host->hostProfile
                ? $host->hostProfile->blockedDates()->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get()->map(fn ($d) => $d->date->toDateString())
                : collect(),
        ];
    }

    /**
     * Money per booking for the earnings page.
     *
     * @return Collection<int, array{booking: Booking, gross: string, refunded: string, net: string}>
     */
    public function earnings(User $host, ?string $month = null): Collection
    {
        $query = Booking::query()->with(['payment', 'experience', 'tourist'])->where('host_id', $host->id)
            ->whereHas('payment', fn ($q) => $q->whereIn('status', [PaymentStatus::Captured->value, PaymentStatus::Refunded->value]));

        if ($month !== null) {
            $start = CarbonImmutable::parse("$month-01");
            $query->whereBetween('date', [$start->toDateString(), $start->endOfMonth()->toDateString()]);
        }

        return $query->orderByDesc('date')->get()->map(fn (Booking $b) => [
            'booking' => $b,
            'gross' => (string) $b->payment->amount,
            'refunded' => (string) $b->payment->refunded_amount,
            'net' => $this->net($b),
        ]);
    }
}
