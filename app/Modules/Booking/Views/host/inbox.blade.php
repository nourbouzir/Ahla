<x-dash area="host" :title="__('booking::host.inbox.title')" :subtitle="$totalUnread ? __('booking::host.inbox.unread_count', ['n' => $totalUnread]) : __('booking::host.inbox.caught_up')">
    <x-slot:actions>
        <a href="{{ route('host.inbox', $unreadOnly ? [] : ['unread' => 1]) }}" class="btn btn-secondary" @if ($unreadOnly) aria-pressed="true" @endif>
            {{ $unreadOnly ? __('booking::host.inbox.show_all') : __('booking::host.inbox.unread_only') }}
        </a>
    </x-slot:actions>

    <p class="mb-4 max-w-2xl text-sm text-zinc-600">{{ __('booking::host.inbox.explain') }}</p>

    @if ($threads->isEmpty())
        <div class="panel px-6 py-16 text-center">
            <p class="text-lg font-semibold">{{ $unreadOnly ? __('booking::host.inbox.none_unread') : __('booking::host.inbox.empty') }}</p>
            <p class="mx-auto mt-1 max-w-sm text-zinc-600">{{ __('booking::host.inbox.empty_text') }}</p>
        </div>
    @else
        <ul class="stagger panel divide-y divide-zinc-100">
            @foreach ($threads as $t)
                @php($b = $t['booking'])
                <li>
                    <a href="{{ route('messages.show', $b) }}" class="flex items-start gap-4 px-5 py-4 no-underline transition-colors hover:bg-zinc-50">
                        <span class="relative">
                            <x-avatar :user="$b->tourist" size="h-11 w-11" />
                            @if ($t['unread'])<span class="absolute -end-0.5 -top-0.5 h-3 w-3 rounded-full bg-accent-600 ring-2 ring-white" aria-hidden="true"></span>@endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-baseline justify-between gap-3">
                                <span class="truncate font-medium text-zinc-900 {{ $t['unread'] ? 'font-semibold' : '' }}">{{ $b->tourist->name }}</span>
                                <time class="shrink-0 text-xs text-zinc-600" datetime="{{ $t['at']->toIso8601String() }}">{{ $t['at']->diffForHumans(short: true) }}</time>
                            </span>
                            <span class="block truncate text-sm text-zinc-600">{{ $b->experience->title }} · {{ $b->date->translatedFormat('j M') }} · <span class="{{ $b->status->value === 'requested' ? 'text-accent-700' : '' }}">{{ __('booking::messages.status.'.$b->status->value) }}</span></span>
                            <span class="mt-1 block truncate text-sm {{ $t['unread'] ? 'font-medium text-zinc-900' : 'text-zinc-600' }}">
                                @if ($t['last'])
                                    @if ($t['last']->sender_id === auth()->id())<span class="text-zinc-500">{{ __('booking::host.inbox.you') }}</span>@endif{{ \Illuminate\Support\Str::limit($t['last']->original_text, 90) }}
                                @else
                                    <span class="italic">{{ __('booking::host.inbox.no_messages') }}</span>
                                @endif
                            </span>
                        </span>
                        @if ($t['unread'])<span class="badge-accent mt-1" aria-label="{{ $t['unread'] }} {{ __('common.new') }}">{{ $t['unread'] }}</span>@endif
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-dash>
