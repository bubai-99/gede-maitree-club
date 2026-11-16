@props(['key', 'width' => 1200, 'eager' => false, 'sizes' => '100vw'])
@use('App\Support\Photo')
@if (Photo::exists($key))
    <img src="{{ Photo::url($key, $width) }}" srcset="{{ Photo::srcset($key) }}" sizes="{{ $sizes }}"
        alt="{{ Photo::alt($key) }}" decoding="async"
        @if ($eager) fetchpriority="high" @else loading="lazy" @endif
        {{ $attributes->merge(['class' => 'w-full h-full object-cover']) }} />
@endif
