{{-- Shared <head> for the member app and the committee console (installable, never indexed). --}}
<meta charset="utf-8">
{{-- Photos: a shimmer until each one has loaded (resources/js/images.js); shown anyway after 4 s if scripts fail --}}
<script>document.documentElement.classList.add('js');setTimeout(function(){document.documentElement.classList.add('img-fallback')},4000)</script>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@vite(['resources/css/app.css', 'resources/js/app.js'])
