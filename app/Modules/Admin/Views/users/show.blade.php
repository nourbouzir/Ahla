<x-dash area="admin" :title="$person->name" :heading="false">
    <a class="rise mb-4 inline-flex items-center gap-1 text-sm font-medium text-zinc-600 no-underline hover:text-zinc-900" href="{{ route('admin.users.index') }}">
        <svg viewBox="0 0 20 20" class="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10H4M9 5l-5 5 5 5"/></svg>
        {{ __('admin::dash.users.back') }}
    </a>

    <section class="panel rise rise-2 flex flex-wrap items-center gap-4 p-5">
        <x-avatar :user="$person" size="h-16 w-16 text-xl" />
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl">{{ $person->name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-600">
                <span>{{ $person->email }}</span>
                <span class="badge">{{ __("admin::dash.users.role_{$person->role->value}") }}</span>
                @if ($person->disabled_at)<span class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-200">{{ __('admin::dash.users.disabled') }}</span>@endif
            </p>
        </div>
        @if ($person->hostProfile && $person->hostProfile->status->value === 'verified')
            <a class="btn btn-secondary" href="{{ route('hosts.show', $person->hostProfile) }}">{{ __('admin::dash.users.public_page') }}</a>
        @endif
    </section>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-6">
            <section class="panel" aria-labelledby="b-title" data-reveal>
                <h2 id="b-title" class="border-b border-zinc-100 px-5 py-3 text-base">{{ __('admin::dash.users.bookings') }}</h2>
                <ul>
                    @forelse ($bookings as $b)
                        <li class="border-b border-zinc-100 last:border-0">
                            <a href="{{ route('bookings.show', $b) }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 no-underline transition-colors hover:bg-zinc-50">
                                <span><span class="block font-medium text-zinc-900">{{ $b->experience->title }}</span><span class="block text-sm text-zinc-600">{{ $b->date->translatedFormat('j M Y') }} · {{ number_format((float) $b->displayed_price, 0) }} TND</span></span>
                                <span class="badge">{{ __('booking::messages.status.'.$b->status->value) }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-zinc-600">{{ __('admin::dash.users.no_bookings') }}</li>
                    @endforelse
                </ul>
            </section>

            <section class="panel" aria-labelledby="h-title" data-reveal>
                <h2 id="h-title" class="border-b border-zinc-100 px-5 py-3 text-base">{{ __('admin::dash.users.history') }}</h2>
                <ul>
                    @forelse ($history as $log)
                        <li class="border-b border-zinc-100 px-5 py-3 text-sm last:border-0">
                            <span class="font-medium">{{ $log->entity }} #{{ $log->entity_id }}</span> <span class="text-zinc-600">{{ $log->old_status ?? '-' }} &rarr; {{ $log->new_status }}</span>
                            @if ($log->reason)<span class="block text-zinc-600">{{ $log->reason }}</span>@endif
                            <span class="block text-xs text-zinc-600">{{ $log->actor?->name ?? '-' }} · {{ $log->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-zinc-600">{{ __('admin::dash.users.no_history') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            <dl class="panel divide-y divide-zinc-100 text-sm" data-reveal>
                <div class="p-4"><dt class="text-zinc-600">{{ __('admin::dash.users.col_joined') }}</dt><dd class="mt-0.5 font-medium">{{ $person->created_at->translatedFormat('j F Y') }}</dd></div>
                <div class="p-4"><dt class="text-zinc-600">{{ __('auth::messages.spoken_languages') }}</dt><dd class="mt-0.5 font-medium">{{ $person->languages->map(fn ($l) => __("common.languages.{$l->language}"))->join(', ') ?: '-' }}</dd></div>
                <div class="p-4"><dt class="text-zinc-600">{{ __('auth::messages.phone') }}</dt><dd class="mt-0.5 font-medium">{{ $person->phone ?: '-' }}</dd></div>
                @if ($person->hostProfile)
                    <div class="p-4"><dt class="text-zinc-600">{{ __('auth::messages.host_status') }}</dt><dd class="mt-0.5 font-medium">{{ __('auth::messages.status.'.$person->hostProfile->status->value) }} · {{ $person->hostProfile->city }}</dd></div>
                @endif
            </dl>

            @if ($person->role->value !== 'admin')
                <section class="panel p-5" aria-labelledby="acc-title" data-reveal>
                    <h2 id="acc-title" class="text-base">{{ __('admin::dash.users.account') }}</h2>
                    @if ($person->disabled_at)
                        <p class="mt-2 text-sm text-zinc-600">{{ __('admin::dash.users.disabled_since', ['date' => $person->disabled_at->translatedFormat('j M Y')]) }} {{ $person->disabled_reason }}</p>
                        <form method="POST" action="{{ route('admin.users.enable', $person) }}" class="mt-3">@csrf<button class="btn btn-secondary" type="submit">{{ __('admin::dash.users.enable') }}</button></form>
                    @else
                        <p class="mt-2 text-sm text-zinc-600">{{ __('admin::dash.users.disable_hint') }}</p>
                        <form method="POST" action="{{ route('admin.users.disable', $person) }}" class="mt-3">
                            @csrf
                            <label class="label" for="reason">{{ __('admin::dash.users.reason') }}</label>
                            <input class="field" id="reason" name="reason" maxlength="200" required>
                            <button class="btn btn-danger mt-3" type="submit">{{ __('admin::dash.users.disable') }}</button>
                        </form>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-dash>
