@php($money = fn ($v) => number_format((float) $v, 3).' TND')
<x-dash area="host" :title="__('booking::host.earnings.title')" :subtitle="$month ? \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') : __('booking::host.earnings.all_time')">
    <x-slot:actions>
        <a class="btn btn-secondary" href="{{ route('host.earnings.csv', array_filter(['month' => $month])) }}">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12M7 11l5 5 5-5M5 20h14"/></svg>
            {{ __('booking::host.earnings.export') }}
        </a>
    </x-slot:actions>

    <div class="rise rise-2 flex flex-wrap gap-2" role="group" aria-label="{{ __('booking::host.earnings.period') }}">
        <a href="{{ route('host.earnings') }}" class="rounded-full border px-3 py-1 text-sm no-underline {{ $month === null ? 'border-accent-900 bg-accent-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}" @if ($month === null) aria-current="true" @endif>{{ __('booking::host.earnings.all_time') }}</a>
        @foreach (array_reverse($months) as $m)
            <a href="{{ route('host.earnings', ['month' => $m['month']]) }}" class="rounded-full border px-3 py-1 text-sm no-underline {{ $month === $m['month'] ? 'border-accent-900 bg-accent-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}" @if ($month === $m['month']) aria-current="true" @endif>{{ \Carbon\Carbon::parse($m['month'].'-01')->translatedFormat('M Y') }}</a>
        @endforeach
    </div>

    <div class="stagger mt-5 grid gap-3 sm:grid-cols-3">
        <div class="card"><p class="text-sm text-zinc-600">{{ __('booking::host.earnings.gross') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $money($gross) }}</p></div>
        <div class="card"><p class="text-sm text-zinc-600">{{ __('booking::host.earnings.refunded') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $money($refunded) }}</p></div>
        <div class="card border-accent-200 bg-accent-50"><p class="text-sm text-accent-900">{{ __('booking::host.earnings.net') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums text-accent-900">{{ $money($net) }}</p></div>
    </div>
    <p class="mt-3 text-sm text-zinc-600">{{ __('booking::host.earnings.note') }}</p>

    @if ($rows->isEmpty())
        <div class="panel mt-6 px-6 py-14 text-center"><p class="text-lg font-semibold">{{ __('booking::host.earnings.empty') }}</p><p class="mt-1 text-zinc-600">{{ __('booking::host.earnings.empty_text') }}</p></div>
    @else
        <div class="panel mt-6 overflow-x-auto" data-reveal>
            <table class="w-full min-w-[40rem] text-start">
                <caption class="sr-only">{{ __('booking::host.earnings.title') }}</caption>
                <thead class="border-b border-zinc-200 bg-zinc-50"><tr>
                    <th scope="col" class="px-4 py-3 text-start">{{ __('booking::host.earnings.col_date') }}</th>
                    <th scope="col" class="px-4 py-3 text-start">{{ __('booking::host.earnings.col_booking') }}</th>
                    <th scope="col" class="px-4 py-3 text-end">{{ __('booking::host.earnings.gross') }}</th>
                    <th scope="col" class="px-4 py-3 text-end">{{ __('booking::host.earnings.refunded') }}</th>
                    <th scope="col" class="px-4 py-3 text-end">{{ __('booking::host.earnings.net') }}</th>
                </tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($rows as $r)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $r['booking']->date->translatedFormat('j M Y') }}</td>
                            <td class="px-4 py-3"><a class="font-medium" href="{{ route('bookings.show', $r['booking']) }}">{{ $r['booking']->experience->title }}</a><span class="block text-xs text-zinc-600">{{ $r['booking']->tourist->name }}</span></td>
                            <td class="px-4 py-3 text-end tabular-nums">{{ $money($r['gross']) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums text-zinc-600">{{ (float) $r['refunded'] > 0 ? '-'.$money($r['refunded']) : '-' }}</td>
                            <td class="px-4 py-3 text-end font-medium tabular-nums">{{ $money($r['net']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-dash>
