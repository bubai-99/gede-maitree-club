<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.app-head')
    <meta name="theme-color" content="#2b2f36">
    <title>{{ __('We couldn’t find that page') }} · {{ __('Gede Maitree Club') }}</title>
</head>
<body class="min-h-dvh !bg-ink text-white">
    <div class="absolute right-5 top-4 z-10"><x-lang-switch dark /></div>
    <main class="halftone min-h-dvh flex items-center">
        <div class="mx-auto max-w-[760px] px-5 py-20 text-center">
            <div class="inline-block"><x-seal :size="110" color="#fff" class="stamp-in" /></div>
            <p class="mt-6 font-display text-[clamp(5rem,18vw,10rem)] leading-none num">{{ digits(404) }}</p>
            <h1 class="poster text-[clamp(2rem,6vw,3.5rem)] mt-4">{{ __('This page went to the pandal') }}</h1>
            <p class="mt-5 text-[18px] text-white/85 max-w-[44ch] mx-auto">{{ __('The link may be old, or the page has moved. Everything else is right where it was.') }}</p>
            <div class="mt-10 flex flex-wrap justify-center gap-2.5">
                <a href="{{ route('home') }}" class="btn btn-poster btn-primary"><span>{{ __('Club home') }}</span></a>
                <a href="{{ route('events.index') }}" class="btn btn-poster btn-light"><span>{{ __('See what’s on') }}</span></a>
            </div>
            <nav class="mt-10" aria-label="{{ __('Popular pages') }}">
                <ul class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-[15px] text-white/80">
                    <li><a href="{{ route('notices.index') }}" class="hover:text-white underline">{{ __('Notices') }}</a></li>
                    <li><a href="{{ route('catering') }}" class="hover:text-white underline">{{ __('Book the serving team') }}</a></li>
                    <li><a href="{{ route('nursery') }}" class="hover:text-white underline">{{ __('Nursery') }}</a></li>
                    <li><a href="{{ route('chanda') }}" class="hover:text-white underline">{{ __('Chanda & accounts') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white underline">{{ __('Contact') }}</a></li>
                </ul>
            </nav>
            <p class="mt-8 text-[15px] text-white/75"><span>{{ __('Looking for your card or dues?') }}</span> <a href="{{ route('member.login') }}" class="font-semibold text-white underline">{{ __('Member sign-in') }}</a></p>
        </div>
    </main>
</body>
</html>
