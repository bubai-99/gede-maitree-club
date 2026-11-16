@props(['title', 'portal' => 'member', 'photo' => 'pujaIdol'])
@php
    $isAdmin = $portal === 'admin';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.app-head')
    <title>{{ $title }} · {{ __('Gede Maitree Club') }}</title>
    <link rel="manifest" href="{{ asset($isAdmin ? 'admin.webmanifest' : 'member.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset($isAdmin ? 'icons/admin-180.png' : 'icons/member-180.png') }}">
</head>
<body class="min-h-dvh bg-paper">
    <div class="min-h-dvh lg:grid lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
        <aside class="hidden lg:flex relative bg-ink text-white flex-col justify-between p-12 overflow-hidden isolate" aria-hidden="true">
            <div class="absolute inset-0 -z-10"><x-photo :key="$photo" :width="1200" /></div>
            <div class="absolute inset-0 -z-10 bg-ink/75"></div>
            <x-seal :size="88" color="#fff" />
            <div>
                <p class="font-display leading-[0.95] text-[clamp(3.25rem,4.4vw,4.25rem)] tracking-[-0.02em]">{{ __('Gede Maitree Club') }}</p>
                <p class="poster text-[1.6rem] leading-tight mt-5 text-marigold">{{ $isAdmin ? __('The committee room') : __('Members’ entrance') }}</p>
                <p class="mt-4 text-[16px] text-white/80 max-w-[38ch]">
                    {{ $isAdmin
                        ? __('Members, books, chanda, catering and the nursery. Every entry carries your name as its signature.')
                        : __('Your card, dues, passes and notices, all in one app. Sign in with a password or a one-time code.') }}
                </p>
            </div>
            <p class="text-[14px] uppercase tracking-wide text-white/75">{{ __('Netaji Para · since 2014') }}</p>
        </aside>

        <div class="flex flex-col">
            <header class="flex items-center justify-between gap-3 px-5 sm:px-10 h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ __('Gede Maitree Club website') }}">
                    <x-seal :size="36" :estd="false" aria-hidden="true" />
                    <span class="font-display text-xl text-sindoor lg:hidden">{{ __('Gede Maitree Club') }}</span>
                </a>
                <div class="flex items-center gap-3">
                    <x-lang-switch />
                    <a href="{{ route('home') }}" class="hidden sm:inline text-sm font-semibold text-ink-2 hover:text-ink">{{ __('Back to website') }}</a>
                </div>
            </header>
            <x-flash class="!max-w-[440px]" />
            <main id="main" class="flex-1 flex items-start sm:items-center justify-center px-5 sm:px-10 py-8">
                <div class="w-full max-w-[400px]">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
