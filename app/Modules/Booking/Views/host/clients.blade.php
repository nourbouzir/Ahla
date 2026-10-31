@php($stages = ['awaiting', 'upcoming', 'returning', 'past', 'closed'])
@php($stageBadge = ['awaiting' => 'badge-accent', 'upcoming' => 'badge-verified', 'returning' => 'badge-verified', 'past' => 'badge', 'closed' => 'badge'])
<x-dash area="host" :title="__('booking::host.clients.title')" :subtitle="__('booking::host.clients.subtitle', ['n' => $all->count()])">
    <form method="GET" action="{{ route('host.clients.index') }}" class="rise rise-2 flex flex-wrap items-end gap-3" role="search">
        <div class="min-w-48 flex-1">
            <label class="label" for="q">{{ __('booking::host.clients.search') }}</label>
            <input class="field" type="search" id="q" name="q" value="{{ $q }}" placeholder="{{ __('booking::host.clients.search_placeholder') }}">
        </div>
        <div>
            <label class="label" for="sort">{{ __('booking::host.clients.sort') }}</label>
            <select class="field" id="sort" name="sort">
                @foreach (['recent', 'name', 'spent', 'bookings'] as $s)<option value="{{ $s }}" @selected($sort === $s)>{{ __("booking::host.clients.sort_$s") }}</option>@endforeach
            </select>
        </div>
        <input type="hidden" name="stage" value="{{ $stage }}">
        <button class="btn btn-primary" type="submit">{{ __('booking::host.clients.apply') }}</button>
    </form>

    <div class="rise rise-3 mt-4 flex flex-wrap gap-2" role="group" aria-label="{{ __('booking::host.clients.stage') }}">
        <a href="{{ route('host.clients.index', array_filter(['q' => $q, 'sort' => $sort])) }}" class="rounded-full border px-3 py-1 text-sm no-underline {{ $stage === null ? 'border-accent-900 bg-accent-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}" @if ($stage === null) aria-current="true" @endif>{{ __('booking::host.clients.all') }} <span class="tabular-nums">{{ $all->count() }}</span></a>
        @foreach ($stages as $s)
            @php($n = $all->where('stage', $s)->count())
            @if ($n > 0)
                <a href="{{ route('host.clients.index', array_filter(['q' => $q, 'sort' => $sort, 'stage' => $s])) }}" class="rounded-full border px-3 py-1 text-sm no-underline {{ $stage === $s ? 'border-accent-900 bg-accent-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}" @if ($stage === $s) aria-current="true" @endif>{{ __("booking::host.stage.$s") }} <span class="tabular-nums">{{ $n }}</span></a>
            @endif
        @endforeach
    </div>

    @if ($clients->isEmpty())
        <div class="panel mt-6 px-6 py-16 text-center">
            <p class="text-lg font-semibold">{{ $all->isEmpty() ? __('booking::host.clients.empty') : __('booking::host.clients.no_match') }}</p>
            <p class="mx-auto mt-1 max-w-sm text-zinc-600">{{ $all->isEmpty() ? __('booking::host.clients.empty_text') : __('booking::host.clients.no_match_text') }}</p>
        </div>
    @else
        <div class="panel mt-6 overflow-x-auto" data-reveal>
            <table class="w-full min-w-[40rem] text-start">
                <caption class="sr-only">{{ __('booking::host.clients.title') }}</caption>
                <thead class="border-b border-zinc-200 bg-zinc-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('booking::host.clients.col_client') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('booking::host.clients.col_stage') }}</th>
                        <th scope="col" class="px-4 py-3 text-end">{{ __('booking::host.clients.col_bookings') }}</th>
                        <th scope="col" class="px-4 py-3 text-end">{{ __('booking::host.clients.col_spent') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('booking::host.clients.col_last') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('booking::host.clients.open') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($clients as $c)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('host.clients.show', $c['tourist']) }}" class="flex items-center gap-3 font-medium text-zinc-900 no-underline">
                                    <x-avatar :user="$c['tourist']" size="h-9 w-9 text-xs" /> {{ $c['tourist']->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3"><span class="{{ $stageBadge[$c['stage']] }}">{{ __("booking::host.stage.{$c['stage']}") }}</span></td>
                            <td class="px-4 py-3 text-end tabular-nums">{{ $c['bookings'] }}</td>
                            <td class="px-4 py-3 text-end tabular-nums">{{ number_format((float) $c['spent'], 0) }} TND</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $c['next'] ? __('booking::host.clients.next_on', ['date' => $c['next']->translatedFormat('j M')]) : $c['last']->translatedFormat('j M Y') }}</td>
                            <td class="px-4 py-3 text-end"><a class="text-sm font-medium" href="{{ route('host.clients.show', $c['tourist']) }}">{{ __('booking::host.clients.open') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-dash>
