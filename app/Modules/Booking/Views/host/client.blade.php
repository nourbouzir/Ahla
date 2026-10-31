@php($stageBadge = ['awaiting' => 'badge-accent', 'upcoming' => 'badge-verified', 'returning' => 'badge-verified', 'past' => 'badge', 'closed' => 'badge'])
<x-dash area="host" :title="$tourist->name">
    <a class="rise -mt-3 mb-4 inline-flex items-center gap-1 text-sm font-medium text-zinc-600 no-underline hover:text-zinc-900" href="{{ route('host.clients.index') }}">
        <svg viewBox="0 0 20 20" class="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10H4M9 5l-5 5 5 5"/></svg>
        {{ __('booking::host.client.back') }}
    </a>

    <div class="grid items-start gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="space-y-6">
            <section class="panel rise rise-2 flex flex-wrap items-center gap-4 p-5" aria-label="{{ __('booking::host.client.summary') }}">
                <x-avatar :user="$tourist" size="h-16 w-16 text-xl" />
                <div class="min-w-0 flex-1">
                    <p class="text-xl font-semibold">{{ $tourist->name }}</p>
                    <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-600">
                        @if ($row)<span class="{{ $stageBadge[$row['stage']] }}">{{ __("booking::host.stage.{$row['stage']}") }}</span>@endif
                        <span>{{ $tourist->languages->map(fn ($l) => __("common.languages.{$l->language}"))->join(', ') ?: '' }}</span>
                    </p>
                </div>
                @if ($chatBooking)
                    <a class="btn btn-primary" href="{{ route('messages.show', $chatBooking) }}">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/></svg>
                        {{ __('booking::host.client.message') }}
                    </a>
                @endif
            </section>

            @if ($row)
                <div class="stagger grid grid-cols-3 gap-3">
                    <div class="card"><p class="text-sm text-zinc-600">{{ __('booking::host.clients.col_bookings') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $row['bookings'] }}</p></div>
                    <div class="card"><p class="text-sm text-zinc-600">{{ __('booking::host.clients.col_spent') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format((float) $row['spent'], 0) }} TND</p></div>
                    <div class="card"><p class="text-sm text-zinc-600">{{ __('booking::host.client.completed') }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $row['completed'] }}</p></div>
                </div>
            @endif

            <section class="panel" aria-labelledby="history-title" data-reveal>
                <h2 id="history-title" class="border-b border-zinc-100 px-5 py-3 text-base">{{ __('booking::host.client.history') }}</h2>
                <ul>
                    @foreach ($bookings as $b)
                        <li class="border-b border-zinc-100 last:border-0">
                            <a href="{{ route('bookings.show', $b) }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 no-underline transition-colors hover:bg-zinc-50">
                                <span>
                                    <span class="block font-medium text-zinc-900">{{ $b->experience->title }}</span>
                                    <span class="block text-sm text-zinc-600">{{ $b->date->translatedFormat('D j M Y') }}, {{ substr($b->start_time, 0, 5) }} · {{ __('booking::messages.people', ['n' => $b->group_size]) }}</span>
                                </span>
                                <span class="text-end"><span class="block text-sm font-medium tabular-nums text-zinc-900">{{ number_format((float) $b->displayed_price, 0) }} TND</span>
                                    <span class="{{ $b->status->value === 'confirmed' || $b->status->value === 'completed' ? 'badge-verified' : ($b->status->value === 'requested' ? 'badge-accent' : 'badge') }}">{{ __('booking::messages.status.'.$b->status->value) }}</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            <section class="panel p-5" aria-labelledby="note-title" data-reveal>
                <h2 id="note-title" class="text-base">{{ __('booking::host.client.note') }}</h2>
                <p class="mt-1 text-sm text-zinc-600">{{ __('booking::host.client.note_hint') }}</p>
                <form method="POST" action="{{ route('host.clients.note', $tourist) }}" class="mt-3">
                    @csrf @method('PUT')
                    <label class="sr-only" for="body">{{ __('booking::host.client.note') }}</label>
                    <textarea class="field" id="body" name="body" rows="5" maxlength="2000" placeholder="{{ __('booking::host.client.note_placeholder') }}">{{ old('body', $note) }}</textarea>
                    <button class="btn btn-secondary mt-3" type="submit">{{ __('common.actions.save') }}</button>
                </form>
            </section>

            <section class="panel p-5" aria-labelledby="contact-title" data-reveal>
                <h2 id="contact-title" class="text-base">{{ __('booking::host.client.contact') }}</h2>
                @if ($canSeeContact)
                    <dl class="mt-3 space-y-2 text-sm">
                        <div><dt class="text-zinc-600">{{ __('auth::messages.email') }}</dt><dd class="font-medium"><a href="mailto:{{ $tourist->email }}">{{ $tourist->email }}</a></dd></div>
                        @if ($canSeePhone && $tourist->phone)<div><dt class="text-zinc-600">{{ __('auth::messages.phone') }}</dt><dd class="font-medium">{{ $tourist->phone }}</dd></div>@endif
                    </dl>
                @else
                    <p class="mt-2 text-sm text-zinc-600">{{ __('booking::messages.contact_hidden') }}</p>
                @endif
                @if ($tourist->interests->isNotEmpty())
                    <p class="mt-4 text-sm text-zinc-600">{{ __('auth::messages.interests') }}</p>
                    <p class="mt-1 flex flex-wrap gap-1.5">@foreach ($tourist->interests as $i)<span class="badge">{{ __("auth::messages.interest.{$i->interest}") }}</span>@endforeach</p>
                @endif
            </section>
        </div>
    </div>
</x-dash>
