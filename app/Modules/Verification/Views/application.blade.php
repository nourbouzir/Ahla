<x-layouts.app :title="__('verification::messages.title')">
    <div class="card mx-auto max-w-xl">
        <h1 class="text-2xl font-bold">{{ __('verification::messages.title') }}</h1>

        @if (! $profile)
            <p class="mt-3">{{ __('verification::messages.profile_first') }}</p>
            <a class="btn btn-primary mt-4" href="{{ route('profile.edit') }}">{{ __('common.nav.profile') }}</a>
        @else
            <p class="mt-3">{{ __('verification::messages.status') }}:
                <span class="badge">{{ __('auth::messages.status.'.$profile->status->value) }}</span></p>

            @if ($open)
                <p class="mt-3">{{ __('verification::messages.under_review') }}</p>
                @if ($open->video_call_at)
                    <p class="mt-2"><strong>{{ __('verification::messages.call') }}:</strong>
                        <time datetime="{{ $open->video_call_at->toIso8601String() }}">{{ $open->video_call_at->format('Y-m-d H:i') }}</time>
                        - <a class="underline" href="{{ $open->video_call_link }}" rel="noopener noreferrer">{{ __('verification::messages.join_call') }}</a></p>
                @endif
            @else
                @if ($last?->decision === 'rejected')
                    <p class="mt-3 rounded border border-red-300 bg-red-50 p-3">{{ __('verification::messages.rejected') }} {{ $last->decision_reason }}</p>
                @endif
                @if (in_array($profile->status->value, ['pending', 'rejected']))
                    <form method="POST" action="{{ route('host.verification.store') }}" enctype="multipart/form-data" class="mt-4">
                        @csrf
                        <label class="label" for="evidence">{{ __('verification::messages.evidence') }} *</label>
                        <input class="field" type="file" id="evidence" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required aria-describedby="evidence-hint">
                        <p id="evidence-hint" class="mt-1 text-sm text-slate-600">{{ __('verification::messages.evidence_hint') }}</p>
                        @error('evidence')<p class="error-text">{{ $message }}</p>@enderror
                        <button class="btn btn-primary mt-4" type="submit">{{ __('verification::messages.submit') }}</button>
                    </form>
                @endif
            @endif
        @endif
    </div>
</x-layouts.app>
