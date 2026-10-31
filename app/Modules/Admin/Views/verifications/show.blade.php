<x-dash area="admin" :title="$request->hostProfile->display_name" :heading="false">
    @php($host = $request->hostProfile)
    <a class="underline" href="{{ route('admin.verifications.index') }}">{{ __('common.actions.back') }}</a>
    <h1 class="mt-2 text-2xl font-bold">{{ $host->display_name }}</h1>
    <p class="text-slate-600">{{ $host->user->name }} - {{ $host->user->email }} - {{ $host->city }}</p>
    <p class="mt-1">{{ __('admin::messages.request_status') }}: <span class="badge">{{ __('admin::messages.request.'.$request->status->value) }}</span>
        - {{ __('admin::messages.host_status') }}: <span class="badge">{{ __('auth::messages.status.'.$host->status->value) }}</span></p>
    <p class="mt-3">{{ $host->bio }}</p>

    <p class="mt-4"><a class="btn btn-secondary" href="{{ route('admin.verifications.evidence', $request) }}">{{ __('admin::messages.download_evidence') }}</a></p>

    @if ($request->status->value !== 'decided')
        <form method="POST" action="{{ route('admin.verifications.schedule', $request) }}" class="card mt-6">
            @csrf
            <h2 class="text-lg font-semibold">{{ __('admin::messages.schedule_call') }}</h2>
            <x-input name="video_call_at" type="datetime-local" :label="__('admin::messages.call_at')" :value="$request->video_call_at?->format('Y-m-d\TH:i')" required />
            <x-input name="video_call_link" type="url" :label="__('admin::messages.call_link')" :value="$request->video_call_link" placeholder="https://meet.jit.si/..." required />
            <button class="btn btn-primary mt-4" type="submit">{{ __('admin::messages.schedule') }}</button>
        </form>

        <form method="POST" action="{{ route('admin.verifications.decide', $request) }}" class="card mt-6">
            @csrf
            <h2 class="text-lg font-semibold">{{ __('admin::messages.decision') }}</h2>
            <div class="mt-3">
                <label class="label" for="reason">{{ __('admin::messages.reason') }}</label>
                <textarea class="field" id="reason" name="reason" rows="3">{{ old('reason') }}</textarea>
            </div>
            <div class="mt-4 flex gap-3">
                <button class="btn btn-primary" type="submit" name="decision" value="approve">{{ __('admin::messages.approve') }}</button>
                <button class="btn btn-danger" type="submit" name="decision" value="reject">{{ __('admin::messages.reject') }}</button>
            </div>
        </form>
    @else
        <p class="mt-4"><strong>{{ __('admin::messages.decision') }}:</strong> {{ $request->decision }} - {{ $request->decision_reason }}</p>
    @endif
</x-dash>
