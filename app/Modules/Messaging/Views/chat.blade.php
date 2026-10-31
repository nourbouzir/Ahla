<x-layouts.app :title="__('messaging::messages.title')">
    <a class="underline" href="{{ route('bookings.show', $booking) }}">{{ __('common.actions.back') }}</a>
    <h1 class="mt-2 text-2xl font-bold">{{ __('messaging::messages.with', ['name' => $other]) }}</h1>
    <p class="text-slate-600">{{ $booking->experience->title }} - {{ $booking->date->format('Y-m-d') }}</p>

    <ol id="chat" class="mx-auto mt-4 max-w-2xl space-y-3" aria-label="{{ __('messaging::messages.title') }}" aria-live="polite"
        data-poll="{{ route('messages.poll', $booking) }}" data-last="{{ $lastId }}"
        data-original="{{ __('messaging::messages.show_original') }}" data-translated="{{ __('messaging::messages.translated') }}"
        data-pending="{{ __('messaging::messages.pending') }}" data-unavailable="{{ __('messaging::messages.unavailable') }}">
        @foreach ($messages as $m)
            <li class="max-w-[85%] rounded-xl border p-3 {{ $m['mine'] ? 'ms-auto border-door-200 bg-door-50' : 'me-auto border-door-100 bg-white' }}" data-id="{{ $m['id'] }}">
                <p class="flex items-center gap-2 text-xs text-slate-600">@if ($m['avatar'])<img src="{{ $m['avatar'] }}" alt="" class="h-6 w-6 rounded-full object-cover" width="24" height="24">@else<span aria-hidden="true" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-door-700 text-[10px] font-semibold text-white">{{ $m['initials'] }}</span>@endif<span>{{ $m['sender'] }}</span> - <time>{{ $m['at'] }}</time></p>
                <p class="mt-1 whitespace-pre-wrap break-words" lang="{{ $m['status'] === 'translated' ? '' : $m['language'] }}">{{ $m['text'] }}</p>
                @if ($m['status'] === 'translated')
                    <details class="mt-1 text-sm"><summary class="cursor-pointer underline">{{ __('messaging::messages.show_original') }}</summary>
                        <p class="mt-1 whitespace-pre-wrap break-words" lang="{{ $m['language'] }}">{{ $m['original'] }}</p></details>
                @elseif ($m['status'] === 'pending')
                    <p class="mt-1 text-xs text-slate-600">{{ __('messaging::messages.pending') }}</p>
                @elseif ($m['status'] === 'unavailable')
                    <p class="mt-1 text-xs text-slate-600">{{ __('messaging::messages.unavailable') }}</p>
                @endif
            </li>
        @endforeach
    </ol>
    <p id="chat-empty" class="mt-4 text-center text-slate-600 {{ $messages->isEmpty() ? '' : 'hidden' }}">{{ __('messaging::messages.empty') }}</p>

    @if ($canSend)
        <form method="POST" action="{{ route('messages.store', $booking) }}" class="mx-auto mt-6 max-w-2xl" id="chat-form">
            @csrf
            <label class="label" for="body">{{ __('messaging::messages.write') }}</label>
            <textarea class="field" id="body" name="body" rows="3" maxlength="1000" required></textarea>
            <button class="btn btn-primary mt-2" type="submit">{{ __('messaging::messages.send') }}</button>
        </form>
    @else
        <p class="mt-6 text-center text-slate-600">{{ __('messaging::messages.closed') }}</p>
    @endif
</x-layouts.app>
