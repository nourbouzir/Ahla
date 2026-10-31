<x-layouts.app :title="__('auth::messages.login_title')">
    <x-signed-in-notice />
    <div class="mx-auto grid max-w-3xl items-start gap-6 md:grid-cols-[1fr_1fr]">
        <form method="POST" action="{{ route('login.store') }}" class="card rise p-6">
            @csrf
            <h1 class="text-2xl">{{ __('auth::messages.login_title') }}</h1>
            <x-input name="email" type="email" :label="__('auth::messages.email')" autocomplete="username" required />
            <x-input name="password" type="password" :label="__('auth::messages.password')" autocomplete="current-password" required />
            <label class="check mt-3"><input type="checkbox" name="remember" value="1"> {{ __('auth::messages.remember') }}</label>
            <button class="btn btn-primary mt-4 w-full" type="submit">{{ __('auth::messages.login_submit') }}</button>
            <p class="mt-4 text-sm"><a href="{{ route('password.request') }}">{{ __('auth::messages.forgot') }}</a></p>
            <p class="mt-2 text-sm text-zinc-600">{{ __('auth::messages.no_account') }} <a class="font-medium" href="{{ route('register') }}">{{ __('common.nav.register') }}</a></p>
        </form>

        @if (config('ahla.show_demo_logins'))
            <aside class="rise rise-2 rounded-xl border border-dashed border-zinc-300 bg-white p-6" aria-labelledby="demo-title" id="demo-logins">
                <h2 id="demo-title" class="text-base">{{ __('auth::messages.demo_title') }}</h2>
                <p class="mt-1 text-sm text-zinc-600">{{ __('auth::messages.demo_text') }}</p>
                <ul class="mt-4 divide-y divide-zinc-100">
                    @foreach ([['admin@ahla.test', 'demo_admin'], ['amel@ahla.test', 'demo_guide'], ['tourist@ahla.test', 'demo_tourist']] as [$email, $role])
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span class="min-w-0"><span class="block text-xs font-medium uppercase tracking-wide text-zinc-600">{{ __("auth::messages.$role") }}</span><code class="block truncate text-sm text-zinc-900">{{ $email }}</code></span>
                            {{-- A real form: one click logs in, no JavaScript needed (demo accounts are public by design, never shown in production). --}}
                            <form method="POST" action="{{ route('login.store') }}">@csrf
                                <input type="hidden" name="email" value="{{ $email }}"><input type="hidden" name="password" value="password">
                                <button type="submit" class="btn btn-secondary h-8 shrink-0 px-3 text-xs">{{ __('auth::messages.demo_use') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-sm text-zinc-600">{{ __('auth::messages.demo_password') }}: <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-zinc-900">password</code></p>
            </aside>
        @endif
    </div>
</x-layouts.app>
