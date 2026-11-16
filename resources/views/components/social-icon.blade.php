@props(['network'])
{{-- Brand marks (Lucide has no brand icons). Decorative: the link carries the name. --}}
<svg viewBox="0 0 24 24" aria-hidden="true" {{ $attributes->merge(['class' => 'w-[18px] h-[18px]']) }}>
    @switch($network)
        @case('facebook')
            <path fill="currentColor" d="M9.1 23.7v-8H6.6V12h2.5v-1.6c0-4.1 1.8-6 5.9-6 .4 0 1 0 1.5.1l1.1.2V8l-.6-.1h-.8c-.7 0-1.2.1-1.6.3-.3.2-.5.4-.7.6-.3.4-.4 1-.4 1.8V12h3.9l-.4 2.1-.3 1.6h-3.2V24c5.9-.8 10.5-5.8 10.5-12C24 5.4 18.6 0 12 0S0 5.4 0 12c0 5.6 3.9 10.4 9.1 11.7Z"/>
            @break
        @case('instagram')
            <rect x="2.75" y="2.75" width="18.5" height="18.5" rx="5.25" fill="none" stroke="currentColor" stroke-width="1.9"/><circle cx="12" cy="12" r="4.2" fill="none" stroke="currentColor" stroke-width="1.9"/><circle cx="17.4" cy="6.6" r="1.25" fill="currentColor"/>
            @break
        @case('youtube')
            <path fill="currentColor" fill-rule="evenodd" d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2C.5 9 .5 12 .5 12s0 3 .5 4.8a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1c.5-1.8.5-4.8.5-4.8s0-3-.5-4.8ZM9.7 15.4V8.6l5.9 3.4-5.9 3.4Z"/>
            @break
        @default
            <path fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" d="M12 2.6a9.4 9.4 0 0 0-8.1 14.2L2.6 21.4l4.7-1.2A9.4 9.4 0 1 0 12 2.6Z"/><path fill="currentColor" d="M8.9 7.6c.3-.3.8-.3 1 .1l.9 1.8c.1.3.1.6-.2.8l-.6.6c.6 1.2 1.5 2.1 2.7 2.7l.6-.6c.2-.2.5-.3.8-.2l1.8.9c.4.2.4.7.1 1-.7.8-1.7 1.1-2.6.8a8 8 0 0 1-5.3-5.3c-.3-.9 0-1.9.8-2.6Z"/>
    @endswitch
</svg>
