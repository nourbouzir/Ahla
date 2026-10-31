@php($badge = ['verified' => 'badge-verified', 'pending' => 'badge-accent', 'rejected' => 'badge', 'suspended' => 'badge'])
<x-dash area="admin" :title="__('admin::dash.guides.title')" :subtitle="__('admin::dash.guides.subtitle', ['n' => $all->count()])">
    <form method="GET" action="{{ route('admin.hosts.index') }}" class="rise rise-2 flex flex-wrap items-end gap-3" role="search">
        <div class="min-w-48 flex-1">
            <label class="label" for="q">{{ __('admin::dash.users.search') }}</label>
            <input class="field" type="search" id="q" name="q" value="{{ $q }}" placeholder="{{ __('admin::dash.users.search_placeholder') }}">
        </div>
        <div>
            <label class="label" for="status">{{ __('admin::dash.guides.status') }}</label>
            <select class="field" id="status" name="status">
                <option value="">{{ __('admin::dash.users.any') }}</option>
                @foreach (['verified', 'pending', 'suspended', 'rejected'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ __("auth::messages.status.$s") }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label" for="city">{{ __('matching::messages.city') }}</label>
            <select class="field" id="city" name="city">
                <option value="">{{ __('admin::dash.users.any') }}</option>
                @foreach ($cities as $c)<option value="{{ $c }}" @selected($city === $c)>{{ __("common.cities.$c") }}</option>@endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">{{ __('admin::dash.users.filter') }}</button>
    </form>

    <div class="panel mt-6 overflow-x-auto rise rise-3">
        <table class="w-full min-w-[52rem] text-start">
            <caption class="sr-only">{{ __('admin::dash.guides.title') }}</caption>
            <thead class="border-b border-zinc-200 bg-zinc-50"><tr>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::dash.guides.col_guide') }}</th>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::dash.guides.status') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.guides.col_rating') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.guides.col_experiences') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.guides.col_bookings') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.guides.col_cancel') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.guides.col_volume') }}</th>
                <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('admin::dash.guides.actions') }}</span></th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($guides as $g)
                    @php($p = $g['profile'])
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $p->user) }}" class="flex items-center gap-3 no-underline">
                                <x-avatar :user="$p->user" :name="$p->display_name" size="h-9 w-9 text-xs" />
                                <span class="min-w-0"><span class="block truncate font-medium text-zinc-900">{{ $p->display_name }}</span><span class="block text-xs text-zinc-600">{{ __("common.cities.{$p->city}") }}</span></span>
                            </a>
                        </td>
                        <td class="px-4 py-3"><span class="{{ $badge[$p->status->value] }}">{{ __('auth::messages.status.'.$p->status->value) }}</span></td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ $p->rating_avg !== null ? number_format((float) $p->rating_avg, 1) : '-' }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ $p->published_count }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ $g['bookings'] }}</td>
                        <td class="px-4 py-3 text-end tabular-nums {{ ($g['cancel_rate'] ?? 0) >= 30 ? 'font-semibold text-red-700' : '' }}">{{ $g['cancel_rate'] !== null ? $g['cancel_rate'].'%' : '-' }}</td>
                        <td class="px-4 py-3 text-end font-medium tabular-nums">{{ number_format((float) $g['volume'], 0) }} TND</td>
                        <td class="px-4 py-3 text-end">
                            @if ($p->status->value === 'verified')
                                <details class="relative inline-block text-start">
                                    <summary class="btn btn-secondary h-8 cursor-pointer list-none px-3 text-xs">{{ __('admin::dash.guides.suspend') }}</summary>
                                    <form method="POST" action="{{ route('admin.hosts.status', $p) }}" class="absolute end-0 z-10 mt-2 w-64 rounded-xl border border-zinc-200 bg-white p-3 shadow-lg">
                                        @csrf<input type="hidden" name="status" value="suspended">
                                        <label class="label" for="r-{{ $p->id }}">{{ __('admin::messages.reason') }}</label>
                                        <input class="field" id="r-{{ $p->id }}" name="reason" required maxlength="200">
                                        <button class="btn btn-danger mt-2 w-full" type="submit">{{ __('admin::messages.suspend') }}</button>
                                    </form>
                                </details>
                            @elseif ($p->status->value === 'suspended')
                                <form method="POST" action="{{ route('admin.hosts.status', $p) }}">@csrf<input type="hidden" name="status" value="verified"><button class="btn btn-secondary h-8 px-3 text-xs" type="submit">{{ __('admin::messages.reinstate') }}</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-12 text-center text-zinc-600" colspan="8">{{ __('admin::dash.users.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-dash>
