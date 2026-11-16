@props(['name'])
{{-- Lucide icon, drawn by resources/js/icons.js. Decorative: hidden from screen readers. --}}
<i data-lucide="{{ $name }}" aria-hidden="true" {{ $attributes->merge(['class' => 'inline-block shrink-0']) }}></i>
