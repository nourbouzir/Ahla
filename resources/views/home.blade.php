<x-layouts.app :title="__('common.nav.home')" wide>
    {{-- Hero: left-aligned copy and the search itself, no decoration --}}
    <section class="relative -mt-8 pb-12 pt-14 md:pb-16 md:pt-20" aria-labelledby="hero-title">
        <div class="dot-grid pointer-events-none absolute inset-x-0 top-0 -z-10 h-[420px]" aria-hidden="true"></div>
        <div class="max-w-3xl">
            <p class="eyebrow rise">{{ __('common.tagline') }}</p>
            <h1 id="hero-title" class="rise rise-2 mt-3 text-4xl leading-[1.1] md:text-6xl">{{ __('common.home.title') }}</h1>
            <p class="rise rise-3 mt-5 max-w-2xl text-lg text-zinc-600">{{ __('common.home.lead') }}</p>
        </div>

        <form method="GET" action="{{ route('search') }}" class="rise rise-4 mt-8 grid max-w-3xl gap-3 rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm md:grid-cols-[1fr_1fr_auto] md:items-end" role="search">
            <div>
                <label class="label px-1" for="hero-city">{{ __('matching::messages.city') }}</label>
                <select class="field border-0 bg-zinc-50 shadow-none" id="hero-city" name="city">
                    <option value="">{{ __('matching::messages.any') }}</option>
                    @foreach ($allCities as $city)<option value="{{ $city }}">{{ __("common.cities.$city") }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label px-1" for="hero-language">{{ __('matching::messages.language') }}</label>
                <select class="field border-0 bg-zinc-50 shadow-none" id="hero-language" name="language">
                    <option value="">{{ __('matching::messages.any') }}</option>
                    @foreach ($languages as $code)<option value="{{ $code }}">{{ __("common.languages.$code") }}</option>@endforeach
                </select>
            </div>
            <button class="btn btn-primary btn-lg md:h-10" type="submit">{{ __('matching::messages.search') }}</button>
        </form>

        <dl class="rise rise-4 mt-10 flex flex-wrap gap-x-12 gap-y-4">
            <div><dt class="text-sm text-zinc-600">{{ __('common.home.stat_hosts') }}</dt><dd class="text-3xl font-semibold tabular-nums">{{ $stats['hosts'] }}</dd></div>
            <div><dt class="text-sm text-zinc-600">{{ __('common.home.stat_cities') }}</dt><dd class="text-3xl font-semibold tabular-nums">{{ $stats['cities'] }}</dd></div>
            <div><dt class="text-sm text-zinc-600">{{ __('common.home.stat_languages') }}</dt><dd class="text-3xl font-semibold tabular-nums">3</dd></div>
        </dl>
    </section>

    {{-- Cities marquee (decorative repeat of the featured cities; the real links are in the grid below) --}}
    <div class="marquee -mx-4 overflow-hidden border-y border-zinc-200 bg-white py-3" aria-hidden="true">
        <div class="marquee-track flex gap-10 text-sm font-medium text-zinc-500">
            @foreach (range(1, 2) as $loop2)
                @foreach ($allCities as $city)
                    <span class="flex items-center gap-10">{{ __("common.cities.$city") }}<span class="h-1 w-1 rounded-full bg-zinc-300"></span></span>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- How it works --}}
    <section class="mt-16" aria-labelledby="how-title" data-reveal>
        <p class="eyebrow">{{ __('common.home.how_eyebrow') }}</p>
        <h2 id="how-title" class="section-title mt-2">{{ __('common.home.steps_title') }}</h2>
        <ol class="mt-8 grid gap-x-8 gap-y-6 md:grid-cols-3">
            @foreach ([1, 2, 3] as $n)
                <li class="border-t border-zinc-300 pt-4">
                    <span class="text-sm font-medium tabular-nums text-accent-700">0{{ $n }}</span>
                    <h3 class="mt-2 text-lg">{{ __("common.home.step_{$n}_title") }}</h3>
                    <p class="mt-1 text-zinc-600">{{ __("common.home.step_$n") }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Featured hosts: real data --}}
    @if ($featured->isNotEmpty())
        <section class="mt-16" aria-labelledby="hosts-title" data-reveal>
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ __('common.home.hosts_eyebrow') }}</p>
                    <h2 id="hosts-title" class="section-title mt-2">{{ __('common.home.hosts_title') }}</h2>
                </div>
                <a class="hidden text-sm font-medium md:inline" href="{{ route('search') }}">{{ __('common.home.all_hosts') }} &rarr;</a>
            </div>
            <ul class="stagger mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($featured as $r)
                    @include('matching::partials.card', ['r' => $r])
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Cities --}}
    <section class="mt-16" aria-labelledby="cities-title" data-reveal>
        <p class="eyebrow">{{ __('common.home.cities_eyebrow') }}</p>
        <h2 id="cities-title" class="section-title mt-2">{{ __('common.home.cities_title') }}</h2>
        <ul class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($cities->take(8) as $city => $hosts)
                <li>
                    <a href="{{ route('search', ['city' => $city]) }}" class="group card-link flex h-full items-center justify-between">
                        <span>
                            <span class="block font-semibold text-zinc-900">{{ __("common.cities.$city") }}</span>
                            <span class="mt-0.5 block text-sm text-zinc-600">{{ trans_choice('common.home.hosts_count', $hosts, ['n' => $hosts]) }}</span>
                        </span>
                        <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-accent-700 rtl:-scale-x-100 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"/></svg>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Two audiences --}}
    <section class="mt-16 grid gap-4 md:grid-cols-2" aria-label="{{ __('common.home.cta_title') }}" data-reveal>
        <div class="card flex flex-col p-8">
            <h2 class="text-2xl">{{ __('common.home.tourist_title') }}</h2>
            <p class="mt-2 flex-1 text-zinc-600">{{ __('common.home.tourist_text') }}</p>
            <div class="mt-6"><a class="btn btn-primary" href="{{ route('search') }}">{{ __('common.home.cta_search') }}</a></div>
        </div>
        <div class="flex flex-col rounded-xl bg-accent-900 p-8 text-white">
            <h2 class="text-2xl text-white">{{ __('common.home.host_title') }}</h2>
            <p class="mt-2 flex-1 text-accent-100">{{ __('common.home.host_text') }}</p>
            @if (! auth()->check() || auth()->user()->role->value === 'host')
                <div class="mt-6">
                    <a class="btn bg-white text-zinc-900 hover:bg-zinc-200" href="{{ auth()->check() ? route('host.verification.show') : route('register', ['role' => 'host']) }}">{{ __('common.home.cta_host') }}</a>
                </div>
            @endif
        </div>
    </section>

    {{-- FAQ --}}
    <section class="mt-16 grid gap-8 md:grid-cols-[1fr_2fr]" aria-labelledby="faq-title" data-reveal>
        <div>
            <p class="eyebrow">{{ __('common.home.faq_eyebrow') }}</p>
            <h2 id="faq-title" class="section-title mt-2">{{ __('common.home.faq_title') }}</h2>
        </div>
        <div class="faq border-t border-zinc-200">
            @foreach ([1, 2, 3, 4] as $n)
                <details @if ($n === 1) open @endif>
                    <summary>{{ __("common.home.faq_{$n}_q") }}</summary>
                    <p>{{ __("common.home.faq_{$n}_a") }}</p>
                </details>
            @endforeach
        </div>
    </section>
</x-layouts.app>
