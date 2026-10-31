<x-layouts.app :title="$host->display_name" wide>
    <a class="rise inline-flex items-center gap-1 text-sm font-medium text-zinc-600 no-underline hover:text-zinc-900" href="{{ route('search') }}">
        <svg viewBox="0 0 20 20" class="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10H4M9 5l-5 5 5 5"/></svg>
        {{ __('matching::messages.back_to_search') }}
    </a>

    <header class="rise rise-2 mt-5 flex flex-wrap items-center gap-5">
        <x-avatar :user="$host->user" :name="$host->display_name" size="h-20 w-20 text-2xl" />
        <div>
            <h1 class="text-3xl">{{ $host->display_name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-zinc-600">
                <span>{{ __("common.cities.{$host->city}") }}</span>
                @if ($host->rating_avg !== null)
                    <span class="flex items-center gap-1"><svg viewBox="0 0 20 20" class="h-4 w-4 text-amber-500" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.4 5.9.8-4.3 4.1 1 5.9L10 14.9 4.8 17.7l1-5.9L1.5 7.7l5.9-.8z"/></svg>{{ number_format((float) $host->rating_avg, 1) }} / 5</span>
                @endif
                <span class="badge-verified"><svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8.5l3.2 3L13 4.5"/></svg>{{ __('auth::messages.status.verified') }}</span>
            </p>
        </div>
    </header>

    <div class="mt-8 grid items-start gap-8 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-8">
            <section class="rise rise-3" aria-labelledby="about-title">
                <h2 id="about-title" class="text-xl">{{ __('matching::messages.about') }}</h2>
                <p class="mt-2 max-w-2xl text-zinc-700">{{ $host->bio }}</p>
            </section>

            <section aria-labelledby="exp-title" data-reveal>
                <h2 id="exp-title" class="text-xl">{{ __('matching::messages.experiences') }}</h2>
                <ul class="stagger mt-3 space-y-3">
                    @forelse ($host->experiences as $e)
                        <li class="card">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold">{{ $e->title }}</h3>
                                    <p class="mt-0.5 text-sm text-zinc-600">{{ $e->duration_hours }} h · {{ __('matching::messages.max_people', ['n' => $e->max_group_size]) }}</p>
                                </div>
                                <p class="text-end"><span class="text-lg font-semibold tabular-nums">{{ number_format((float) $e->price_per_person, 0) }} TND</span><span class="block text-xs text-zinc-600">{{ __('booking::messages.per_person') }}</span></p>
                            </div>
                            <p class="mt-3 text-zinc-700">{{ $e->description }}</p>
                            @if ($e->meeting_point)<p class="mt-2 text-sm text-zinc-600">{{ __('booking::messages.meeting') }}: {{ $e->meeting_point }}</p>@endif
                            @auth
                                @can('booking.request')
                                    <a class="btn btn-primary mt-4" href="{{ route('bookings.create', $e) }}">{{ __('matching::messages.request_booking') }}</a>
                                @endcan
                            @else
                                <a class="btn btn-primary mt-4" href="{{ route('login') }}">{{ __('matching::messages.login_to_book') }}</a>
                            @endauth
                        </li>
                    @empty
                        <li class="text-zinc-600">{{ __('matching::messages.no_experiences') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-20" aria-label="{{ __('matching::messages.details') }}">
            <dl class="panel divide-y divide-zinc-100 text-sm">
                <div class="p-4"><dt class="font-medium">{{ __('auth::messages.spoken_languages') }}</dt>
                    <dd class="mt-1 text-zinc-600">{{ $host->user->languages->map(fn ($l) => __("common.languages.{$l->language}"))->join(', ') ?: '-' }}</dd></div>
                <div class="p-4"><dt class="font-medium">{{ __('auth::messages.interests') }}</dt>
                    <dd class="mt-1 flex flex-wrap gap-1.5">
                        @forelse ($host->user->interests as $i)<span class="badge">{{ __("auth::messages.interest.{$i->interest}") }}</span>@empty - @endforelse
                    </dd></div>
                <div class="p-4"><dt class="font-medium">{{ __('experience::messages.weekly') }}</dt>
                    <dd class="mt-1 space-y-0.5 text-zinc-600">
                        @forelse ($host->availabilities->sortBy(['day_of_week', 'starts_at']) as $a)
                            <span class="flex justify-between gap-3"><span>{{ __('experience::messages.days.'.$a->day_of_week) }}</span><span class="tabular-nums">{{ substr($a->starts_at, 0, 5) }}-{{ substr($a->ends_at, 0, 5) }}</span></span>
                        @empty
                            {{ __('matching::messages.no_hours') }}
                        @endforelse
                    </dd></div>
            </dl>
        </aside>
    </div>
</x-layouts.app>
