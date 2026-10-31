<x-dash area="host" :title="__('experience::messages.title')" :heading="false">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ __('experience::messages.title') }}</h1>
        <div class="flex gap-2">
            <a class="btn btn-secondary" href="{{ route('host.availability.index') }}">{{ __('experience::messages.availability') }}</a>
            <a class="btn btn-primary" href="{{ route('host.experiences.create') }}">{{ __('experience::messages.new') }}</a>
        </div>
    </div>

    @if (! $profile)
        <p class="mt-4">{{ __('verification::messages.profile_first') }} <a class="underline" href="{{ route('profile.edit') }}">{{ __('common.nav.profile') }}</a></p>
    @else
        @if ($profile->status->value !== 'verified')
            <p class="mt-4 rounded border border-amber-300 bg-amber-50 p-3">{{ __('experience::messages.not_verified') }}
                <a class="underline" href="{{ route('host.verification.show') }}">{{ __('verification::messages.title') }}</a></p>
        @endif
        <ul class="mt-4 space-y-3">
            @forelse ($experiences as $e)
                <li class="card flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $e->title }}</h2>
                        <p class="text-sm text-slate-600">{{ $e->duration_hours }} h - {{ number_format((float) $e->price_per_person, 3) }} TND - max {{ $e->max_group_size }}</p>
                        <span class="badge">{{ $e->published ? __('experience::messages.published') : __('experience::messages.draft') }}</span>
                    </div>
                    <div class="flex gap-2">
                        <a class="btn btn-secondary" href="{{ route('host.experiences.edit', $e) }}">{{ __('experience::messages.edit') }}</a>
                        <form method="POST" action="{{ route('host.experiences.toggle', $e) }}">@csrf
                            <button class="btn btn-secondary" type="submit">{{ $e->published ? __('experience::messages.unpublish') : __('experience::messages.publish') }}</button>
                        </form>
                    </div>
                </li>
            @empty
                <li>{{ __('experience::messages.empty') }}</li>
            @endforelse
        </ul>
    @endif
</x-dash>
