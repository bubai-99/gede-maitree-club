@props(['key', 'plain' => false])
@use('App\Support\Photo')
{{-- plain: text only, for credits inside a link (a link inside a link breaks the page layout) --}}
@if (Photo::exists($key))
    @if ($plain)
        <span {{ $attributes }}>{{ Photo::by($key) }}</span>
    @else
        <span {{ $attributes }}><a href="{{ Photo::page($key) }}" target="_blank" rel="noopener" class="hover:underline">{{ Photo::by($key) }}</a></span>
    @endif
@endif
