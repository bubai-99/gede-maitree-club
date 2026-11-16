@props(['title' => null])
@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $nav = \App\Support\Navigation::groups(\App\Support\Navigation::MEMBER, $user);
    $tabs = \App\Support\Navigation::items(\App\Support\Navigation::MEMBER_TABS, $user);
    $homeUrl = Route::has('member.home') ? route('member.home') : url('/');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.app-head')
    <title>{{ $title ? $title.' · ' : '' }}{{ __('Gede Maitree Club · Member App') }}</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Maitree Club">
    <link rel="manifest" href="{{ asset('member.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/member-180.png') }}">
</head>
<body class="min-h-dvh bg-paper-2" data-sw="{{ asset('sw.js') }}">
    <a href="#main" class="skip-link">{{ __('Skip to content') }}</a>

    <div class="lg:grid lg:grid-cols-[272px_minmax(0,1fr)] min-h-dvh">
        <aside class="hidden lg:flex flex-col sticky top-0 h-dvh border-r border-rule bg-paper" aria-label="{{ __('Member app') }}">
            <a href="{{ $homeUrl }}" class="flex items-center gap-3 px-5 h-[72px] shrink-0 border-b border-rule">
                <x-seal :size="40" :estd="false" aria-hidden="true" />
                <span class="min-w-0 leading-tight">
                    <span class="block font-display text-[1.15rem] text-sindoor truncate">{{ __('Gede Maitree Club') }}</span>
                    <span class="block text-[12.5px] text-ink-3">{{ __('Member app') }}</span>
                </span>
            </a>
            <nav class="flex-1 overflow-y-auto px-3 py-4 text-[15px]" aria-label="{{ __('Main') }}">
                @foreach ($nav as $group)
                    @if ($group['group'])
                        <p class="px-3 mt-5 mb-1.5 text-[11.5px] font-bold uppercase tracking-[0.12em] text-ink-3">{{ __($group['group']) }}</p>
                    @endif
                    <ul class="space-y-0.5">
                        @foreach ($group['items'] as $item)
                            <li><a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif class="flex items-center gap-3 h-10 px-3 rounded-md text-ink-2 hover:bg-paper-2 hover:text-ink aria-[current=page]:bg-sindoor-wash aria-[current=page]:text-sindoor-deep aria-[current=page]:font-semibold">
                                <x-icon :name="$item['icon']" class="w-[18px] h-[18px]" /><span class="truncate">{{ __($item['label']) }}</span></a></li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>
            @if ($user)
                <div class="border-t border-rule p-3 shrink-0 flex items-center gap-2">
                    <span class="w-10 h-10 rounded-full bg-ink text-white grid place-items-center font-display" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                    <span class="min-w-0 flex-1 leading-tight">
                        <span class="block font-semibold truncate">{{ $user->name }}</span>
                        @if ($user->family)
                            <span class="block text-[13px] text-ink-3 num">{{ $user->family->family_no }}</span>
                        @endif
                    </span>
                    @if (Route::has('member.logout'))
                        <form method="POST" action="{{ route('member.logout') }}">
                            @csrf
                            <button type="submit" class="w-9 h-9 grid place-items-center rounded-md hover:bg-paper-2" aria-label="{{ __('Sign out') }}" title="{{ __('Sign out') }}"><x-icon name="log-out" class="w-4 h-4" /></button>
                        </form>
                    @endif
                </div>
            @endif
        </aside>

        <div class="min-w-0 flex flex-col">
            <header class="sticky top-0 z-40 bg-paper/95 backdrop-blur-sm border-b border-rule">
                <div class="mx-auto max-w-[1120px] h-14 lg:h-[72px] px-4 sm:px-6 lg:px-10 flex items-center gap-2">
                    <a href="{{ $homeUrl }}" class="flex items-center gap-2.5 min-w-0 lg:hidden" aria-label="{{ __('Home') }}">
                        <x-seal :size="32" :estd="false" aria-hidden="true" />
                        <span class="font-display text-[1.1rem] text-sindoor truncate">{{ __('Gede Maitree Club') }}</span>
                    </a>
                    @if ($user)
                        <p class="hidden lg:block text-[15px] text-ink-2 min-w-0 truncate">{{ __('Namaskar') }}, <span class="font-semibold text-ink">{{ \Illuminate\Support\Str::before($user->name, ' ') }}</span> · {{ local_date(now(), 'l, j F') }}</p>
                    @endif
                    <div class="ml-auto flex items-center gap-1">
                        <x-lang-switch />
                        @if ($user && Route::has('member.logout'))
                            <form method="POST" action="{{ route('member.logout') }}" class="lg:hidden">
                                @csrf
                                <button type="submit" class="w-11 h-11 rounded-full grid place-items-center hover:bg-paper-2" aria-label="{{ __('Sign out') }}"><x-icon name="log-out" class="w-5 h-5" /></button>
                            </form>
                        @endif
                    </div>
                </div>
                <div x-data="{ offline: ! navigator.onLine }" @online.window="offline = false" @offline.window="offline = true" x-show="offline" x-cloak class="bg-ink text-paper text-sm" role="status">
                    <div class="mx-auto max-w-[1120px] px-4 sm:px-6 lg:px-10 py-2 flex items-center gap-2"><x-icon name="wifi-off" class="w-4 h-4" /><span>{{ __('You’re offline. Pages you have opened before still show; payments and forms need the internet.') }}</span></div>
                </div>
            </header>

            <x-flash />

            <main id="main" tabindex="-1" class="flex-1 w-full mx-auto max-w-[1120px] px-4 sm:px-6 lg:px-10 pt-5 lg:pt-8 pb-28 lg:pb-14 focus:outline-none">
                {{ $slot }}
            </main>
        </div>
    </div>

    @if ($tabs)
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 border-t border-rule bg-paper/95 backdrop-blur-sm pb-[env(safe-area-inset-bottom)]" aria-label="{{ __('App') }}">
            <div class="grid max-w-xl mx-auto" style="grid-template-columns: repeat({{ count($tabs) }}, minmax(0, 1fr))">
                @foreach ($tabs as $tab)
                    <a href="{{ $tab['url'] }}" @if ($tab['active']) aria-current="page" @endif class="relative flex flex-col items-center gap-0.5 pt-2 pb-2.5 text-[11.5px] font-medium text-ink-3 aria-[current=page]:text-sindoor">
                        <x-icon :name="$tab['icon']" class="w-6 h-6" /><span>{{ __($tab['label']) }}</span></a>
                @endforeach
            </div>
        </nav>
    @endif
</body>
</html>
