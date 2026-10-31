@props(['title' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#174a80">
    <title>@if ($title){{ $title }} - @endif{{ __('common.app_name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-2 focus:top-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">{{ __('common.skip_to_content') }}</a>

    @php($user = auth()->user())
    @php($role = $user?->role->value)
    @php($link = 'rounded-md px-2 py-2 text-sm font-medium text-zinc-600 no-underline transition-colors hover:text-zinc-900')
    <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/85 backdrop-blur supports-[backdrop-filter]:bg-white/70">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-2.5">
            <a href="{{ route('home') }}" class="rounded-lg no-underline" aria-label="{{ __('common.app_name') }} - {{ __('common.nav.home') }}"><x-logo /></a>
            <button id="nav-toggle" type="button" class="btn btn-secondary md:hidden" aria-expanded="false" aria-controls="nav-menu">{{ __('common.menu') }}</button>
            <nav id="nav-menu" aria-label="{{ __('common.menu') }}" class="hidden w-full flex-col gap-1 pb-2 md:flex md:w-auto md:flex-1 md:flex-row md:items-center md:pb-0">
                <div class="flex flex-col md:flex-row md:items-center md:gap-1">
                    @if (Route::has('search'))
                        <a class="{{ $link }}" href="{{ route('search') }}" @if (request()->routeIs('search')) aria-current="page" @endif>{{ __('common.nav.search') }}</a>
                    @endif
                    @auth
                        @if ($role === 'tourist' && Route::has('bookings.index'))
                            <a class="{{ $link }}" href="{{ route('bookings.index') }}">{{ __('common.nav.bookings') }}</a>
                        @endif
                        @if ($role === 'host')
                            <a class="{{ $link }}" href="{{ route('host.dashboard') }}" @if (request()->routeIs('host.*')) aria-current="page" @endif>{{ __('common.nav.guide_space') }}</a>
                        @endif
                        @if ($role === 'admin')
                            <a class="{{ $link }}" href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.*')) aria-current="page" @endif>{{ __('common.nav.admin') }}</a>
                        @endif
                    @endauth
                </div>

                <div class="flex flex-col gap-1 md:ms-auto md:flex-row md:items-center md:gap-2">
                    <div class="flex items-center" role="group" aria-label="{{ __('common.language') }}">
                        @foreach (__('common.languages') as $code => $label)
                            <form method="POST" action="{{ route('locale.update', $code) }}">@csrf
                                <button type="submit" lang="{{ $code }}" @if (app()->getLocale() === $code) aria-current="true" @endif
                                    class="min-h-9 rounded-md px-2 text-sm transition-colors {{ app()->getLocale() === $code ? 'bg-zinc-100 font-semibold text-zinc-900' : 'text-zinc-600 hover:text-zinc-900' }}">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>
                    @auth
                        @php($unread = $user->unreadNotifications()->count())
                        <a class="{{ $link }}" href="{{ route('notifications.index') }}">{{ __('notification::messages.title') }}@if ($unread > 0) <span class="badge-accent ms-1" aria-label="{{ $unread }} {{ __('notification::messages.new') }}">{{ $unread }}</span>@endif</a>
                        <a class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-medium text-zinc-900 no-underline hover:bg-zinc-100" href="{{ route('profile.edit') }}">
                            <x-avatar :user="$user" size="h-7 w-7 text-xs" /> {{ __('common.nav.profile') }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="{{ $link }} w-full text-start" type="submit">{{ __('common.nav.logout') }}</button>
                        </form>
                    @else
                        <a class="{{ $link }}" href="{{ route('login') }}">{{ __('common.nav.login') }}</a>
                        <a class="btn btn-dark" href="{{ route('register') }}">{{ __('common.nav.register') }}</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main id="main" class="mx-auto w-full flex-1 px-4 py-8 {{ $wide ? 'max-w-6xl' : 'max-w-5xl' }}">
        @if (session('status'))
            <div role="status" class="mb-6 flex items-start gap-3 rounded-lg border border-olive-200 bg-olive-100 p-3 text-sm text-olive-700">
                <svg viewBox="0 0 20 20" class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif
        {{ $slot }}
    </main>

    <footer class="border-t border-zinc-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 text-sm md:grid-cols-[1.5fr_1fr_1fr]">
            <div>
                <x-logo />
                <p class="mt-3 max-w-xs text-zinc-600">{{ __('common.tagline') }}</p>
            </div>
            <nav aria-label="{{ __('common.footer_nav') }}">
                <h2 class="text-sm font-semibold">{{ __('common.footer_explore') }}</h2>
                <ul class="mt-3 space-y-2">
                    <li><a class="text-zinc-600 hover:text-zinc-900" href="{{ route('search') }}">{{ __('common.nav.search') }}</a></li>
                    <li><a class="text-zinc-600 hover:text-zinc-900" href="{{ route('register', ['role' => 'host']) }}">{{ __('common.home.cta_host') }}</a></li>
                </ul>
            </nav>
            <div>
                <h2 class="text-sm font-semibold">{{ __('common.footer_account') }}</h2>
                <ul class="mt-3 space-y-2">
                    @auth
                        <li><a class="text-zinc-600 hover:text-zinc-900" href="{{ route('profile.edit') }}">{{ __('common.nav.profile') }}</a></li>
                    @else
                        <li><a class="text-zinc-600 hover:text-zinc-900" href="{{ route('login') }}">{{ __('common.nav.login') }}</a></li>
                        <li><a class="text-zinc-600 hover:text-zinc-900" href="{{ route('register') }}">{{ __('common.nav.register') }}</a></li>
                    @endauth
                </ul>
            </div>
        </div>
        <div class="border-t border-zinc-200 py-4 text-center text-xs text-zinc-600">{{ __('common.footer') }}</div>
    </footer>
</body>
</html>
