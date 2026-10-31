@props(['text' => true, 'size' => 'h-9 w-9'])
{{--
    The Ahla mark: the arched blue door of Sidi Bou Said, studded with brass, under a terracotta
    eight-point star that doubles as the sun. Pure SVG, no external file.
--}}
<span {{ $attributes->class('inline-flex items-center gap-2') }}>
    <svg class="{{ $size }}" viewBox="0 0 48 48" @if ($text) aria-hidden="true" @else role="img" aria-label="{{ __('common.app_name') }}" @endif xmlns="http://www.w3.org/2000/svg">
        <path d="M24 2.2l2.1 4.4 4.8-.9-1.6 4.6 4.3 2.4-4.6 1.8.3 4.8-3.7-3.1-3.7 3.1.3-4.8-4.6-1.8 4.3-2.4-1.6-4.6 4.8.9z" fill="#cf6a45"/>
        <path d="M9 45V27c0-8.3 6.7-15 15-15s15 6.7 15 15v18z" fill="#174a80"/>
        <path d="M13 45V27.5C13 20.6 17.9 16 24 16s11 4.6 11 11.5V45z" fill="#1f5a99"/>
        <path d="M24 16v29" stroke="#0d2c4f" stroke-width="1.6"/>
        <g fill="#e0b24a">
            <circle cx="17.5" cy="26" r="1.5"/><circle cx="17.5" cy="32" r="1.5"/><circle cx="17.5" cy="38" r="1.5"/>
            <circle cx="30.5" cy="26" r="1.5"/><circle cx="30.5" cy="32" r="1.5"/><circle cx="30.5" cy="38" r="1.5"/>
            <circle cx="21" cy="31" r="1.1"/><circle cx="27" cy="31" r="1.1"/>
        </g>
        <path d="M6 45.5h36" stroke="#4c5e22" stroke-width="2.2" stroke-linecap="round"/>
    </svg>
    @if ($text)
        <span class="font-display text-3xl font-bold leading-none text-door-800">{{ __('common.app_name') }}</span>
    @endif
</span>
