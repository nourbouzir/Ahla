<x-layouts.app :title="__('common.nav.home')">
    <section class="py-8">
        <h1 class="text-3xl font-bold md:text-4xl">{{ __('common.home.title') }}</h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-700">{{ __('common.home.lead') }}</p>
        <div class="mt-6 flex flex-wrap gap-3">
            @if (Route::has('search'))
                <a class="btn btn-primary" href="{{ route('search') }}">{{ __('common.home.cta_search') }}</a>
            @endif
            @guest
                <a class="btn btn-secondary" href="{{ route('register') }}">{{ __('common.home.cta_register') }}</a>
            @endguest
        </div>
    </section>
    <section class="mt-6">
        <h2 class="text-xl font-semibold">{{ __('common.home.steps_title') }}</h2>
        <ol class="mt-3 grid gap-3 md:grid-cols-3">
            @foreach ([1, 2, 3] as $n)
                <li class="card">{{ __("common.home.step_$n") }}</li>
            @endforeach
        </ol>
    </section>
</x-layouts.app>
