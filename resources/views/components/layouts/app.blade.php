@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@if ($title){{ $title }} - @endif{{ __('common.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-ink antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">{{ __('common.skip_to_content') }}</a>

    @php($role = auth()->user()?->role->value)
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="text-xl font-bold text-brand-600">{{ __('common.app_name') }}</a>
            <button id="nav-toggle" type="button" class="btn btn-secondary md:hidden" aria-expanded="false" aria-controls="nav-menu">{{ __('common.menu') }}</button>
            <nav id="nav-menu" aria-label="{{ __('common.menu') }}" class="hidden w-full flex-col gap-2 md:flex md:w-auto md:flex-row md:items-center md:gap-4">
                @if (Route::has('search'))
                    <a class="py-2" href="{{ route('search') }}">{{ __('common.nav.search') }}</a>
                @endif
                @auth
                    @if ($role === 'tourist' && Route::has('bookings.index'))
                        <a class="py-2" href="{{ route('bookings.index') }}">{{ __('common.nav.bookings') }}</a>
                    @endif
                    @if ($role === 'host' && Route::has('host.bookings.index'))
                        <a class="py-2" href="{{ route('host.bookings.index') }}">{{ __('common.nav.host_bookings') }}</a>
                    @endif
                    @if ($role === 'host' && Route::has('host.experiences.index'))
                        <a class="py-2" href="{{ route('host.experiences.index') }}">{{ __('common.nav.experiences') }}</a>
                    @endif
                    @if ($role === 'admin' && Route::has('admin.verifications.index'))
                        <a class="py-2" href="{{ route('admin.verifications.index') }}">{{ __('common.nav.admin') }}</a>
                    @endif
                    <a class="py-2" href="{{ route('profile.edit') }}">{{ __('common.nav.profile') }}</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="py-2 text-start underline" type="submit">{{ __('common.nav.logout') }}</button>
                    </form>
                @else
                    <a class="py-2" href="{{ route('login') }}">{{ __('common.nav.login') }}</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">{{ __('common.nav.register') }}</a>
                @endauth
                <div class="flex gap-1" role="group" aria-label="{{ __('common.language') }}">
                    @foreach (__('common.languages') as $code => $label)
                        <form method="POST" action="{{ route('locale.update', $code) }}">@csrf
                            <button type="submit" lang="{{ $code }}" @if (app()->getLocale() === $code) aria-current="true" @endif
                                class="min-h-11 rounded px-2 text-sm {{ app()->getLocale() === $code ? 'bg-brand-100 font-bold' : 'underline' }}">{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </nav>
        </div>
    </header>

    <main id="main" class="mx-auto max-w-5xl px-4 py-6">
        @if (session('status'))
            <div role="status" class="mb-4 rounded-lg border border-green-300 bg-green-50 p-3 text-green-900">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-red-900">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-5xl px-4 py-8 text-sm text-slate-600">{{ __('common.footer') }}</footer>
</body>
</html>
