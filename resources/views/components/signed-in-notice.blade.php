@auth
    @php($role = auth()->user()->role->value)
    {{-- Login and sign-up stay open while someone is logged in: say so, and make switching accounts obvious. --}}
    <div class="mx-auto mb-5 flex max-w-3xl flex-wrap items-center justify-between gap-3 rounded-xl border border-accent-200 bg-accent-50 p-4 text-sm text-accent-900" role="status">
        <p>{{ __('common.signed_in_as', ['name' => auth()->user()->name, 'role' => __("admin::dash.users.role_{$role}")]) }} {{ __('common.switch_hint') }}</p>
        <div class="flex gap-2">
            <a class="btn btn-secondary" href="{{ auth()->user()->role->value === 'host' ? route('host.dashboard') : (auth()->user()->role->value === 'admin' ? route('admin.dashboard') : route('home')) }}">{{ __('common.continue_as') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-dark" type="submit">{{ __('common.nav.logout') }}</button></form>
        </div>
    </div>
@endauth
