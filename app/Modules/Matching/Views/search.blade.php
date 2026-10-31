<x-layouts.app :title="__('matching::messages.title')">
    <h1 class="text-2xl font-bold">{{ __('matching::messages.title') }}</h1>

    <form method="GET" action="{{ route('search') }}" class="card mt-4" role="search" aria-label="{{ __('matching::messages.title') }}">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label" for="q">{{ __('matching::messages.q') }}</label>
                <input class="field" id="q" name="q" value="{{ $input['q'] ?? '' }}" maxlength="80">
            </div>
            <div>
                <label class="label" for="city">{{ __('matching::messages.city') }}</label>
                <select class="field" id="city" name="city">
                    <option value="">{{ __('matching::messages.any') }}</option>
                    @foreach ($cities as $city)<option value="{{ $city }}" @selected(($input['city'] ?? null) === $city)>{{ $city }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label" for="language">{{ __('matching::messages.language') }}</label>
                <select class="field" id="language" name="language">
                    <option value="">{{ __('matching::messages.any') }}</option>
                    @foreach ($languages as $code)<option value="{{ $code }}" @selected(($input['language'] ?? null) === $code)>{{ __("common.languages.$code") }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label" for="date">{{ __('matching::messages.date') }}</label>
                <input class="field" type="date" id="date" name="date" value="{{ $input['date'] ?? '' }}" min="{{ today()->toDateString() }}">
            </div>
            <div>
                <label class="label" for="group_size">{{ __('matching::messages.group_size') }}</label>
                <input class="field" type="number" id="group_size" name="group_size" min="1" max="6" value="{{ $input['group_size'] ?? '' }}">
            </div>
            <div>
                <label class="label" for="max_price">{{ __('matching::messages.max_price') }}</label>
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
        <fieldset class="mt-4">
            <legend class="label">{{ __('matching::messages.interests') }}</legend>
            @foreach ($interests as $interest)
                <label class="mr-4 inline-flex items-center gap-2 py-2">
                    <input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, $input['interests'] ?? []))>
                    {{ __("auth::messages.interest.$interest") }}
                </label>
            @endforeach
        </fieldset>
        <button class="btn btn-primary mt-4" type="submit">{{ __('matching::messages.search') }}</button>
    </form>

    <section class="mt-6" aria-live="polite" aria-labelledby="results-title">
        <h2 id="results-title" class="text-xl font-semibold">{{ __('matching::messages.results', ['n' => $results->total()]) }}</h2>
        @auth @else
            <p class="mt-1 text-sm text-slate-600">{{ __('matching::messages.login_hint') }}</p>
        @endauth

        @if ($results->isEmpty())
            <div class="card tile-bg mt-3 text-center">
                <p class="font-display text-2xl text-door-900">{{ $searched ? __('matching::messages.empty_search') : __('matching::messages.empty_all') }}</p>
                @if ($searched)<a class="underline" href="{{ route('search') }}">{{ __('matching::messages.clear') }}</a>@endif
            </div>
        @else
            <ul class="mt-3 grid gap-4 md:grid-cols-2">
                @foreach ($results as $r)
                    @include('matching::partials.card', ['r' => $r])
                @endforeach
            </ul>
            <div class="mt-4">{{ $results->links() }}</div>
        @endif
    </section>
</x-layouts.app>
