@props(['area', 'title' => null, 'subtitle' => null, 'heading' => true])
{{--
    The shell shared by the guide space and the admin space: a sticky side menu on large screens,
    a scrolling tab bar on phones, and a page heading with room for actions. One place to change both.
--}}
@php
    $user = auth()->user();
    $icons = [
        'home' => 'M3 10.5L12 3l9 7.5M5 9.5V20h14V9.5M10 20v-6h4v6',
        'list' => 'M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01',
        'calendar' => 'M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z',
        'users' => 'M16 19v-1a4 4 0 00-4-4H7a4 4 0 00-4 4v1M9.5 10a3 3 0 100-6 3 3 0 000 6zM21 19v-1a4 4 0 00-3-3.9M15.5 4.2a3 3 0 010 5.6',
        'chat' => 'M4 5h16v11H9l-5 4z',
        'compass' => 'M12 21a9 9 0 100-18 9 9 0 000 18zM15.5 8.5l-2 5-5 2 2-5z',
        'clock' => 'M12 21a9 9 0 100-18 9 9 0 000 18zM12 7v5l3 2',
        'wallet' => 'M3 7h16a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2zM3 7V6a2 2 0 012-2h11M16 14h2',
        'shield' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z M9 12l2 2 4-4',
        'chart' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
        'history' => 'M3 12a9 9 0 109-9 9 9 0 00-6.4 2.6L3 8M3 3v5h5M12 8v5l3 2',
        'user' => 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 20a8 8 0 0116 0',
    ];

    if ($area === 'host') {
        $pending = \App\Modules\Booking\Models\Booking::where('host_id', $user->id)->where('status', 'requested')->count();
        $unread = \App\Modules\Messaging\Models\Message::where('recipient_id', $user->id)->whereNull('read_at')->count();
        $items = [
            ['host.dashboard', 'host.dashboard', 'home', 'booking::host.nav.overview', null],
            ['host.bookings.index', 'host.bookings.*', 'list', 'booking::host.nav.bookings', $pending],
            ['host.calendar', 'host.calendar', 'calendar', 'booking::host.nav.calendar', null],
            ['host.clients.index', 'host.clients.*', 'users', 'booking::host.nav.clients', null],
            ['host.inbox', 'host.inbox', 'chat', 'booking::host.nav.inbox', $unread],
            ['host.experiences.index', 'host.experiences.*', 'compass', 'booking::host.nav.experiences', null],
            ['host.availability.index', 'host.availability.*', 'clock', 'booking::host.nav.availability', null],
            ['host.earnings', 'host.earnings*', 'wallet', 'booking::host.nav.earnings', null],
            ['host.verification.show', 'host.verification.*', 'shield', 'booking::host.nav.verification', null],
        ];
        $menuTitle = __('booking::host.nav.title');
    } else {
        $items = [
            ['admin.dashboard', 'admin.dashboard', 'home', 'admin::dash.nav.overview', null],
            ['admin.users.index', 'admin.users.*', 'users', 'admin::dash.nav.users', null],
            ['admin.hosts.index', 'admin.hosts.*', 'compass', 'admin::dash.nav.guides', null],
            ['admin.verifications.index', 'admin.verifications.*', 'shield', 'admin::dash.nav.verifications', null],
            ['admin.bookings.index', 'admin.bookings.*', 'list', 'admin::dash.nav.bookings', null],
            ['admin.revenue', 'admin.revenue*', 'chart', 'admin::dash.nav.revenue', null],
            ['admin.audit', 'admin.audit', 'history', 'admin::dash.nav.audit', null],
        ];
        $menuTitle = __('admin::dash.nav.title');
    }
@endphp
<x-layouts.app :title="$title" wide>
    <div class="grid items-start gap-6 lg:grid-cols-[13.5rem_1fr] lg:gap-10">
        <nav class="lg:sticky lg:top-20" aria-label="{{ $menuTitle }}" @if ($area === 'host') data-summary="{{ route('host.dashboard.summary') }}" @endif>
            <p class="mb-2 hidden px-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 lg:block">{{ $menuTitle }}</p>
            <ul class="-mx-4 flex gap-1 overflow-x-auto px-4 pb-2 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0">
                @foreach ($items as [$route, $pattern, $icon, $label, $count])
                    @php($active = request()->routeIs($pattern))
                    @if (Route::has($route))
                        <li class="shrink-0">
                            <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                                class="group flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium no-underline transition-colors {{ $active ? 'bg-accent-900 text-white' : 'text-zinc-700 hover:bg-accent-50 hover:text-accent-900' }}">
                                <svg viewBox="0 0 24 24" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-white' : 'text-zinc-500 group-hover:text-zinc-800' }}" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$icon] }}"/></svg>
                                <span class="whitespace-nowrap">{{ __($label) }}</span>
                                @if ($count)<span class="ms-auto rounded-full {{ $active ? 'bg-white/20 text-white' : 'bg-clay-700 text-white' }} px-1.5 text-xs font-semibold tabular-nums" data-live-count="{{ $route }}"><span class="sr-only">{{ __('common.new') }}: </span><span data-n>{{ $count }}</span></span>@endif
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>

        <div class="min-w-0">
            @if ($title && $heading)
                <header class="rise mb-6 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 class="text-2xl md:text-3xl">{{ $title }}</h1>
                        @if ($subtitle)<p class="mt-1 text-zinc-600">{{ $subtitle }}</p>@endif
                    </div>
                    @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
                </header>
            @endif
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
