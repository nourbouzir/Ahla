<x-layouts.app :title="__('auth::messages.register_title')">
    <form method="POST" action="{{ route('register') }}" class="card mx-auto max-w-md">
        @csrf
        <h1 class="text-2xl font-bold">{{ __('auth::messages.register_title') }}</h1>
        <x-input name="name" :label="__('auth::messages.name')" autocomplete="name" required />
        <x-input name="email" type="email" :label="__('auth::messages.email')" autocomplete="email" required />
        <x-input name="password" type="password" :label="__('auth::messages.password')" autocomplete="new-password" required />
        <x-input name="password_confirmation" type="password" :label="__('auth::messages.password_confirmation')" autocomplete="new-password" required />
        <fieldset class="mt-4">
            <legend class="label">{{ __('auth::messages.role') }}</legend>
            @foreach (['tourist', 'host'] as $role)
                <label class="mt-2 flex items-center gap-2">
                    <input type="radio" name="role" value="{{ $role }}" @checked(old('role', 'tourist') === $role)>
                    {{ __("auth::messages.role_$role") }}
                </label>
            @endforeach
        </fieldset>
        <button class="btn btn-primary mt-6 w-full" type="submit">{{ __('auth::messages.register_submit') }}</button>
        <p class="mt-4 text-sm">{{ __('auth::messages.have_account') }} <a class="underline" href="{{ route('login') }}">{{ __('common.nav.login') }}</a></p>
    </form>
</x-layouts.app>
