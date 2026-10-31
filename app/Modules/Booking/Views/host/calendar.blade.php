@php
    $first = $month->startOfMonth();
    // Weeks start on Monday; Carbon dayOfWeekIso is 1 (Mon) to 7 (Sun).
    $offset = $first->dayOfWeekIso - 1;
    $days = $first->daysInMonth;
    $cells = $offset + $days;
    $rows = (int) ceil($cells / 7);
    $today = now()->toDateString();
    $chip = ['requested' => 'bg-accent-50 text-accent-900 ring-accent-200', 'confirmed' => 'bg-olive-100 text-olive-700 ring-olive-200', 'completed' => 'bg-zinc-100 text-zinc-700 ring-zinc-200'];
@endphp
<x-dash area="host" :title="__('booking::host.calendar.title')" :subtitle="$month->translatedFormat('F Y')">
    <x-slot:actions>
        <a class="btn btn-secondary" href="{{ route('host.calendar', ['month' => $prev]) }}" aria-label="{{ __('booking::host.calendar.prev') }}"><svg viewBox="0 0 20 20" class="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l-6 6 6 6"/></svg></a>
        <a class="btn btn-secondary" href="{{ route('host.calendar') }}">{{ __('booking::host.calendar.today') }}</a>
        <a class="btn btn-secondary" href="{{ route('host.calendar', ['month' => $next]) }}" aria-label="{{ __('booking::host.calendar.next') }}"><svg viewBox="0 0 20 20" class="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 4l6 6-6 6"/></svg></a>
    </x-slot:actions>

    <ul class="mb-3 flex flex-wrap gap-4 text-xs text-zinc-600">
        <li class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-accent-200"></span>{{ __('booking::messages.status.requested') }}</li>
        <li class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-olive-200"></span>{{ __('booking::messages.status.confirmed') }}</li>
        <li class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-zinc-300"></span>{{ __('booking::messages.status.completed') }}</li>
        <li class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-zinc-800"></span>{{ __('booking::host.calendar.day_off') }}</li>
    </ul>

    <div class="panel overflow-hidden rise rise-2">
        <table class="w-full table-fixed border-collapse text-start" role="grid">
            <caption class="sr-only">{{ $month->translatedFormat('F Y') }}</caption>
            <thead class="border-b border-zinc-200 bg-zinc-50">
                <tr>
                    @foreach (range(1, 7) as $d)
                        <th scope="col" class="p-2 text-center text-xs font-medium uppercase tracking-wide text-zinc-600"><span aria-hidden="true">{{ \Carbon\Carbon::create(2024, 1, $d)->translatedFormat('D') }}</span><span class="sr-only">{{ \Carbon\Carbon::create(2024, 1, $d)->translatedFormat('l') }}</span></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach (range(0, $rows - 1) as $week)
                    <tr class="divide-x divide-zinc-100 border-b border-zinc-100 last:border-0 rtl:divide-x-reverse">
                        @foreach (range(0, 6) as $col)
                            @php($n = $week * 7 + $col - $offset + 1)
                            @if ($n < 1 || $n > $days)
                                <td class="h-24 bg-zinc-50/60 align-top md:h-28"></td>
                            @else
                                @php($date = $first->setDay($n)->toDateString())
                                @php($items = $bookings->get($date, collect()))
                                @php($isOff = $off->contains($date))
                                <td class="h-24 p-1 align-top md:h-28 {{ $isOff ? 'bg-zinc-100' : '' }}">
                                    <p class="mb-1 flex items-center justify-between px-1">
                                        <span class="text-xs font-medium tabular-nums {{ $date === $today ? 'flex h-6 w-6 items-center justify-center rounded-full bg-accent-600 text-white' : 'text-zinc-700' }}" @if ($date === $today) aria-current="date" @endif>{{ $n }}</span>
                                        @if ($isOff)<span class="rounded bg-zinc-800 px-1 text-[10px] font-medium text-white">{{ __('booking::host.calendar.off') }}</span>@endif
                                    </p>
                                    <ul class="space-y-0.5">
                                        @foreach ($items->take(2) as $b)
                                            <li><a href="{{ route('bookings.show', $b) }}" class="block truncate rounded px-1 py-0.5 text-[11px] font-medium no-underline ring-1 ring-inset {{ $chip[$b->status->value] }}" title="{{ $b->tourist->name }} · {{ $b->experience->title }}">{{ substr($b->start_time, 0, 5) }} {{ $b->tourist->name }}</a></li>
                                        @endforeach
                                        @if ($items->count() > 2)<li class="px-1 text-[11px] text-zinc-600">+{{ $items->count() - 2 }}</li>@endif
                                    </ul>
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-sm text-zinc-600">{{ __('booking::host.calendar.hint') }} <a class="font-medium" href="{{ route('host.availability.index') }}">{{ __('booking::host.calendar.edit_hours') }}</a></p>
</x-dash>
