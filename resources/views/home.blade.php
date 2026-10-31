<x-layouts.app :title="__('common.nav.home')">
    {{-- Hero: an arched doorway frame with the tagline --}}
    <section class="tile-bg-blue -mx-4 -mt-6 overflow-hidden px-4 pb-10 pt-10 text-white md:mx-0 md:mt-0 md:rounded-2xl" aria-labelledby="hero-title">
        <div class="mx-auto grid max-w-4xl items-center gap-8 md:grid-cols-[1fr_auto]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gold-400">{{ __('common.tagline') }}</p>
                <h1 id="hero-title" class="mt-2 text-4xl font-bold md:text-5xl">{{ __('common.home.title') }}</h1>
                <p class="mt-4 max-w-xl text-lg text-door-100">{{ __('common.home.lead') }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a class="btn bg-white px-5 text-door-900 hover:bg-door-50" href="{{ route('search') }}">{{ __('common.home.cta_search') }}</a>
                    @guest
                        <a class="btn border border-white/60 px-5 text-white hover:bg-white/10" href="{{ route('register', ['role' => 'host']) }}">{{ __('common.home.cta_host') }}</a>
                    @endguest
                </div>
            </div>
            <div class="arch mx-auto hidden w-44 justify-self-center border-4 border-gold-400 p-4 md:block" aria-hidden="true">
                <x-logo :text="false" size="h-32 w-full" />
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mt-10" aria-labelledby="how-title">
        <h2 id="how-title" class="text-3xl font-bold text-door-900">{{ __('common.home.steps_title') }}</h2>
        <ol class="mt-4 grid gap-4 md:grid-cols-3">
            @foreach ([1 => 'search', 2 => 'match', 3 => 'tea'] as $n => $icon)
                <li class="card lift">
                    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-door-50 text-door-700" aria-hidden="true">
                        @switch($icon)
                            @case('search')
                                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/><path d="M10.5 7.5v6M7.5 10.5h6" stroke="#cf6a45"/></svg>
                                @break
                            @case('match')
                                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 8.7l5.4-.8z"/><path d="M5 21h14" stroke="#4c5e22"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h9l-1 9a2 2 0 01-2 2h-3a2 2 0 01-2-2z"/><path d="M15 9.5h2.2a1.8 1.8 0 010 3.6H14.8"/><path d="M9 3c-1 1 1 2 0 3M12 3c-1 1 1 2 0 3" stroke="#cf6a45"/></svg>
                        @endswitch
                    </span>
                    <h3 class="text-xl font-bold text-door-900">{{ __("common.home.step_{$n}_title") }}</h3>
                    <p class="mt-1 text-slate-700">{{ __("common.home.step_$n") }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Featured cities --}}
    <section class="mt-10" aria-labelledby="cities-title">
        <h2 id="cities-title" class="text-3xl font-bold text-door-900">{{ __('common.home.cities_title') }}</h2>
        <ul class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3">
            @foreach ($cities as $city => $hosts)
                <li>
                    <a href="{{ route('search', ['city' => $city]) }}" class="tile-bg lift block h-full rounded-xl border border-door-100 p-4 no-underline">
                        <span class="block font-display text-2xl font-bold text-door-900">{{ __("common.cities.$city") }}</span>
                        <span class="mt-1 block text-sm text-slate-700">{{ trans_choice('common.home.hosts_count', $hosts, ['n' => $hosts]) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Two calls to action --}}
    <section class="mt-10 grid gap-4 md:grid-cols-2" aria-label="{{ __('common.home.cta_title') }}">
        <div class="card border-door-200">
            <h2 class="text-2xl font-bold text-door-900">{{ __('common.home.tourist_title') }}</h2>
            <p class="mt-2 text-slate-700">{{ __('common.home.tourist_text') }}</p>
            <a class="btn btn-primary mt-4" href="{{ route('search') }}">{{ __('common.home.cta_search') }}</a>
        </div>
        <div class="card border-clay-100 bg-clay-50">
            <h2 class="text-2xl font-bold text-clay-700">{{ __('common.home.host_title') }}</h2>
            <p class="mt-2 text-slate-700">{{ __('common.home.host_text') }}</p>
            @auth
                @if (auth()->user()->role->value === 'host')
                    <a class="btn btn-accent mt-4" href="{{ route('host.verification.show') }}">{{ __('common.home.cta_host') }}</a>
                @endif
            @else
                <a class="btn btn-accent mt-4" href="{{ route('register', ['role' => 'host']) }}">{{ __('common.home.cta_host') }}</a>
            @endauth
        </div>
    </section>
</x-layouts.app>
