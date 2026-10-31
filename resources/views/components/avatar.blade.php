@props(['user', 'name' => null, 'size' => 'h-10 w-10 text-sm', 'label' => null])
@php
    // Same person, same tint, everywhere. Soft tints with dark text: all pairs pass 4.5:1.
    $palette = ['bg-accent-100 text-accent-900', 'bg-clay-100 text-clay-700', 'bg-olive-100 text-olive-700', 'bg-zinc-200 text-zinc-800'];
    $tint = $palette[$user->id % count($palette)];
    $url = $user->avatarUrl();
@endphp
{{-- Decorative by default: the person's name is always written next to it. Pass label="..." where it is not. --}}
@if ($url)
    <img src="{{ $url }}" alt="{{ $label ?? '' }}" width="64" height="64" loading="lazy" decoding="async"
        {{ $attributes->class(["$size shrink-0 rounded-full object-cover ring-1 ring-zinc-200"]) }}>
@else
    <span @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
        {{ $attributes->class(["$size $tint inline-flex shrink-0 items-center justify-center rounded-full font-semibold"]) }}>{{ $user->initials($name) }}</span>
@endif
