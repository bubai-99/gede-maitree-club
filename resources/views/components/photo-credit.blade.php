@props(['key'])
@use('App\Support\Photo')
@if (Photo::exists($key))
    <span {{ $attributes }}><a href="{{ Photo::page($key) }}" target="_blank" rel="noopener" class="hover:underline">{{ Photo::by($key) }} / Unsplash</a></span>
@endif
