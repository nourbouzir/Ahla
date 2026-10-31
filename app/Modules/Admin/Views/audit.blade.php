<x-layouts.app :title="__('admin::messages.audit')">
    <h1 class="text-2xl font-bold">{{ __('admin::messages.audit') }}</h1>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full">
            <caption class="sr-only">{{ __('admin::messages.audit') }}</caption>
            <thead><tr class="border-b">
                @foreach (['when', 'entity', 'change', 'actor', 'reason'] as $col)
                    <th scope="col" class="p-2 text-start">{{ __("admin::messages.col_$col") }}</th>
                @endforeach
            </tr></thead>
            <tbody>
            @foreach ($logs as $log)
                <tr class="border-b">
                    <td class="p-2">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td class="p-2">{{ $log->entity }} #{{ $log->entity_id }}</td>
                    <td class="p-2">{{ $log->old_status ?? '-' }} &rarr; {{ $log->new_status }}</td>
                    <td class="p-2">{{ $log->actor?->name ?? '-' }}</td>
                    <td class="p-2">{{ $log->reason }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</x-layouts.app>
