<x-layouts.app :title="__('notification::messages.title')">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ __('notification::messages.title') }}</h1>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
            <button class="btn btn-secondary" type="submit">{{ __('notification::messages.read_all') }}</button></form>
    </div>

    <ul class="mt-4 space-y-3">
        @forelse ($notifications as $n)
            @php($kind = $n->data['kind'] ?? 'unknown')
            <li class="card {{ $n->read_at ? '' : 'border-brand-600' }}">
                <a class="font-semibold underline" href="{{ route('notifications.open', $n->id) }}">{{ __("notification::messages.kinds.$kind.title", $n->data['vars'] ?? []) }}</a>
                @unless ($n->read_at)<span class="badge ms-2">{{ __('notification::messages.new') }}</span>@endunless
                <p class="mt-1">{{ __("notification::messages.kinds.$kind.body", $n->data['vars'] ?? []) }}</p>
                <p class="mt-1 text-xs text-slate-600"><time datetime="{{ $n->created_at->toIso8601String() }}">{{ $n->created_at->format('Y-m-d H:i') }}</time></p>
            </li>
        @empty
            <li>{{ __('notification::messages.none') }}</li>
        @endforelse
    </ul>
    {{ $notifications->links() }}

    <form method="POST" action="{{ route('notifications.preferences') }}" class="card mt-6">@csrf
        <label class="flex items-center gap-2"><input type="checkbox" name="email_notifications" value="1" @checked(auth()->user()->email_notifications)> {{ __('notification::messages.email_pref') }}</label>
        <button class="btn btn-secondary mt-3" type="submit">{{ __('common.actions.save') }}</button>
    </form>
</x-layouts.app>
