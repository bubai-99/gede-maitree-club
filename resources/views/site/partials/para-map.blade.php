{{-- Sketch map of the para (from the prototype). Not to scale; replaced by a real map once the club confirms the location. --}}
<svg viewBox="0 0 640 400" class="block w-full h-auto" role="img" aria-label="{{ __('Sketch map of Netaji Para showing the club room beside School Road, at the north corner of the para ground') }}" font-family="Anek Bangla Variable, sans-serif">
    <rect width="640" height="400" fill="#f2f1ef"/>
    <g fill="#e2e0dc">
        <rect x="20" y="20" width="80" height="200" rx="3"/><rect x="160" y="20" width="70" height="90" rx="3"/><rect x="160" y="130" width="70" height="90" rx="3"/>
        <rect x="460" y="20" width="160" height="90" rx="3"/><rect x="460" y="130" width="70" height="90" rx="3"/><rect x="560" y="280" width="60" height="100" rx="3"/>
        <rect x="200" y="280" width="200" height="100" rx="3"/><rect x="460" y="280" width="70" height="100" rx="3"/>
    </g>
    <ellipse cx="100" cy="330" rx="72" ry="42" fill="#e1e6f1" stroke="#b9c3d6" stroke-width="1.5"/>
    <text x="100" y="335" text-anchor="middle" font-size="13" fill="#4b5059">{{ __('Pond') }}</text>
    <rect x="250" y="40" width="160" height="180" rx="4" fill="#d9ead6" stroke="#2c6a3a" stroke-width="1.5" stroke-dasharray="5 4"/>
    <text x="330" y="140" text-anchor="middle" font-size="14" font-weight="600" fill="#2c6a3a">{{ __('Para ground') }}</text>
    <g fill="none" stroke-linecap="round">
        <path d="M0 250 H640" stroke="#c9c6c0" stroke-width="30"/><path d="M0 250 H640" stroke="#fff" stroke-width="26"/>
        <path d="M430 0 V400" stroke="#c9c6c0" stroke-width="24"/><path d="M430 0 V400" stroke="#fff" stroke-width="20"/>
        <path d="M130 250 V0" stroke="#c9c6c0" stroke-width="16"/><path d="M130 250 V0" stroke="#fff" stroke-width="12"/>
        <path d="M240 250 V0" stroke="#c9c6c0" stroke-width="16"/><path d="M240 250 V0" stroke="#fff" stroke-width="12"/>
        <path d="M545 250 V400" stroke="#c9c6c0" stroke-width="16"/><path d="M545 250 V400" stroke="#fff" stroke-width="12"/>
        <path d="M180 250 C 180 300, 190 360, 180 400" stroke="#c9c6c0" stroke-width="14"/><path d="M180 250 C 180 300, 190 360, 180 400" stroke="#fff" stroke-width="10"/>
        <path d="M410 70 H430" stroke="#2b2f36" stroke-width="2" stroke-dasharray="3 3"/>
    </g>
    <g font-size="12.5" fill="#4b5059">
        <text x="24" y="246">{{ __('Netaji Subhas Road') }}</text>
        <text x="436" y="246">{{ __('Crossing') }}</text>
        <text transform="translate(447 380) rotate(-90)">{{ __('School Road') }}</text>
        <text transform="translate(135 200) rotate(-90)" font-size="11.5">{{ __('Lane 1') }}</text>
        <text transform="translate(245 200) rotate(-90)" font-size="11.5">{{ __('Lane 2') }}</text>
        <text transform="translate(550 390) rotate(-90)" font-size="11.5">{{ __('Lane 3') }}</text>
        <text x="190" y="396" font-size="11.5">{{ __('Pukur Par') }}</text>
    </g>
    <g transform="translate(398 70)">
        <circle r="26" fill="#d21f1b" opacity=".14"/>
        <path d="M0 -30 C -12 -30 -20 -21 -20 -10 C -20 4 0 22 0 22 C 0 22 20 4 20 -10 C 20 -21 12 -30 0 -30 Z" transform="translate(0 -10)" fill="#d21f1b" stroke="#fff" stroke-width="2"/>
        <circle cy="-20" r="7" fill="#fff"/>
    </g>
    <g transform="translate(276 22)">
        <rect x="0" y="-14" width="104" height="22" rx="4" fill="#2b2f36"/>
        <text x="52" y="1.5" text-anchor="middle" font-size="12.5" font-weight="600" fill="#fff">{{ __('Maitree club room') }}</text>
    </g>
    <g transform="translate(600 46)" fill="#2b2f36">
        <circle r="16" fill="#fff" stroke="#2b2f36" stroke-width="1.5"/>
        <path d="M0 -11 L5 4 L0 1 L-5 4 Z"/>
        <text y="13" text-anchor="middle" font-size="9" font-weight="700">N</text>
    </g>
</svg>
