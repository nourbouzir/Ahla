<x-layouts.app :title="__('auth::messages.reset_title')">
    <form method="POST" action="{{ route('password.update') }}" class="card mx-auto max-w-md">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <h1 class="text-2xl font-bold">{{ __('auth::messages.reset_title') }}</h1>
        <x-input name="email" type="email" :label="__('auth::messages.email')" :value="$email" autocomplete="username" required />
        <x-input name="password" type="password" :label="__('auth::messages.new_password')" autocomplete="new-password" required />
        <x-input name="password_confirmation" type="password" :label="__('auth::messages.password_confirmation')" autocomplete="new-password" required />
        <button class="btn btn-primary mt-6 w-full" type="submit">{{ __('auth::messages.reset_submit') }}</button>
    </form>
</x-layouts.app>
