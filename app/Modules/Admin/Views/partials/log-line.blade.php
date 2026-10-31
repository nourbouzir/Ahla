@php
    // Everyday actions have a sentence ("Logged in", "Sent a message"); status changes show old -> new.
    $key = "admin::dash.activity.{$log->entity}.{$log->new_status}";
    $sentence = \Illuminate\Support\Facades\Lang::has($key) ? __($key) : null;
@endphp
<span class="font-medium text-zinc-900">{{ $log->actor?->name ?? __('admin::dash.overview.system') }}</span>
@if ($sentence)
    <span class="text-zinc-700">{{ $sentence }}</span>
@else
    <span class="text-zinc-700">{{ __("admin::dash.entity.{$log->entity}") === "admin::dash.entity.{$log->entity}" ? $log->entity : __("admin::dash.entity.{$log->entity}") }} #{{ $log->entity_id }}:</span>
    <span class="text-zinc-600">{{ $log->old_status ?? '-' }} &rarr; {{ $log->new_status }}</span>
@endif
@if ($log->reason)<span class="text-zinc-600">· {{ $log->reason }}</span>@endif
