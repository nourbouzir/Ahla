<x-layouts.app :title="$user->hostProfile?->display_name ?? $user->name">
    <article class="card mx-auto max-w-xl">
        <h1 class="text-2xl font-bold">{{ $user->hostProfile?->display_name ?? $user->name }}</h1>
        @if ($user->hostProfile)
            <p class="text-slate-600">{{ $user->hostProfile->city }}</p>
            <p class="mt-3">{{ $user->hostProfile->bio }}</p>
        @endif
        <dl class="mt-4 space-y-2">
            <div><dt class="label">{{ __('auth::messages.spoken_languages') }}</dt>
                <dd>{{ $user->languages->map(fn ($l) => __("common.languages.{$l->language}"))->join(', ') ?: '-' }}</dd></div>
            <div><dt class="label">{{ __('auth::messages.interests') }}</dt>
                <dd>{{ $user->interests->map(fn ($i) => __("auth::messages.interest.{$i->interest}"))->join(', ') ?: '-' }}</dd></div>
            @if ($showContact)
                <div><dt class="label">{{ __('auth::messages.email') }}</dt><dd>{{ $user->email }}</dd></div>
                @if ($showPhone)
                    <div><dt class="label">{{ __('auth::messages.phone') }}</dt><dd>{{ $user->phone ?: '-' }}</dd></div>
                @endif
            @endif
        </dl>
    </article>
</x-layouts.app>
