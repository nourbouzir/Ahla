@props(['user', 'name' => null, 'size' => 'h-10 w-10 text-sm', 'label' => null])
@php
    // Same person, same colour, everywhere. All four give white text 4.5:1 or better.
    $palette = ['bg-door-700', 'bg-clay-700', 'bg-olive-700', 'bg-door-900'];
    $color = $palette[$user->id % count($palette)];
    $url = $user->avatarUrl();
@endphp
{{-- Decorative by default: the person's name is always written next to it. Pass label="" text where it is not. --}}
@if ($url)
    <img src="{{ $url }}" alt="{{ $label ?? '' }}" width="64" height="64" loading="lazy" decoding="async"
        {{ $attributes->class(["$size shrink-0 rounded-full object-cover ring-2 ring-white"]) }}>
@else
    <span @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
        {{ $attributes->class(["$size $color inline-flex shrink-0 items-center justify-center rounded-full font-semibold text-white ring-2 ring-white"]) }}>{{ $user->initials($name) }}</span>
@endif
