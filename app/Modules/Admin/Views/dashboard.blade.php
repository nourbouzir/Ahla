@php
    // $money, $users, $guides, $bookings, $daily, $months, $attention, $recent come from AdminStats::overview().
    $tnd = fn ($v) => number_format((float) $v, 0).' TND';
    $barSteps = ['h-[4%]', 'h-[12%]', 'h-[20%]', 'h-[30%]', 'h-[40%]', 'h-[50%]', 'h-[60%]', 'h-[70%]', 'h-[80%]', 'h-[90%]', 'h-full'];
    $wideSteps = ['w-[3%]', 'w-[10%]', 'w-[20%]', 'w-[30%]', 'w-[40%]', 'w-[50%]', 'w-[60%]', 'w-[70%]', 'w-[80%]', 'w-[90%]', 'w-full'];
    $maxDay = max(1, collect($daily)->max('n'));
    $maxMonth = max(1, (float) collect($months)->max('volume'));
    $statusMax = max(1, (int) $bookings['by_status']->max());
    $tone = ['requested' => 'bg-accent-500', 'confirmed' => 'bg-olive-1000', 'completed' => 'bg-zinc-500', 'declined' => 'bg-zinc-300', 'expired' => 'bg-zinc-300', 'cancelled' => 'bg-red-400'];
    $attentionItems = [
        ['applications', 'admin.verifications.index', 'admin::dash.attention.applications'],
        ['expiring', 'admin.bookings.index', 'admin::dash.attention.expiring'],
        ['refunds_pending', 'admin.bookings.index', 'admin::dash.attention.refunds_pending'],
        ['stuck_holds', 'admin.bookings.index', 'admin::dash.attention.stuck_holds'],
        ['disabled', 'admin.users.index', 'admin::dash.attention.disabled'],
    ];
    $open = collect($attentionItems)->filter(fn ($i) => $attention[$i[0]] > 0);
