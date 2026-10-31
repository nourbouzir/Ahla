<x-layouts.app :title="__('auth::messages.forgot_title')">
    <form method="POST" action="{{ route('password.email') }}" class="card mx-auto max-w-md">
        @csrf
        <h1 class="text-2xl font-bold">{{ __('auth::messages.forgot_title') }}</h1>
        <x-input name="email" type="email" :label="__('auth::messages.email')" autocomplete="email" required />
        <button class="btn btn-primary mt-6 w-full" type="submit">{{ __('auth::messages.send_link') }}</button>
    </form>
</x-layouts.app>
