<x-layouts.app :title="__('common.nav.bookings')">
    <h1 class="text-2xl font-bold">{{ __('common.nav.bookings') }}</h1>
    <ul class="mt-4 space-y-3">
        @forelse ($bookings as $b)
            <li class="card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <a class="font-semibold underline" href="{{ route('bookings.show', $b) }}">{{ $b->experience->title }}</a>
                    <p class="text-sm text-slate-600">{{ $b->host->hostProfile?->display_name ?? $b->host->name }} -
                        {{ $b->date->format('Y-m-d') }} {{ substr($b->start_time, 0, 5) }}-{{ substr($b->end_time, 0, 5) }}</p>
                </div>
                <span class="badge">{{ __('booking::messages.status.'.$b->status->value) }}</span>
            </li>
        @empty
            <li>{{ __('booking::messages.none') }}</li>
        @endforelse
    </ul>
</x-layouts.app>
