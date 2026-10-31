<x-layouts.app :title="__('auth::messages.login_title')">
    <form method="POST" action="{{ route('login') }}" class="card mx-auto max-w-md">
        @csrf
        <h1 class="text-2xl font-bold">{{ __('auth::messages.login_title') }}</h1>
        <x-input name="email" type="email" :label="__('auth::messages.email')" autocomplete="username" required />
        <x-input name="password" type="password" :label="__('auth::messages.password')" autocomplete="current-password" required />
        <label class="mt-4 flex items-center gap-2"><input type="checkbox" name="remember" value="1"> {{ __('auth::messages.remember') }}</label>
        <button class="btn btn-primary mt-6 w-full" type="submit">{{ __('auth::messages.login_submit') }}</button>
        <p class="mt-4 text-sm"><a class="underline" href="{{ route('password.request') }}">{{ __('auth::messages.forgot') }}</a></p>
        <p class="mt-2 text-sm">{{ __('auth::messages.no_account') }} <a class="underline" href="{{ route('register') }}">{{ __('common.nav.register') }}</a></p>
    </form>
</x-layouts.app>