@endphp
<x-dash area="admin" :title="__('admin::dash.overview.title')" :subtitle="now()->translatedFormat('l j F Y')">
    <x-slot:actions>
        <a class="btn btn-secondary" href="{{ route('admin.revenue') }}">{{ __('admin::dash.nav.revenue') }}</a>
        <a class="btn btn-primary" href="{{ route('admin.verifications.index') }}">{{ __('admin::dash.overview.review_applications') }}@if ($attention['applications']) <span class="rounded-full bg-white/25 px-1.5 text-xs tabular-nums">{{ $attention['applications'] }}</span>@endif</a>
    </x-slot:actions>

    <div class="stagger grid grid-cols-2 gap-3 lg:grid-cols-4">
        <a href="{{ route('admin.users.index') }}" class="card-link">
            <p class="text-sm text-zinc-600">{{ __('admin::dash.kpi.users') }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $users['total'] }}</p>
            <p class="mt-1 text-xs text-zinc-600">{{ __('admin::dash.kpi.users_hint', ['t' => $users['tourists'], 'g' => $users['hosts'], 'n' => $users['new_30']]) }}</p>
        </a>
        <a href="{{ route('admin.hosts.index') }}" class="card-link">
            <p class="text-sm text-zinc-600">{{ __('admin::dash.kpi.guides') }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $guides['verified'] }}</p>
            <p class="mt-1 text-xs text-zinc-600">{{ __('admin::dash.kpi.guides_hint', ['p' => $guides['pending'], 's' => $guides['suspended']]) }}</p>
        </a>
        <a href="{{ route('admin.bookings.index') }}" class="card-link">
            <p class="text-sm text-zinc-600">{{ __('admin::dash.kpi.bookings') }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $bookings['total'] }}</p>
            <p class="mt-1 text-xs text-zinc-600">{{ __('admin::dash.kpi.bookings_hint', ['n' => $bookings['today']]) }}</p>
        </a>
        <a href="{{ route('admin.revenue') }}" class="card-link border-accent-200 bg-accent-50">
            <p class="text-sm text-accent-900">{{ __('admin::dash.kpi.volume') }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums text-accent-900">{{ $tnd($money['volume']) }}</p>
            <p class="mt-1 text-xs text-accent-900">{{ __('admin::dash.kpi.revenue_hint', ['amount' => $tnd($money['revenue']), 'rate' => (float) $money['rate'] * 100]) }}</p>
        </a>
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="space-y-6">
            <section class="panel p-5" aria-labelledby="daily-title" data-reveal>
                <div class="flex items-center justify-between">
                    <h2 id="daily-title" class="text-base">{{ __('admin::dash.overview.daily') }}</h2>
                    <span class="text-sm text-zinc-600">{{ __('admin::dash.overview.last_days', ['n' => 14]) }}</span>
                </div>
                <ul class="mt-6 grid h-36 grid-cols-14 items-end gap-1.5" role="list">
                    @foreach ($daily as $d)
                        <li class="flex h-full flex-col items-center justify-end gap-1">
                            <span class="grow-y w-full rounded-t {{ $d['n'] > 0 ? 'bg-accent-600' : 'bg-zinc-200' }} {{ $barSteps[(int) round($d['n'] / $maxDay * 10)] }}"><span class="sr-only">{{ $d['day'] }}: {{ $d['n'] }}</span></span>
                            <span class="text-[10px] text-zinc-600" aria-hidden="true">{{ $d['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="panel p-5" aria-labelledby="months-title" data-reveal>
                <div class="flex items-center justify-between">
                    <h2 id="months-title" class="text-base">{{ __('admin::dash.overview.volume_months') }}</h2>
                    <a class="text-sm font-medium" href="{{ route('admin.revenue') }}">{{ __('admin::dash.overview.details') }}</a>
                </div>
                <ul class="mt-6 grid h-40 grid-cols-6 items-end gap-3" role="list">
                    @foreach ($months as $m)
                        <li class="flex h-full flex-col items-center justify-end gap-1.5">
                            <span class="text-xs font-medium tabular-nums text-zinc-700">{{ (float) $m['volume'] > 0 ? number_format((float) $m['volume'], 0) : '' }}</span>
                            <span class="grow-y w-full max-w-12 rounded-t-md {{ (float) $m['volume'] > 0 ? 'bg-accent-600' : 'bg-zinc-200' }} {{ $barSteps[(int) round((float) $m['volume'] / $maxMonth * 10)] }}"><span class="sr-only">{{ $m['label'] }}: {{ $tnd($m['volume']) }}</span></span>
                            <span class="text-xs text-zinc-600" aria-hidden="true">{{ $m['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="panel p-5" aria-labelledby="status-title" data-reveal>
                <h2 id="status-title" class="text-base">{{ __('admin::dash.overview.by_status') }}</h2>
                <ul class="mt-4 space-y-2.5">
                    @foreach (['requested', 'confirmed', 'completed', 'declined', 'expired', 'cancelled'] as $s)
                        @php($n = (int) ($bookings['by_status'][$s] ?? 0))
                        <li class="grid grid-cols-[6rem_1fr_2.5rem] items-center gap-3 text-sm">
                            <span class="text-zinc-700">{{ __("booking::messages.status.$s") }}</span>
                            <span class="h-2.5 overflow-hidden rounded-full bg-zinc-100"><span class="grow-x block h-full rounded-full {{ $tone[$s] }} {{ $n > 0 ? $wideSteps[(int) round($n / $statusMax * 10)] : 'w-0' }}"></span></span>
                            <span class="text-end font-medium tabular-nums">{{ $n }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            <section class="panel" aria-labelledby="attention-title" data-reveal>
                <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3">
                    <h2 id="attention-title" class="text-base">{{ __('admin::dash.overview.attention') }}</h2>
                    @if ($open->isNotEmpty())<span class="badge-accent">{{ $open->sum(fn ($i) => $attention[$i[0]]) }}</span>@endif
                </div>
                @forelse ($open as [$key, $route, $label])
                    <a href="{{ route($route) }}" class="flex items-center justify-between gap-3 border-b border-zinc-100 px-5 py-3 text-sm no-underline transition-colors last:border-0 hover:bg-zinc-50">
                        <span class="text-zinc-900">{{ __($label) }}</span>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-amber-900">{{ $attention[$key] }}</span>
                    </a>
                @empty
                    <p class="flex items-center gap-2 px-5 py-8 text-sm text-zinc-600">
                        <svg viewBox="0 0 20 20" class="h-5 w-5 text-olive-700" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ __('admin::dash.overview.all_clear') }}
                    </p>
                @endforelse
            </section>

            <section class="panel p-5" aria-labelledby="funnel-title" data-reveal>
                <h2 id="funnel-title" class="text-base">{{ __('admin::dash.overview.guides_by_status') }}</h2>
                <dl class="mt-3 divide-y divide-zinc-100 text-sm">
                    @foreach (['verified' => 'badge-verified', 'pending' => 'badge-accent', 'suspended' => 'badge', 'rejected' => 'badge'] as $s => $badge)
                        <div class="flex items-center justify-between py-2"><dt><span class="{{ $badge }}">{{ __("auth::messages.status.$s") }}</span></dt><dd class="font-medium tabular-nums">{{ $guides[$s] }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="panel" aria-labelledby="recent-title" data-reveal>
                <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3">
                    <h2 id="recent-title" class="text-base">{{ __('admin::dash.overview.activity') }}</h2>
                    <a class="text-sm font-medium" href="{{ route('admin.audit') }}">{{ __('admin::dash.overview.all') }}</a>
                </div>
                <ul>
                    @forelse ($recent as $log)
                        <li class="border-b border-zinc-100 px-5 py-3 text-sm last:border-0">
                            <p class="text-zinc-900"><span class="font-medium">{{ $log->entity }} #{{ $log->entity_id }}</span> <span class="text-zinc-600">{{ $log->old_status ?? '-' }} &rarr; {{ $log->new_status }}</span></p>
                            <p class="text-xs text-zinc-600">{{ $log->actor?->name ?? __('admin::dash.overview.system') }} · {{ $log->created_at->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-zinc-600">{{ __('admin::dash.overview.no_activity') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-dash>
