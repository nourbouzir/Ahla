<x-layouts.app :title="__('experience::messages.availability')">
    <h1 class="text-2xl font-bold">{{ __('experience::messages.availability') }}</h1>

    @if (! $profile)
        <p class="mt-4">{{ __('verification::messages.profile_first') }}</p>
    @else
        <section class="mt-4">
            <h2 class="text-lg font-semibold">{{ __('experience::messages.weekly') }}</h2>
            <ul class="mt-2 space-y-2">
                @forelse ($slots as $slot)
                    <li class="card flex items-center justify-between">
                        <span>{{ __('experience::messages.days.'.$slot->day_of_week) }} {{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}</span>
                        <form method="POST" action="{{ route('host.availability.slots.destroy', $slot) }}">@csrf @method('DELETE')
                            <button class="btn btn-secondary" type="submit">{{ __('experience::messages.remove') }}</button></form>
                    </li>
                @empty
                    <li>{{ __('experience::messages.no_slots') }}</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('host.availability.slots.store') }}" class="card mt-3">@csrf
                <div>
                    <label class="label" for="day_of_week">{{ __('experience::messages.day') }}</label>
                    <select class="field" id="day_of_week" name="day_of_week">
                        @foreach (range(0, 6) as $d)<option value="{{ $d }}" @selected((int) old('day_of_week', 1) === $d)>{{ __("experience::messages.days.$d") }}</option>@endforeach
                    </select>
                </div>
                <x-input name="starts_at" type="time" :label="__('experience::messages.from')" value="09:00" required />
                <x-input name="ends_at" type="time" :label="__('experience::messages.to')" value="17:00" required />
                <button class="btn btn-primary mt-4" type="submit">{{ __('experience::messages.add_slot') }}</button>
            </form>
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-semibold">{{ __('experience::messages.blocked') }}</h2>
            <ul class="mt-2 space-y-2">
                @forelse ($blocked as $b)
                    <li class="card flex items-center justify-between">
                        <span>{{ $b->date->format('Y-m-d') }} {{ $b->reason }}</span>
                        <form method="POST" action="{{ route('host.availability.blocked.destroy', $b) }}">@csrf @method('DELETE')
                            <button class="btn btn-secondary" type="submit">{{ __('experience::messages.remove') }}</button></form>
                    </li>
                @empty
                    <li>{{ __('experience::messages.no_blocked') }}</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('host.availability.blocked.store') }}" class="card mt-3">@csrf
                <x-input name="date" type="date" :label="__('experience::messages.date')" required />
                <x-input name="reason" :label="__('experience::messages.reason')" />
                <button class="btn btn-primary mt-4" type="submit">{{ __('experience::messages.block_date') }}</button>
            </form>
        </section>
    @endif
</x-layouts.app>
