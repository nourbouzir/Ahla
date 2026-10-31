<x-layouts.app :title="$booking->experience->title">
    @php($other = $viewerIsHost ? $booking->tourist : $booking->host)
    @php($canSeeContact = $booking->status->value === 'confirmed' || (auth()->user()->role->value === 'admin'))
    <article class="card mx-auto max-w-xl">
        <h1 class="text-2xl font-bold">{{ $booking->experience->title }}</h1>
        <p class="mt-1"><span class="badge">{{ __('booking::messages.status.'.$booking->status->value) }}</span></p>

        <dl class="mt-4 space-y-2">
            <div><dt class="label">{{ $viewerIsHost ? __('booking::messages.tourist') : __('booking::messages.host') }}</dt>
                <dd>{{ ($viewerIsHost ? null : $booking->host->hostProfile?->display_name) ?? $other->name }}</dd></div>
            <div><dt class="label">{{ __('booking::messages.when') }}</dt>
                <dd>{{ $booking->date->format('Y-m-d') }} {{ substr($booking->start_time, 0, 5) }}-{{ substr($booking->end_time, 0, 5) }}</dd></div>
            <div><dt class="label">{{ __('booking::messages.group') }}</dt><dd>{{ $booking->group_size }}</dd></div>
            @if ($booking->location)<div><dt class="label">{{ __('booking::messages.meeting') }}</dt><dd>{{ $booking->location }}</dd></div>@endif
            <div><dt class="label">{{ __('booking::messages.price') }}</dt><dd>{{ number_format((float) $booking->displayed_price, 3) }} TND</dd></div>
            @if ($booking->payment)
                <div><dt class="label">{{ __('booking::messages.payment') }}</dt>
                    <dd>{{ __('booking::messages.payment_status.'.$booking->payment->status->value) }}
                        @if ((float) $booking->payment->refunded_amount > 0) ({{ number_format((float) $booking->payment->refunded_amount, 3) }} TND {{ __('booking::messages.refunded') }})@endif</dd></div>
            @endif
            @if ($booking->status->value === 'requested')
                <div><dt class="label">{{ __('booking::messages.answer_before') }}</dt><dd>{{ $booking->expires_at->format('Y-m-d H:i') }}</dd></div>
            @endif
            @if ($canSeeContact)
                <div><dt class="label">{{ __('auth::messages.email') }}</dt><dd>{{ $other->email }}</dd></div>
                <div><dt class="label">{{ __('auth::messages.phone') }}</dt><dd>{{ $other->phone ?: '-' }}</dd></div>
            @else
                <p class="text-sm text-slate-600">{{ __('booking::messages.contact_hidden') }}</p>
            @endif
            @if ($booking->status->value === 'cancelled')
                <div><dt class="label">{{ __('booking::messages.cancelled_by') }}</dt>
                    <dd>{{ $booking->canceller?->name }} {{ $booking->cancellation_reason ? '- '.$booking->cancellation_reason : '' }}</dd></div>
            @endif
        </dl>

        @error('booking')<p class="error-text" role="alert">{{ $message }}</p>@enderror

        @if ($booking->status->value === 'confirmed' && ! auth()->user()->can('admin.audit'))
            <form method="POST" action="{{ route('bookings.cancel', $booking) }}" class="mt-6">
                @csrf
                <label class="label" for="reason">{{ __('booking::messages.cancel_reason') }}</label>
                <input class="field" id="reason" name="reason" maxlength="500">
                <p class="mt-2 text-sm text-slate-600">{{ $viewerIsHost ? __('booking::messages.cancel_host_note') : __('booking::messages.cancel_tourist_note') }}</p>
                <button class="btn btn-danger mt-3" type="submit">{{ __('booking::messages.cancel') }}</button>
            </form>
        @endif
    </article>

    @if ($alternatives->isNotEmpty())
        <section class="mx-auto mt-6 max-w-xl" aria-labelledby="alt-title">
            <h2 id="alt-title" class="text-xl font-semibold">{{ __('booking::messages.alternatives') }}</h2>
            <ul class="mt-3 space-y-3">
                @foreach ($alternatives as $alt)
                    <li class="card">
                        <p class="font-semibold">{{ $alt->display_name }} <span class="badge">{{ __('auth::messages.status.verified') }}</span></p>
                        <p class="text-sm text-slate-600">{{ $alt->city }}</p>
                        @if (Route::has('hosts.show'))<a class="underline" href="{{ route('hosts.show', $alt) }}">{{ __('booking::messages.view_host') }}</a>@endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
