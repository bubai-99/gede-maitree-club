@props(['title' => null])
@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $nav = \App\Support\Navigation::groups(\App\Support\Navigation::ADMIN, $user);
    $tabs = \App\Support\Navigation::items(\App\Support\Navigation::ADMIN_TABS, $user);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.app-head')
    <title>{{ $title ? $title.' · ' : '' }}{{ __('Gede Maitree Club · Committee Console') }}</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Maitree Console">
    <link rel="manifest" href="{{ asset('admin.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/admin-180.png') }}">
</head>
<body class="app-shell min-h-dvh bg-paper" data-sw="{{ asset('sw.js') }}" x-data="{ side: false }" @keydown.escape.window="side = false">
    <a href="#main" class="skip-link">{{ __('Skip to content') }}</a>
    <div class="lg:grid lg:grid-cols-[248px_1fr] min-h-dvh">
        <aside data-no-print id="sidebar" class="fixed inset-y-0 left-0 z-40 w-[260px] -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-dvh lg:w-auto bg-paper-2 border-r border-rule flex flex-col transition-transform duration-300 ease-[cubic-bezier(.16,1,.3,1)]"
            :class="{ '-translate-x-full': ! side }" aria-label="{{ __('Console') }}">
            <div class="px-5 h-16 flex items-center gap-2.5 border-b border-rule shrink-0">
                <x-seal :size="38" :rotate="-6" aria-hidden="true" />
                <div class="leading-none">
                    <p class="font-display text-lg text-sindoor">{{ __('Gede Maitree Club') }}</p>
                    <p class="text-[11.5px] font-semibold tracking-[0.14em] text-ink-2 mt-0.5">{{ __('COMMITTEE CONSOLE') }}</p>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 py-4 text-[15px]" aria-label="{{ __('Console') }}">
                @foreach ($nav as $group)
                    @if ($group['group'])
                        <p class="px-3 mt-5 mb-1.5 text-[12px] font-semibold text-ink-3 tracking-wide">{{ __($group['group']) }}</p>
                    @endif
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif class="flex items-center gap-3 px-3 h-10 rounded-lg text-ink-2 hover:bg-paper-3/50 aria-[current=page]:bg-card aria-[current=page]:text-ink aria-[current=page]:font-semibold aria-[current=page]:shadow-[0_1px_2px_rgba(29,33,64,.08)]">
                            <x-icon :name="$item['icon']" class="w-[18px] h-[18px]" />{{ __($item['label']) }}
                            @if ($item['count'] > 0)
                                <span class="ml-auto chip h-5 px-1.5 text-[11.5px] bg-sindoor text-white num">{{ digits($item['count']) }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>
            @if ($user)
                <div class="border-t border-rule px-4 py-3 flex items-center gap-3">
                    <span class="flex items-center gap-3 min-w-0 -m-1 p-1">
                        <span class="w-9 h-9 rounded-full bg-ink text-paper grid place-items-center font-display shrink-0" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                        <span class="leading-tight min-w-0">
                            <span class="block font-semibold text-sm truncate">{{ $user->name }}</span>
                            <span class="block text-[12.5px] text-ink-2 truncate">{{ $user->roleLabels() }}</span>
                        </span>
                    </span>
                    @if (Route::has('admin.logout'))
                        <form method="POST" action="{{ route('admin.logout') }}" class="ml-auto">
                            @csrf
                            <button type="submit" class="w-8 h-8 grid place-items-center rounded-md hover:bg-paper-3/60" aria-label="{{ __('Sign out') }}" title="{{ __('Sign out') }}"><x-icon name="log-out" class="w-4 h-4" /></button>
                        </form>
                    @endif
                </div>
            @endif
        </aside>
        <div class="fixed inset-0 bg-ink/40 z-30 lg:hidden" x-show="side" x-cloak @click="side = false"></div>

        <div class="min-w-0 flex flex-col">
            <header data-no-print class="h-16 border-b border-rule flex items-center gap-3 px-4 sm:px-6 bg-paper sticky top-0 z-20">
                <button type="button" class="lg:hidden w-10 h-10 -ml-2 grid place-items-center rounded-md hover:bg-paper-2" @click="side = true" aria-controls="sidebar" :aria-expanded="side.toString()" aria-label="{{ __('Open menu') }}"><x-icon name="menu" class="w-5 h-5" /></button>
                @can('fees.manage')
                    {{-- Finds receipts by receipt number, family number or the head of family's name --}}
                    <form method="GET" action="{{ route('admin.receipts.index') }}" role="search" class="relative flex-1 max-w-md" x-data @keydown.slash.window="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.q.focus() }">
                        <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-3" />
                        <label for="gsearch" class="sr-only">{{ __('Find a family or receipt') }}</label>
                        <input id="gsearch" x-ref="q" name="q" type="search" value="{{ request()->routeIs('admin.receipts.index') ? request('q') : '' }}" maxlength="60" class="field h-10 pl-9 bg-card text-ellipsis" autocomplete="off" placeholder="{{ __('Find a family or receipt') }}" />
                        <kbd class="hidden sm:block absolute right-2.5 top-1/2 -translate-y-1/2 text-[12px] text-ink-3 border border-rule rounded px-1.5">/</kbd>
                    </form>
                @else
                    <p class="flex-1 min-w-0 font-semibold truncate">{{ $title }}</p>
                @endcan
                <div class="ml-auto shrink-0 flex items-center gap-1">
                    <x-lang-switch class="hidden sm:flex mr-1" />
                    <a href="{{ route('home') }}" class="w-10 h-10 grid place-items-center rounded-md hover:bg-paper-2" target="_blank" rel="noopener" aria-label="{{ __('View website') }}" title="{{ __('View website') }}"><x-icon name="external-link" class="w-5 h-5" /></a>
                    @can('activity.view')
                        <a href="{{ route('admin.activity') }}" class="relative w-10 h-10 grid place-items-center rounded-md hover:bg-paper-2" aria-label="{{ __('Activity') }}" title="{{ __('Activity') }}"><x-icon name="bell" class="w-5 h-5" /></a>
                    @endcan
                </div>
                @can('fees.manage')
                    <a href="{{ route('admin.collect') }}" class="btn btn-primary btn-sm shrink-0"><x-icon name="plus" /><span class="hidden sm:inline">{{ __('Collect payment') }}</span></a>
                @endcan
            </header>

            <x-flash />

            <main id="main" tabindex="-1" class="flex-1 min-w-0 px-4 sm:px-6 pt-6 pb-24 lg:py-8 focus:outline-none">
                {{ $slot }}
            </main>
        </div>
    </div>

    @if ($tabs)
        <nav data-no-print class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-paper border-t border-rule grid h-16 pb-[env(safe-area-inset-bottom)] text-[11.5px] font-semibold text-ink-2" style="grid-template-columns: repeat({{ count($tabs) + 1 }}, minmax(0, 1fr))" aria-label="{{ __('Quick') }}">
            @foreach ($tabs as $tab)
                @if ($tab['route'] === 'admin.collect')
                    <a href="{{ $tab['url'] }}" @if ($tab['active']) aria-current="page" @endif class="flex flex-col items-center justify-center gap-1 aria-[current=page]:text-sindoor"><span class="w-11 h-11 -mt-5 rounded-full bg-sindoor text-white grid place-items-center shadow-[0_4px_10px_-2px_rgba(210,31,27,.5)]"><x-icon name="plus" class="w-6 h-6" /></span><span>{{ __($tab['label']) }}</span></a>
                @else
                    <a href="{{ $tab['url'] }}" @if ($tab['active']) aria-current="page" @endif class="flex flex-col items-center justify-center gap-1 aria-[current=page]:text-sindoor"><x-icon :name="$tab['icon']" class="w-5 h-5" /><span>{{ __($tab['label']) }}</span></a>
                @endif
            @endforeach
            <button type="button" @click="side = true" class="flex flex-col items-center justify-center gap-1"><x-icon name="menu" class="w-5 h-5" /><span>{{ __('More') }}</span></button>
        </nav>
    @endif
</body>
</html>
