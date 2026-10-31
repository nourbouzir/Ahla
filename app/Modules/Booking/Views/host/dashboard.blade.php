@php
    $money = fn ($v) => number_format((float) $v, 0).' TND';
    $barSteps = ['h-[4%]', 'h-[12%]', 'h-[20%]', 'h-[30%]', 'h-[40%]', 'h-[50%]', 'h-[60%]', 'h-[70%]', 'h-[80%]', 'h-[90%]', 'h-full'];
    $widthSteps = [0 => 'w-0', 20 => 'w-1/5', 40 => 'w-2/5', 60 => 'w-3/5', 80 => 'w-4/5', 100 => 'w-full'];
    $maxNet = max(1, (float) collect($months)->max('net'));
    $statusBadge = ['requested' => 'badge-accent', 'confirmed' => 'badge-verified'];
    $firstName = \Illuminate\Support\Str::of($host->name)->before(' ')->toString();
@endphp
<x-dash area="host" :title="__('booking::host.overview.title', ['name' => $firstName])" :subtitle="now()->translatedFormat('l j F')">
    <x-slot:actions>
        @if ($profile?->status->value === 'verified')
            <a class="btn btn-secondary" href="{{ route('hosts.show', $profile) }}">{{ __('booking::host.overview.public_page') }}</a>
        @endif
        <a class="btn btn-primary" href="{{ route('host.experiences.create') }}">{{ __('booking::host.overview.new_experience') }}</a>
    </x-slot:actions>

    {{-- Verification banner --}}
    @if (! $profile)
        <div class="rise mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p>{{ __('booking::host.banner.no_profile') }}</p>
            <a class="btn btn-secondary" href="{{ route('profile.edit') }}">{{ __('booking::host.banner.complete_profile') }}</a>
        </div>
    @elseif ($profile->status->value !== 'verified')
        <div class="rise mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p><strong>{{ __('booking::host.banner.'.$profile->status->value) }}</strong> {{ __('booking::host.banner.'.$profile->status->value.'_text') }}</p>
            <a class="btn btn-secondary" href="{{ route('host.verification.show') }}">{{ __('booking::host.banner.go') }}</a>
        </div>
    @endif

    {{-- Key numbers --}}
    <div class="stagger grid grid-cols-2 gap-3 lg:grid-cols-4">
        <a href="{{ route('host.bookings.index') }}" class="card-link">
            <p class="text-sm text-zinc-600">{{ __('booking::host.kpi.pending') }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $pending->count() }}</p>
            <p class="mt-1 text-xs text-zinc-600">{{ $pending->isNotEmpty() ? __('booking::host.kpi.pending_hint') : __('booking::host.kpi.pending_none') }}</p>
        </a>
        <a href="{{ route('host.calendar') }}" class="card-link">
            <p class="text-sm text-zinc-600">{{ __('booking::host.kpi.upcoming') }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $upcoming->count() }}</p>
            <p class="mt-1 truncate text-xs text-zinc-600">{{ $next ? __('booking::host.kpi.next', ['date' => $next->date->translatedFormat('D j M').' '.substr($next->start_time, 0, 5)]) : __('booking::host.kpi.upcoming_none') }}</p>
        </a>
        <a href="{{ route('host.earnings', ['month' => now()->format('Y-m')]) }}" class="card-link">
            <p class="text-sm text-zinc-600">{{ __('booking::host.kpi.earned') }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $money($earned_month) }}</p>
            <p class="mt-1 text-xs text-zinc-600">{{ now()->translatedFormat('F') }}</p>
        </a>
        <a href="{{ route('host.clients.index') }}" class="card-link">
            <p class="text-sm text-zinc-600">{{ __('booking::host.kpi.clients') }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $clients }}</p>
            <p class="mt-1 text-xs text-zinc-600">{{ __('booking::host.kpi.completed', ['n' => $completed]) }}</p>
        </a>
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="space-y-6">
            {{-- Needs an answer --}}
            <section class="panel" aria-labelledby="answer-title" data-reveal>
                <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3">
                    <h2 id="answer-title" class="text-base">{{ __('booking::host.overview.needs_answer') }}</h2>
                    @if ($pending->isNotEmpty())<span class="badge-accent">{{ $pending->count() }}</span>@endif
                </div>
                @forelse ($pending as $b)
                    <article class="flex flex-wrap items-center gap-4 border-b border-zinc-100 px-5 py-4 last:border-0">
                        <x-avatar :user="$b->tourist" size="h-10 w-10" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $b->tourist->name }} <span class="font-normal text-zinc-600">· {{ $b->experience->title }}</span></p>
                            <p class="text-sm text-zinc-600">{{ $b->date->translatedFormat('D j M') }}, {{ substr($b->start_time, 0, 5) }}-{{ substr($b->end_time, 0, 5) }} · {{ __('booking::messages.people', ['n' => $b->group_size]) }} · {{ $money($b->displayed_price) }}</p>
                            <p class="mt-0.5 text-xs font-medium text-amber-800">{{ __('booking::host.overview.answer_in') }} <time data-countdown="{{ $b->expires_at->toIso8601String() }}" data-expired="{{ __('booking::host.overview.expired') }}" datetime="{{ $b->expires_at->toIso8601String() }}">{{ $b->expires_at->diffForHumans(null, true) }}</time></p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('host.bookings.accept', $b) }}">@csrf<button class="btn btn-primary" type="submit">{{ __('booking::messages.accept') }}</button></form>
                            <form method="POST" action="{{ route('host.bookings.decline', $b) }}">@csrf<button class="btn btn-secondary" type="submit">{{ __('booking::messages.decline') }}</button></form>
                        </div>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-zinc-600">{{ __('booking::host.overview.all_answered') }}</p>
                @endforelse
            </section>

            {{-- Next seven days --}}
            <section class="panel" aria-labelledby="agenda-title" data-reveal>
                <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3">
                    <h2 id="agenda-title" class="text-base">{{ __('booking::host.overview.agenda') }}</h2>
                    <a class="text-sm font-medium" href="{{ route('host.calendar') }}">{{ __('booking::host.overview.open_calendar') }}</a>
                </div>
                @forelse ($agenda as $day => $items)
                    <div class="border-b border-zinc-100 px-5 py-3 last:border-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-600">{{ \Carbon\Carbon::parse($day)->isToday() ? __('booking::host.overview.today') : \Carbon\Carbon::parse($day)->translatedFormat('l j M') }}</p>
                        <ul class="mt-2 space-y-2">
                            @foreach ($items as $b)
                                <li>
                                    <a href="{{ route('bookings.show', $b) }}" class="flex items-center gap-3 rounded-lg p-2 no-underline transition-colors hover:bg-zinc-50">
                                        <span class="w-24 shrink-0 text-sm font-medium tabular-nums text-zinc-900">{{ substr($b->start_time, 0, 5) }}-{{ substr($b->end_time, 0, 5) }}</span>
                                        <x-avatar :user="$b->tourist" size="h-8 w-8 text-xs" />
                                        <span class="min-w-0 flex-1 truncate text-sm text-zinc-900">{{ $b->tourist->name }} <span class="text-zinc-600">· {{ $b->experience->title }}</span></span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-zinc-600">{{ __('booking::host.overview.nothing_scheduled') }}</p>
                @endforelse
            </section>

            {{-- Last six months --}}
            <section class="panel p-5" aria-labelledby="chart-title" data-reveal>
                <div class="flex items-center justify-between">
                    <h2 id="chart-title" class="text-base">{{ __('booking::host.overview.last_months') }}</h2>
                    <a class="text-sm font-medium" href="{{ route('host.earnings') }}">{{ __('booking::host.overview.details') }}</a>
                </div>
                <ul class="mt-6 grid h-40 grid-cols-6 items-end gap-3" role="list">
                    @foreach ($months as $m)
                        @php($step = $barSteps[(int) round(((float) $m['net'] / $maxNet) * 10)])
                        <li class="flex h-full flex-col items-center justify-end gap-1.5">
                            <span class="text-xs font-medium tabular-nums text-zinc-700">{{ (float) $m['net'] > 0 ? number_format((float) $m['net'], 0) : '' }}</span>
                            <span class="grow-y w-full max-w-10 rounded-t-md {{ (float) $m['net'] > 0 ? 'bg-accent-600' : 'bg-zinc-200' }} {{ $step }}"><span class="sr-only">{{ $m['label'] }}: {{ $money($m['net']) }}</span></span>
                            <span class="text-xs text-zinc-600" aria-hidden="true">{{ $m['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            {{-- Setup checklist --}}
            @if ($progress < 100)
                <section class="panel p-5" aria-labelledby="setup-title" data-reveal>
                    <div class="flex items-center justify-between">
                        <h2 id="setup-title" class="text-base">{{ __('booking::host.checklist.title') }}</h2>
                        <span class="text-sm font-medium tabular-nums">{{ $progress }}%</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('booking::host.checklist.title') }}">
                        <div class="grow-x h-full rounded-full bg-accent-600 {{ $widthSteps[$progress] ?? 'w-full' }}"></div>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ([
                            'profile' => ['profile.edit', 'booking::host.checklist.profile'],
                            'verified' => ['host.verification.show', 'booking::host.checklist.verified'],
                            'experience' => ['host.experiences.create', 'booking::host.checklist.experience'],
                            'hours' => ['host.availability.index', 'booking::host.checklist.hours'],
                            'photo' => ['profile.edit', 'booking::host.checklist.photo'],
                        ] as $key => [$route, $label])
                            <li>
                                <a href="{{ route($route) }}" class="flex items-center gap-2.5 rounded-md py-1 no-underline {{ $checklist[$key] ? 'text-zinc-500' : 'text-zinc-900 hover:text-accent-700' }}">
                                    @if ($checklist[$key])
                                        <svg viewBox="0 0 20 20" class="h-5 w-5 shrink-0 text-olive-700" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span class="line-through">{{ __($label) }}</span><span class="sr-only"> ({{ __('booking::host.checklist.done') }})</span>
                                    @else
                                        <span class="h-5 w-5 shrink-0 rounded-full border-2 border-zinc-300" aria-hidden="true"></span>
                                        <span class="font-medium">{{ __($label) }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Conversations --}}
            <section class="panel" aria-labelledby="chats-title" data-reveal>
                <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3">
                    <h2 id="chats-title" class="text-base">{{ __('booking::host.overview.messages') }}</h2>
                    <a class="text-sm font-medium" href="{{ route('host.inbox') }}">{{ __('booking::host.overview.all') }}</a>
                </div>
                <ul>
                    @forelse ($recent as $t)
                        <li class="border-b border-zinc-100 last:border-0">
                            <a href="{{ route('messages.show', $t['booking']) }}" class="flex items-start gap-3 px-5 py-3 no-underline transition-colors hover:bg-zinc-50">
                                <x-avatar :user="$t['booking']->tourist" size="h-9 w-9 text-xs" />
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2"><span class="truncate text-sm font-medium text-zinc-900">{{ $t['booking']->tourist->name }}</span>
                                        @if ($t['unread'])<span class="badge-accent" aria-label="{{ $t['unread'] }} {{ __('common.new') }}">{{ $t['unread'] }}</span>@endif</span>
                                    <span class="block truncate text-sm text-zinc-600">{{ \Illuminate\Support\Str::limit($t['last']->original_text, 60) }}</span>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-zinc-600">{{ __('booking::host.overview.no_messages') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-dash>
