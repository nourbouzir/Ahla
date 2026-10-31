<x-layouts.app :title="__('admin::messages.verifications')">
    <h1 class="text-2xl font-bold">{{ __('admin::messages.verifications') }}</h1>

    <nav class="mt-3 flex gap-3" aria-label="{{ __('admin::messages.filter') }}">
        @foreach (['open', 'decided', 'all'] as $s)
            <a class="underline {{ $status === $s ? 'font-bold' : '' }}" @if ($status === $s) aria-current="page" @endif
                href="{{ route('admin.verifications.index', ['status' => $s]) }}">{{ __("admin::messages.filter_$s") }}</a>
        @endforeach
        <a class="ms-auto underline" href="{{ route('admin.audit') }}">{{ __('admin::messages.audit') }}</a>
    </nav>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full text-start">
            <caption class="sr-only">{{ __('admin::messages.verifications') }}</caption>
            <thead><tr class="border-b">
                <th scope="col" class="p-2 text-start">{{ __('admin::messages.host') }}</th>
                <th scope="col" class="p-2 text-start">{{ __('admin::messages.city') }}</th>
                <th scope="col" class="p-2 text-start">{{ __('admin::messages.request_status') }}</th>
                <th scope="col" class="p-2 text-start">{{ __('admin::messages.sent') }}</th>
                <th scope="col" class="p-2"><span class="sr-only">{{ __('admin::messages.open') }}</span></th>
            </tr></thead>
            <tbody>
            @forelse ($requests as $r)
                <tr class="border-b">
                    <td class="p-2">{{ $r->hostProfile->display_name }}</td>
                    <td class="p-2">{{ $r->hostProfile->city }}</td>
                    <td class="p-2"><span class="badge">{{ __('admin::messages.request.'.$r->status->value) }}</span></td>
                    <td class="p-2">{{ $r->created_at->format('Y-m-d') }}</td>
                    <td class="p-2"><a class="underline" href="{{ route('admin.verifications.show', $r) }}">{{ __('admin::messages.open') }}</a></td>
                </tr>
            @empty
                <tr><td class="p-2" colspan="5">{{ __('admin::messages.none') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}

    <h2 class="mt-8 text-xl font-semibold">{{ __('admin::messages.live_hosts') }}</h2>
    <ul class="mt-3 space-y-3">
        @foreach ($hosts as $h)
            <li class="card flex flex-wrap items-center justify-between gap-3">
                <span>{{ $h->display_name }} - {{ $h->city }} <span class="badge">{{ __('auth::messages.status.'.$h->status->value) }}</span></span>
                <form method="POST" action="{{ route('admin.hosts.status', $h) }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    @if ($h->status->value === 'verified')
                        <input type="hidden" name="status" value="suspended">
                        <label class="sr-only" for="reason-{{ $h->id }}">{{ __('admin::messages.reason') }}</label>
                        <input class="field !mt-0 w-56" id="reason-{{ $h->id }}" name="reason" placeholder="{{ __('admin::messages.reason') }}" required>
                        <button class="btn btn-danger" type="submit">{{ __('admin::messages.suspend') }}</button>
                    @else
                        <input type="hidden" name="status" value="verified">
                        <button class="btn btn-secondary" type="submit">{{ __('admin::messages.reinstate') }}</button>
                    @endif
                </form>
            </li>
        @endforeach
    </ul>
</x-layouts.app>
