<x-layouts.app :title="__('booking::messages.request_title')">
    @php($host = $experience->hostProfile)
    <form method="POST" action="{{ route('bookings.store', $experience) }}" class="card mx-auto max-w-xl">
        @csrf
        <h1 class="text-2xl font-bold">{{ __('booking::messages.request_title') }}</h1>
        <p class="mt-2 font-semibold">{{ $experience->title }}</p>
        <p class="text-slate-600">{{ $host->display_name }} - {{ $host->city }} - {{ $experience->duration_hours }} h -
            {{ number_format((float) $experience->price_per_person, 3) }} TND {{ __('booking::messages.per_person') }}</p>

        <x-input name="date" type="date" :label="__('booking::messages.date')" :min="today()->toDateString()" required />
        <x-input name="start_time" type="time" :label="__('booking::messages.start_time')" required />
        <x-input name="group_size" type="number" min="1" :max="$experience->max_group_size" :label="__('booking::messages.group_size', ['max' => $experience->max_group_size])" :value="1" required />

        @if ($host->availabilities->isNotEmpty())
            <p class="mt-4 text-sm text-slate-600">{{ __('booking::messages.host_hours') }}:
                {{ $host->availabilities->sortBy('day_of_week')->map(fn ($a) => __('experience::messages.days.'.$a->day_of_week).' '.substr($a->starts_at, 0, 5).'-'.substr($a->ends_at, 0, 5))->join(', ') }}</p>
        @endif
        @error('booking')<p class="error-text" role="alert">{{ $message }}</p>@enderror

        <p class="mt-4 rounded bg-slate-100 p-3 text-sm">{{ __('booking::messages.payment_note') }}</p>
        <button class="btn btn-primary mt-4" type="submit">{{ __('booking::messages.send_request') }}</button>
    </form>
</x-layouts.app>
