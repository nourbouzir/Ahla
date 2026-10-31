<x-dash area="admin" :title="__('admin::messages.bookings')" :heading="false">
    <h1 class="text-2xl font-bold">{{ __('admin::messages.bookings') }}</h1>
    <nav class="mt-3 flex flex-wrap gap-3" aria-label="{{ __('admin::messages.filter') }}">
        <a class="underline {{ $status === null ? 'font-bold' : '' }}" href="{{ route('admin.bookings.index') }}">{{ __('admin::messages.filter_all') }}</a>
        @foreach (['requested', 'confirmed', 'declined', 'expired', 'cancelled', 'completed'] as $s)
            <a class="underline {{ $status === $s ? 'font-bold' : '' }}" href="{{ route('admin.bookings.index', ['status' => $s]) }}">{{ __("booking::messages.status.$s") }}</a>
        @endforeach
    </nav>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full">
            <caption class="sr-only">{{ __('admin::messages.bookings') }}</caption>
            <thead><tr class="border-b">
                @foreach (['booking', 'tourist', 'host', 'payment'] as $col)
                    <th scope="col" class="p-2 text-start">{{ __("admin::messages.col_$col") }}</th>
                @endforeach
            </tr></thead>
            <tbody>
            @forelse ($bookings as $b)
                <tr class="border-b">
                    <td class="p-2"><a class="underline" href="{{ route('bookings.show', $b) }}">#{{ $b->id }} {{ $b->experience->title }}</a><br>
                        <span class="text-sm text-slate-600">{{ $b->date->format('Y-m-d') }} {{ substr($b->start_time, 0, 5) }} - <span class="badge">{{ __('booking::messages.status.'.$b->status->value) }}</span></span></td>
                    <td class="p-2">{{ $b->tourist->name }}</td>
                    <td class="p-2">{{ $b->host->hostProfile?->display_name ?? $b->host->name }}</td>
                    <td class="p-2">{{ number_format((float) $b->displayed_price, 3) }} TND<br>
                        <span class="text-sm">{{ $b->payment ? __('booking::messages.payment_status.'.$b->payment->status->value) : '-' }}</span></td>
                </tr>
            @empty
                <tr><td class="p-2" colspan="4">{{ __('admin::messages.none') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $bookings->links() }}
</x-dash>
