@props(['member'])
{{-- The ID card photo, or (until the family adds one) a drawn portrait with the person's initial. --}}
@php
    $url = $member->photoUrl();
    $initial = mb_substr((string) $member->tr('name'), 0, 1);
    $gid = 'idp'.$member->id;
@endphp
@if ($url)
    <img src="{{ $url }}" alt="{{ __('Photo of :name', ['name' => $member->tr('name')]) }}" {{ $attributes->merge(['class' => 'w-full h-full object-cover']) }} loading="lazy" decoding="async">
@else
    <svg viewBox="0 0 120 160" role="img" aria-label="{{ __('No photo yet') }}" {{ $attributes->merge(['class' => 'w-full h-full']) }} preserveAspectRatio="xMidYMid slice">
        <defs>
            <linearGradient id="{{ $gid }}-bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fbf3e2"/><stop offset="1" stop-color="#f1dfc0"/></linearGradient>
            <radialGradient id="{{ $gid }}-glow" cx="50%" cy="38%" r="60%"><stop offset="0" stop-color="#f2a31b" stop-opacity=".35"/><stop offset="1" stop-color="#f2a31b" stop-opacity="0"/></radialGradient>
            <linearGradient id="{{ $gid }}-body" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3b4049"/><stop offset="1" stop-color="#23262c"/></linearGradient>
        </defs>
        <rect width="120" height="160" fill="url(#{{ $gid }}-bg)"/>
        <rect width="120" height="160" fill="url(#{{ $gid }}-glow)"/>
        {{-- a faint alpana behind the head --}}
        <g transform="translate(60 58)" fill="none" stroke="#c9302c" stroke-opacity=".22" stroke-width=".9">
            <circle r="40"/><circle r="33" stroke-dasharray="2 3"/>
            @for ($i = 0; $i < 12; $i++)
                <path d="M0 -40 C 6 -34, 6 -27, 0 -22 C -6 -27, -6 -34, 0 -40 Z" transform="rotate({{ $i * 30 }})"/>
            @endfor
        </g>
        {{-- head and shoulders --}}
        <circle cx="60" cy="60" r="21" fill="url(#{{ $gid }}-body)"/>
        <path d="M14 160 C 16 118, 36 98, 60 98 C 84 98, 104 118, 106 160 Z" fill="url(#{{ $gid }}-body)"/>
        <path d="M50 99 L60 112 L70 99" fill="none" stroke="#fbf3e2" stroke-opacity=".35" stroke-width="2"/>
        {{-- the initial, on the chest --}}
        <circle cx="60" cy="137" r="13" fill="#c9302c"/>
        <text x="60" y="142.5" text-anchor="middle" font-family="'Tiro Bangla', serif" font-size="16" fill="#fff">{{ $initial }}</text>
    </svg>
@endif
