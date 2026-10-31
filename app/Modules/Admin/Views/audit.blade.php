@php($filters = ['session', 'profile', 'experience', 'availability', 'message', 'booking', 'payment', 'host', 'verification', 'user'])
<x-dash area="admin" :title="__('admin::messages.audit')" :subtitle="__('admin::dash.audit.subtitle')">
    <div class="rise rise-2 flex flex-wrap gap-2" role="group" aria-label="{{ __('admin::dash.audit.filter') }}">
        <a href="{{ route('admin.audit') }}" class="rounded-full border px-3 py-1 text-sm no-underline {{ $entity === null ? 'border-accent-900 bg-accent-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}" @if ($entity === null) aria-current="true" @endif>{{ __('admin::dash.audit.all') }}</a>
        @foreach ($filters as $f)
            <a href="{{ route('admin.audit', ['entity' => $f]) }}" class="rounded-full border px-3 py-1 text-sm no-underline {{ $entity === $f ? 'border-accent-900 bg-accent-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}" @if ($entity === $f) aria-current="true" @endif>{{ __("admin::dash.entity.$f") }}</a>
        @endforeach
    </div>

    <div class="panel mt-5 overflow-x-auto rise rise-3">
        <table class="w-full min-w-[40rem] text-start">
            <caption class="sr-only">{{ __('admin::messages.audit') }}</caption>
            <thead class="border-b border-zinc-200 bg-zinc-50"><tr>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::messages.col_when') }}</th>
                <th scope="col" class="px-4 py-3 text-start">{{ __('admin::dash.audit.what') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600"><time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">{{ $log->created_at->diffForHumans() }}</time></td>
                        <td class="px-4 py-3 text-sm">@include('admin::partials.log-line', ['log' => $log])</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-12 text-center text-zinc-600" colspan="2">{{ __('admin::dash.audit.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-dash>
