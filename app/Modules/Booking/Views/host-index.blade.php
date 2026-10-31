<x-layouts.app :title="__('common.nav.host_bookings')">
    <h1 class="text-2xl font-bold">{{ __('common.nav.host_bookings') }}</h1>
    @error('booking')<p class="error-text" role="alert">{{ $message }}</p>@enderror

    <h2 class="mt-4 text-lg font-semibold">{{ __('booking::messages.waiting') }}</h2>
    <ul class="mt-2 space-y-3">
        @forelse ($requests as $b)
            <li class="card">
                <p class="font-semibold">{{ $b->experience->title }}</p>
                <p class="text-sm text-slate-600">{{ $b->tourist->name }} - {{ $b->date->format('Y-m-d') }}
                    {{ substr($b->start_time, 0, 5) }}-{{ substr($b->end_time, 0, 5) }} - {{ __('booking::messages.people', ['n' => $b->group_size]) }} -
                    {{ number_format((float) $b->displayed_price, 3) }} TND</p>
                <p class="text-sm">{{ __('booking::messages.answer_before') }} {{ $b->expires_at->format('Y-m-d H:i') }}</p>
                <div class="mt-3 flex gap-2">
                    <form method="POST" action="{{ route('host.bookings.accept', $b) }}">@csrf
                        <button class="btn btn-primary" type="submit">{{ __('booking::messages.accept') }}</button></form>
                    <form method="POST" action="{{ route('host.bookings.decline', $b) }}">@csrf
                        <button class="btn btn-secondary" type="submit">{{ __('booking::messages.decline') }}</button></form>
                </div>
            </li>
        @empty
            <li>{{ __('booking::messages.no_requests') }}</li>
        @endforelse
    </ul>

    <h2 class="mt-8 text-lg font-semibold">{{ __('booking::messages.all') }}</h2>
    <ul class="mt-2 space-y-3">
        @foreach ($others as $b)
            <li class="card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <a class="font-semibold underline" href="{{ route('bookings.show', $b) }}">{{ $b->experience->title }}</a>
                    <p class="text-sm text-slate-600">{{ $b->tourist->name }} - {{ $b->date->format('Y-m-d') }} {{ substr($b->start_time, 0, 5) }}</p>
                </div>
                <span class="badge">{{ __('booking::messages.status.'.$b->status->value) }}</span>
            </li>
        @endforeach
    </ul>
</x-layouts.app>
