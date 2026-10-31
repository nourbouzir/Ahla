<li>
    <article class="card-link group relative flex h-full flex-col">
        <div class="flex items-start gap-3">
            <x-avatar :user="$r->host->user" :name="$r->host->display_name" size="h-12 w-12 text-base" />
            <div class="min-w-0 flex-1">
                <h3 class="truncate text-base font-semibold">
                    <a class="text-zinc-900 no-underline after:absolute after:inset-0 after:content-['']" href="{{ route('hosts.show', $r->host) }}">{{ $r->host->display_name }}</a>
                </h3>
                <p class="text-sm text-zinc-600">
                    {{ __("common.cities.{$r->host->city}") }}
                    @if ($r->host->rating_avg !== null)
                        <span aria-hidden="true">·</span>
                        <span class="whitespace-nowrap"><svg viewBox="0 0 20 20" class="-mt-0.5 inline h-3.5 w-3.5 text-amber-500" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.4 5.9.8-4.3 4.1 1 5.9L10 14.9 4.8 17.7l1-5.9L1.5 7.7l5.9-.8z"/></svg> {{ number_format((float) $r->host->rating_avg, 1) }}</span>
                    @endif
                </p>
            </div>
            <span class="badge-verified shrink-0"><svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8.5l3.2 3L13 4.5"/></svg>{{ __('auth::messages.status.verified') }}</span>
        </div>

        <div class="mt-4">
            <p class="text-xs font-medium text-zinc-600">{{ __('matching::messages.why') }}</p>
            <ul class="mt-1.5 flex flex-wrap gap-1.5">
                @foreach ($r->reasons as $reason)<li class="badge-accent">{{ $reason }}</li>@endforeach
            </ul>
        </div>

        <div class="mt-4 flex flex-1 items-end justify-between gap-3 border-t border-zinc-100 pt-3 text-sm">
            @php($cheapest = $r->experiences->min('price_per_person'))
            <span class="text-zinc-600">
                @if ($cheapest !== null){{ __('matching::messages.from') }} <strong class="font-semibold text-zinc-900">{{ number_format((float) $cheapest, 0) }} TND</strong>@endif
            </span>
            <span class="font-medium text-zinc-900">{{ __('matching::messages.match', ['n' => $r->score]) }}</span>
        </div>
    </article>
</li>
