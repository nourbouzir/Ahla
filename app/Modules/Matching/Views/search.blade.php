<x-layouts.app :title="__('matching::messages.title')" wide>
    <div class="rise mb-6">
        <h1 class="text-3xl">{{ __('matching::messages.title') }}</h1>
        <p class="mt-1 text-zinc-600" id="results-count" aria-live="polite">{{ __('matching::messages.results', ['n' => $results->total()]) }}</p>
    </div>

    <div class="grid items-start gap-8 lg:grid-cols-[17rem_1fr]">
        <form method="GET" action="{{ route('search') }}" class="panel rise rise-2 space-y-4 p-4 lg:sticky lg:top-20" role="search" aria-label="{{ __('matching::messages.title') }}">
            <div>
                <label class="label" for="q">{{ __('matching::messages.q') }}</label>
                <input class="field" id="q" name="q" value="{{ $input['q'] ?? '' }}" maxlength="80" type="search">
            </div>
            <div>
                <label class="label" for="city">{{ __('matching::messages.city') }}</label>
                <select class="field" id="city" name="city">
                    <option value="">{{ __('matching::messages.any') }}</option>
                    @foreach ($cities as $city)<option value="{{ $city }}" @selected(($input['city'] ?? null) === $city)>{{ __("common.cities.$city") }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label" for="language">{{ __('matching::messages.language') }}</label>
                <select class="field" id="language" name="language">
                    <option value="">{{ __('matching::messages.any') }}</option>
                    @foreach ($languages as $code)<option value="{{ $code }}" @selected(($input['language'] ?? null) === $code)>{{ __("common.languages.$code") }}</option>@endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label" for="date">{{ __('matching::messages.date') }}</label>
                    <input class="field" type="date" id="date" name="date" value="{{ $input['date'] ?? '' }}" min="{{ today()->toDateString() }}">
                </div>
                <div>
                    <label class="label" for="group_size">{{ __('matching::messages.group_size') }}</label>
                    <input class="field" type="number" id="group_size" name="group_size" min="1" max="6" value="{{ $input['group_size'] ?? '' }}">
                </div>
                <div>
                    <label class="label" for="max_price">{{ __('matching::messages.max_price_short') }}</label>
                    <input class="field" type="number" id="max_price" name="max_price" min="0" max="1000" step="1" value="{{ $input['max_price'] ?? '' }}">
                </div>
                <div>
                    <label class="label" for="min_rating">{{ __('matching::messages.min_rating') }}</label>
                    <select class="field" id="min_rating" name="min_rating">
                        <option value="">{{ __('matching::messages.any') }}</option>
                        @foreach ([3, 4, 4.5] as $r)<option value="{{ $r }}" @selected((string) ($input['min_rating'] ?? '') === (string) $r)>{{ $r }}+</option>@endforeach
                    </select>
                </div>
            </div>
            <fieldset>
                <legend class="label">{{ __('matching::messages.interests') }}</legend>
                <div class="mt-1 grid grid-cols-2 gap-x-2">
                    @foreach ($interests as $interest)
                        <label class="check"><input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, $input['interests'] ?? []))> {{ __("auth::messages.interest.$interest") }}</label>
                    @endforeach
                </div>
            </fieldset>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1" type="submit">{{ __('matching::messages.search') }}</button>
                @if ($searched)<a class="btn btn-secondary" href="{{ route('search') }}">{{ __('matching::messages.clear_short') }}</a>@endif
            </div>
        </form>

        <section aria-labelledby="results-title">
            <h2 id="results-title" class="sr-only">{{ __('matching::messages.results', ['n' => $results->total()]) }}</h2>
            @auth @else
                <p class="mb-4 rounded-lg bg-accent-50 px-4 py-3 text-sm text-accent-900">{{ __('matching::messages.login_hint') }}</p>
            @endauth

            @if ($results->isEmpty())
                <div class="panel flex flex-col items-center px-6 py-16 text-center">
                    <svg viewBox="0 0 48 48" class="h-12 w-12 text-zinc-300" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="21" cy="21" r="12"/><path d="M30 30l10 10M16 21h10"/></svg>
                    <p class="mt-4 text-lg font-semibold">{{ $searched ? __('matching::messages.empty_title') : __('matching::messages.empty_all') }}</p>
                    @if ($searched)
                        <p class="mt-1 max-w-sm text-zinc-600">{{ __('matching::messages.empty_search') }}</p>
                        <a class="btn btn-secondary mt-5" href="{{ route('search') }}">{{ __('matching::messages.clear') }}</a>
                    @endif
                </div>
            @else
                <ul class="stagger grid gap-4 md:grid-cols-2">
                    @foreach ($results as $r)
                        @include('matching::partials.card', ['r' => $r])
                    @endforeach
                </ul>
                <div class="mt-6">{{ $results->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.app>
