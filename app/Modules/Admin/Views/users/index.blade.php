@php($roleBadge = ['tourist' => 'badge', 'host' => 'badge-accent', 'admin' => 'badge-verified'])
<x-dash area="admin" :title="__('admin::dash.users.title')" :subtitle="__('admin::dash.users.subtitle', ['n' => $users->total()])">
    <form method="GET" action="{{ route('admin.users.index') }}" class="rise rise-2 flex flex-wrap items-end gap-3" role="search">
        <div class="min-w-52 flex-1">
            <label class="label" for="q">{{ __('admin::dash.users.search') }}</label>
            <input class="field" type="search" id="q" name="q" value="{{ $q }}" placeholder="{{ __('admin::dash.users.search_placeholder') }}">
        </div>
        <div>
            <label class="label" for="role">{{ __('admin::dash.users.role') }}</label>
            <select class="field" id="role" name="role">
                <option value="">{{ __('admin::dash.users.any') }}</option>
                @foreach (['tourist', 'host', 'admin'] as $r)<option value="{{ $r }}" @selected($role === $r)>{{ __("admin::dash.users.role_$r") }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label" for="state">{{ __('admin::dash.users.state') }}</label>
            <select class="field" id="state" name="state">
                <option value="">{{ __('admin::dash.users.any') }}</option>
                <option value="active" @selected($state === 'active')>{{ __('admin::dash.users.active') }}</option>
                <option value="disabled" @selected($state === 'disabled')>{{ __('admin::dash.users.disabled') }}</option>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">{{ __('admin::dash.users.filter') }}</button>
    </form>

    <div class="panel mt-6 overflow-x-auto rise rise-3">
        <table class="w-full min-w-[44rem] text-start">
            <caption class="sr-only">{{ __('admin::dash.users.title') }}</caption>
            <thead class="border-b border-zinc-200 bg-zinc-50"><tr>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::dash.users.col_user') }}</th>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::dash.users.role') }}</th>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::dash.users.state') }}</th>
                <th scope="col" class="px-4 py-3 text-end">{{ __('admin::dash.users.col_bookings') }}</th>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::dash.users.col_joined') }}</th>
                <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('admin::dash.users.open') }}</span></th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($users as $u)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $u) }}" class="flex items-center gap-3 no-underline">
                                <x-avatar :user="$u" size="h-9 w-9 text-xs" />
                                <span class="min-w-0"><span class="block truncate font-medium text-zinc-900">{{ $u->name }}</span><span class="block truncate text-xs text-zinc-600">{{ $u->email }}</span></span>
                            </a>
                        </td>
                        <td class="px-4 py-3"><span class="{{ $roleBadge[$u->role->value] }}">{{ __("admin::dash.users.role_{$u->role->value}") }}</span></td>
                        <td class="px-4 py-3">@if ($u->disabled_at)<span class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-200">{{ __('admin::dash.users.disabled') }}</span>@else<span class="text-zinc-600">{{ __('admin::dash.users.active') }}</span>@endif</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ $u->bookings_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $u->created_at->translatedFormat('j M Y') }}</td>
                        <td class="px-4 py-3 text-end"><a class="text-sm font-medium" href="{{ route('admin.users.show', $u) }}">{{ __('admin::dash.users.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-12 text-center text-zinc-600" colspan="6">{{ __('admin::dash.users.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-dash>
