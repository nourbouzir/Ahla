<li class="card">
    <div class="flex items-start justify-between gap-2">
        <h3 class="text-lg font-semibold"><a class="underline" href="{{ route('hosts.show', $r->host) }}">{{ $r->host->display_name }}</a></h3>
        <span class="badge">{{ __('auth::messages.status.verified') }}</span>
    </div>
    <p class="text-sm text-slate-600">{{ $r->host->city }}
        @if ($r->host->rating_avg !== null) - {{ number_format((float) $r->host->rating_avg, 1) }} / 5 @endif
        - {{ __('matching::messages.match', ['n' => $r->score]) }}</p>

    <p class="mt-2 text-sm font-medium">{{ __('matching::messages.why') }}</p>
    <ul class="mt-1 flex flex-wrap gap-2">
        @foreach ($r->reasons as $reason)<li class="badge">{{ $reason }}</li>@endforeach
    </ul>

    <ul class="mt-3 space-y-1 text-sm">
        @foreach ($r->experiences->take(2) as $e)
            <li>{{ $e->title }} - {{ $e->duration_hours }} h - {{ number_format((float) $e->price_per_person, 3) }} TND</li>
        @endforeach
    </ul>
    <a class="btn btn-secondary mt-3" href="{{ route('hosts.show', $r->host) }}">{{ __('matching::messages.view_profile') }}</a>
</li>
