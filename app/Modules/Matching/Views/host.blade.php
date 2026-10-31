<x-layouts.app :title="$host->display_name">
    <article class="mx-auto max-w-2xl">
        <div class="flex items-start justify-between gap-2">
            <h1 class="text-2xl font-bold">{{ $host->display_name }}</h1>
            <span class="badge">{{ __('auth::messages.status.verified') }}</span>
        </div>
        <p class="text-slate-600">{{ $host->city }}@if ($host->rating_avg !== null) - {{ number_format((float) $host->rating_avg, 1) }} / 5 @endif</p>
        <p class="mt-3">{{ $host->bio }}</p>

        <dl class="mt-4 grid gap-2 md:grid-cols-2">
            <div><dt class="label">{{ __('auth::messages.spoken_languages') }}</dt>
                <dd>{{ $host->user->languages->map(fn ($l) => __("common.languages.{$l->language}"))->join(', ') ?: '-' }}</dd></div>
            <div><dt class="label">{{ __('auth::messages.interests') }}</dt>
                <dd>{{ $host->user->interests->map(fn ($i) => __("auth::messages.interest.{$i->interest}"))->join(', ') ?: '-' }}</dd></div>
        </dl>

        <h2 class="mt-6 text-xl font-semibold">{{ __('matching::messages.experiences') }}</h2>
        <ul class="mt-3 space-y-3">
            @forelse ($host->experiences as $e)
                <li class="card">
                    <h3 class="font-semibold">{{ $e->title }}</h3>
                    <p class="text-sm text-slate-600">{{ $e->duration_hours }} h - {{ number_format((float) $e->price_per_person, 3) }} TND {{ __('booking::messages.per_person') }} - max {{ $e->max_group_size }}</p>
                    <p class="mt-2">{{ $e->description }}</p>
                    @if ($e->meeting_point)<p class="mt-1 text-sm">{{ __('booking::messages.meeting') }}: {{ $e->meeting_point }}</p>@endif
                    @auth
                        @can('booking.request')
                            <a class="btn btn-primary mt-3" href="{{ route('bookings.create', $e) }}">{{ __('matching::messages.request_booking') }}</a>
                        @endcan
                    @else
                        <a class="btn btn-primary mt-3" href="{{ route('login') }}">{{ __('matching::messages.login_to_book') }}</a>
                    @endauth
                </li>
            @empty
                <li>{{ __('matching::messages.no_experiences') }}</li>
            @endforelse
        </ul>

        <h2 class="mt-6 text-xl font-semibold">{{ __('experience::messages.weekly') }}</h2>
        <ul class="mt-2">
            @forelse ($host->availabilities->sortBy(['day_of_week', 'starts_at']) as $a)
                <li>{{ __('experience::messages.days.'.$a->day_of_week) }} {{ substr($a->starts_at, 0, 5) }} - {{ substr($a->ends_at, 0, 5) }}</li>
            @empty
                <li>{{ __('matching::messages.no_hours') }}</li>
            @endforelse
        </ul>
    </article>
</x-layouts.app>
