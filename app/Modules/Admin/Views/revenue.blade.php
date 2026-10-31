@php
    $tnd = fn ($v) => number_format((float) $v, 0).' TND';
    $barSteps = ['h-[4%]', 'h-[12%]', 'h-[20%]', 'h-[30%]', 'h-[40%]', 'h-[50%]', 'h-[60%]', 'h-[70%]', 'h-[80%]', 'h-[90%]', 'h-full'];
    $max = max(1, (float) collect($months)->max('volume'));
@endphp
<x-dash area="admin" :title="__('admin::dash.revenue.title')" :subtitle="__('admin::dash.revenue.subtitle')">
    <x-slot:actions>
        <a class="btn btn-secondary" href="{{ route('admin.revenue.csv') }}">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12M7 11l5 5 5-5M5 20h14"/></svg>
            {{ __('admin::dash.revenue.export') }}
        </a>
    </x-slot:actions>

    <div class="stagger grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card"><p class="text-sm text-zinc-600">{{ __('admin::dash.revenue.paid') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $tnd($totals['paid']) }}</p></div>
        <div class="card"><p class="text-sm text-zinc-600">{{ __('admin::dash.revenue.refunded') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $tnd($totals['refunded']) }}</p></div>
        <div class="card"><p class="text-sm text-zinc-600">{{ __('admin::dash.revenue.volume') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $tnd($totals['volume']) }}</p></div>
        <div class="card border-accent-200 bg-accent-50"><p class="text-sm text-accent-900">{{ __('admin::dash.revenue.revenue', ['rate' => (float) $rate * 100]) }}</p><p class="mt-1 text-2xl font-semibold tabular-nums text-accent-900">{{ $tnd($totals['revenue']) }}</p></div>
    </div>
    <p class="mt-3 max-w-3xl rounded-lg bg-zinc-100 px-4 py-3 text-sm text-zinc-700">{{ __('admin::dash.revenue.note', ['rate' => (float) $rate * 100]) }}</p>

    <section class="panel mt-6 p-5" aria-labelledby="chart-title" data-reveal>
        <h2 id="chart-title" class="text-base">{{ __('admin::dash.revenue.chart') }}</h2>
        <ul class="mt-6 grid h-44 grid-cols-12 items-end gap-2" role="list">
            @foreach ($months as $m)
                <li class="flex h-full flex-col items-center justify-end gap-1.5">
                    <span class="hidden text-[11px] font-medium tabular-nums text-zinc-700 md:block">{{ (float) $m['volume'] > 0 ? number_format((float) $m['volume'], 0) : '' }}</span>
                    <span class="grow-y w-full max-w-9 rounded-t-md {{ (float) $m['volume'] > 0 ? 'bg-accent-600' : 'bg-zinc-200' }} {{ $barSteps[(int) round((float) $m['volume'] / $max * 10)] }}"><span class="sr-only">{{ $m['label'] }}: {{ $tnd($m['volume']) }}</span></span>
                    <span class="text-[11px] text-zinc-600" aria-hidden="true">{{ $m['label'] }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel mt-6 overflow-x-auto" aria-labelledby="table-title" data-reveal>
        <h2 id="table-title" class="border-b border-zinc-100 px-5 py-3 text-base">{{ __('admin::dash.revenue.by_month') }}</h2>
        <table class="w-full min-w-[40rem] text-start">
            <thead class="border-b border-zinc-200 bg-zinc-50"><tr>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::dash.revenue.col_month') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.revenue.col_payments') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.revenue.paid') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.revenue.refunded') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.revenue.volume') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.revenue.col_revenue') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach (array_reverse($months) as $m)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ \Carbon\Carbon::parse($m['month'].'-01')->translatedFormat('F Y') }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ $m['bookings'] }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ number_format((float) $m['paid'], 3) }}</td>
                        <td class="px-4 py-3 text-end tabular-nums text-zinc-600">{{ number_format((float) $m['refunded'], 3) }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ number_format((float) $m['volume'], 3) }}</td>
                        <td class="px-4 py-3 text-end font-medium tabular-nums">{{ number_format((float) $m['revenue'], 3) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <section class="panel" aria-labelledby="guides-title" data-reveal>
            <h2 id="guides-title" class="border-b border-zinc-100 px-5 py-3 text-base">{{ __('admin::dash.revenue.top_guides') }}</h2>
            <ol>
                @forelse ($guides as $g)
                    <li class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0">
                        <span class="w-5 text-sm tabular-nums text-zinc-500">{{ $loop->iteration }}</span>
                        <x-avatar :user="$g['guide']" size="h-8 w-8 text-xs" />
                        <a class="min-w-0 flex-1 truncate font-medium text-zinc-900 no-underline" href="{{ route('admin.users.show', $g['guide']) }}">{{ $g['guide']->hostProfile?->display_name ?? $g['guide']->name }}</a>
                        <span class="text-sm font-medium tabular-nums">{{ $tnd($g['volume']) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-zinc-600">{{ __('admin::dash.revenue.none') }}</li>
                @endforelse
            </ol>
        </section>
        <section class="panel" aria-labelledby="cities-title" data-reveal>
            <h2 id="cities-title" class="border-b border-zinc-100 px-5 py-3 text-base">{{ __('admin::dash.revenue.top_cities') }}</h2>
            <ol>
                @forelse ($cities as $c)
                    <li class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0">
                        <span class="w-5 text-sm tabular-nums text-zinc-500">{{ $loop->iteration }}</span>
                        <span class="flex-1 font-medium">{{ __("common.cities.{$c['name']}") }}</span>
                        <span class="text-sm text-zinc-600">{{ __('admin::dash.revenue.payments_n', ['n' => $c['bookings']]) }}</span>
                        <span class="text-sm font-medium tabular-nums">{{ $tnd($c['volume']) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-zinc-600">{{ __('admin::dash.revenue.none') }}</li>
                @endforelse
            </ol>
        </section>
    </div>
</x-dash>
