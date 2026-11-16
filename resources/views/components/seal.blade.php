@props(['size' => 96, 'rotate' => -8, 'estd' => true, 'color' => 'var(--color-sindoor)'])
@php
    $id = 'seal-'.\Illuminate\Support\Str::random(8);
    $ring = $estd ? 'GEDE MAITREE CLUB  •  NETAJI PARA  •  ESTD 2014  •  ' : 'GEDE MAITREE CLUB  •  NETAJI PARA  •  ';
    $bengali = app()->getLocale() === 'bn';
@endphp
<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 200 200" role="img" aria-label="{{ __('Gede Maitree Club seal') }}" style="transform:rotate({{ (int) $rotate }}deg);color:{{ $color }}">
    <defs><path id="{{ $id }}" d="M100,100 m-74,0 a74,74 0 1,1 148,0 a74,74 0 1,1 -148,0"/></defs>
    <circle cx="100" cy="100" r="96" fill="none" stroke="currentColor" stroke-width="5"/>
    <circle cx="100" cy="100" r="88" fill="none" stroke="currentColor" stroke-width="1.6"/>
    <circle cx="100" cy="100" r="58" fill="none" stroke="currentColor" stroke-width="1.6"/>
    <text font-family="Anek Bangla Variable, sans-serif" font-weight="700" font-size="13.5" letter-spacing="1.2" fill="currentColor">
        <textPath href="#{{ $id }}" textLength="462" lengthAdjust="spacing">{{ $ring }}</textPath>
    </text>
    <text x="100" y="110" text-anchor="middle" font-family="DM Serif Display, Tiro Bangla, serif" font-size="{{ $bengali ? 40 : 29 }}" fill="currentColor">{{ __('Maitree') }}</text>
    <text x="100" y="134" text-anchor="middle" font-family="Anek Bangla Variable, sans-serif" font-weight="600" font-size="11" letter-spacing="3" fill="currentColor">{{ __('CLUB') }}</text>
</svg>
