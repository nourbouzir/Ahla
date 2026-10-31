<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Auth\Enums\Role;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Enums\VerificationRequestStatus;
use App\Modules\Verification\Models\HostProfile;
use App\Modules\Verification\Models\VerificationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Read-only figures for the admin space. It changes nothing.
 *
 * Money words used on the screens:
 *   paid      = amount of every payment that was captured (money really taken)
 *   refunded  = part of that paid amount given back
 *   volume    = paid - refunded  (what clients really spent; "net volume")
 *   revenue   = volume x commission rate. Ahla takes no commission in v1 (ADR 0004), so this is an
 *               INDICATIVE figure for planning (AHLA_COMMISSION_RATE, ADR 0007), not a ledger entry.
 */
class AdminStats
{
    public function rate(): string
    {
        return (string) config('ahla.commission_rate');
    }

    private function revenueOf(string $volume): string
    {
        return bcmul($volume, $this->rate(), 3);
    }

    /** @return array<string, mixed> */
    public function overview(): array
    {
        $payments = Payment::query()->whereIn('status', [PaymentStatus::Captured->value, PaymentStatus::Refunded->value])->get();
        $paid = $payments->reduce(fn ($s, $p) => bcadd($s, (string) $p->amount, 3), '0.000');
        $refunded = $payments->reduce(fn ($s, $p) => bcadd($s, (string) $p->refunded_amount, 3), '0.000');
        $volume = bcsub($paid, $refunded, 3);

        $roles = User::query()->selectRaw('role, COUNT(*) as n')->groupBy('role')->pluck('n', 'role');
        $statuses = Booking::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $hostStatuses = HostProfile::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'users' => [
                'total' => $roles->sum(),
                'tourists' => (int) ($roles[Role::Tourist->value] ?? 0),
                'hosts' => (int) ($roles[Role::Host->value] ?? 0),
                'admins' => (int) ($roles[Role::Admin->value] ?? 0),
                'new_30' => User::where('created_at', '>=', now()->subDays(30))->count(),
                'disabled' => User::whereNotNull('disabled_at')->count(),
            ],
            'guides' => [
                'verified' => (int) ($hostStatuses[HostStatus::Verified->value] ?? 0),
                'pending' => (int) ($hostStatuses[HostStatus::Pending->value] ?? 0),
                'suspended' => (int) ($hostStatuses[HostStatus::Suspended->value] ?? 0),
                'rejected' => (int) ($hostStatuses[HostStatus::Rejected->value] ?? 0),
            ],
            'bookings' => [
                'total' => $statuses->sum(),
                'by_status' => $statuses,
                'today' => Booking::whereDate('created_at', today())->count(),
            ],
            'money' => ['paid' => $paid, 'refunded' => $refunded, 'volume' => $volume, 'revenue' => $this->revenueOf($volume), 'rate' => $this->rate()],
            'daily' => $this->daily(14),
            'months' => $this->months(6),
            'attention' => $this->attention(),
            'recent' => AuditLog::with('actor')->latest('created_at')->latest('id')->limit(8)->get(),
        ];
    }

    /**
     * Things an admin should look at today.
     *
     * @return array<string, int>
     */
    public function attention(): array
    {
        return [
            // Applications waiting for a decision.
            'applications' => VerificationRequest::where('status', '!=', VerificationRequestStatus::Decided->value)->count(),
            // Requests that will expire in under two hours with nobody answering.
            'expiring' => Booking::where('status', BookingStatus::Requested->value)->whereBetween('expires_at', [now(), now()->addHours(2)])->count(),
            // Cancelled bookings whose money has not been fully returned yet (the retry job is working on them).
            'refunds_pending' => Booking::where('status', BookingStatus::Cancelled->value)->whereHas('payment', fn ($q) => $q->where('status', PaymentStatus::Captured->value))->count(),
            // Money still reserved for a booking that is already closed.
            'stuck_holds' => Payment::where('status', PaymentStatus::Authorised->value)->whereHas('booking', fn ($q) => $q->whereIn('status', [BookingStatus::Declined->value, BookingStatus::Expired->value, BookingStatus::Cancelled->value]))->count(),
            'disabled' => User::whereNotNull('disabled_at')->count(),
        ];
    }

    /** @return list<array{day: string, label: string, n: int}> bookings requested per day, oldest first */
    public function daily(int $days): array
    {
        $start = CarbonImmutable::today()->subDays($days - 1);
        $counts = Booking::query()->where('created_at', '>=', $start)->get(['created_at'])->countBy(fn ($b) => $b->created_at->toDateString());

        return collect(range(0, $days - 1))->map(function ($i) use ($start, $counts) {
            $day = $start->addDays($i);

            return ['day' => $day->toDateString(), 'label' => $day->translatedFormat('j'), 'n' => (int) ($counts[$day->toDateString()] ?? 0)];
        })->all();
    }

    /**
     * Money per month, by the day the payment was captured.
     *
     * @return list<array{month: string, label: string, paid: string, refunded: string, volume: string, revenue: string, bookings: int}>
     */
    public function months(int $count): array
    {
        // Start from the first day of this month: subtracting months from the 31st would overflow (31 Oct - 11 months = 1 Dec).
        $end = CarbonImmutable::today()->endOfMonth();
        $start = CarbonImmutable::today()->startOfMonth()->subMonths($count - 1);
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $key = $start->addMonths($i)->format('Y-m');
            $rows[$key] = ['month' => $key, 'label' => $start->addMonths($i)->translatedFormat('M'), 'paid' => '0.000', 'refunded' => '0.000', 'bookings' => 0];
        }

        Payment::query()->whereIn('status', [PaymentStatus::Captured->value, PaymentStatus::Refunded->value])
            ->whereBetween('captured_at', [$start, $end])->get()
            ->each(function (Payment $p) use (&$rows) {
                $key = $p->captured_at->format('Y-m');
                $rows[$key]['paid'] = bcadd($rows[$key]['paid'], (string) $p->amount, 3);
                $rows[$key]['refunded'] = bcadd($rows[$key]['refunded'], (string) $p->refunded_amount, 3);
                $rows[$key]['bookings']++;
            });

        return collect($rows)->map(function ($r) {
            $volume = bcsub($r['paid'], $r['refunded'], 3);

            return $r + ['volume' => $volume, 'revenue' => $this->revenueOf($volume)];
        })->values()->all();
    }

    /**
     * Where the money comes from.
     *
     * @return array{cities: Collection<int, array<string, mixed>>, guides: Collection<int, array<string, mixed>>}
     */
    public function breakdown(int $limit = 8): array
    {
        $rows = Payment::query()->with('booking.host.hostProfile')
            ->whereIn('status', [PaymentStatus::Captured->value, PaymentStatus::Refunded->value])->get()
            ->map(fn (Payment $p) => [
                'guide' => $p->booking->host,
                'city' => $p->booking->host->hostProfile?->city ?? '-',
                'volume' => bcsub((string) $p->amount, (string) $p->refunded_amount, 3),
            ]);

        $group = fn ($key) => $rows->groupBy($key)->map(fn ($g) => ['volume' => $g->reduce(fn ($s, $r) => bcadd($s, $r['volume'], 3), '0.000'), 'bookings' => $g->count()]);

        return [
            'cities' => $group('city')->sortByDesc(fn ($r) => (float) $r['volume'])->take($limit)->map(fn ($r, $city) => $r + ['name' => $city])->values(),
            'guides' => $rows->groupBy(fn ($r) => $r['guide']->id)->map(fn ($g) => [
                'guide' => $g->first()['guide'],
                'volume' => $g->reduce(fn ($s, $r) => bcadd($s, $r['volume'], 3), '0.000'),
                'bookings' => $g->count(),
            ])->sortByDesc(fn ($r) => (float) $r['volume'])->take($limit)->values(),
        ];
    }

    /**
     * One row per guide with the numbers that show how they are doing.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function guides(?string $status = null, ?string $city = null, ?string $search = null): Collection
    {
        $profiles = HostProfile::query()->with('user')->withCount(['experiences as published_count' => fn ($q) => $q->where('published', true)])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($city, fn ($q, $c) => $q->where('city', $c))
            ->when($search, fn ($q, $s) => $q->where('display_name', 'like', '%'.addcslashes($s, '%_\\').'%'))
            ->get();

        $bookings = Booking::query()->with('payment')->whereIn('host_id', $profiles->pluck('user_id'))->get()->groupBy('host_id');

        return $profiles->map(function (HostProfile $profile) use ($bookings) {
            $mine = $bookings->get($profile->user_id, collect());
            $done = $mine->where('status', BookingStatus::Completed)->count();
            $cancelled = $mine->where('status', BookingStatus::Cancelled)->count();
            $volume = $mine->reduce(function ($sum, Booking $b) {
                $p = $b->payment;

                return $p && in_array($p->status, [PaymentStatus::Captured, PaymentStatus::Refunded], true)
                    ? bcadd($sum, bcsub((string) $p->amount, (string) $p->refunded_amount, 3), 3) : $sum;
            }, '0.000');

            return [
                'profile' => $profile,
                'bookings' => $mine->count(),
                'completed' => $done,
                // Share of confirmed work that ended in a cancellation: a quick reliability signal.
                'cancel_rate' => ($done + $cancelled) > 0 ? (int) round($cancelled / ($done + $cancelled) * 100) : null,
                'volume' => $volume,
                'last' => $mine->max(fn ($b) => $b->date->toDateString()),
            ];
        })->sortByDesc(fn ($r) => (float) $r['volume'])->values();
    }
}
