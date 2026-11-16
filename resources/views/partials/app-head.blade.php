{{-- Shared <head> for the member app and the committee console (installable, never indexed). --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@vite(['resources/css/app.css', 'resources/js/app.js'])
