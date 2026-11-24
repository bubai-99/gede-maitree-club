@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'ogType' => 'website',
    'noindex' => false,
    'seo' => null,
])
@php
    /** @var \App\Support\SiteSettings $site */
    $club = $site->club();
    $bn = app()->getLocale() === 'bn';
    $clubName = $bn ? $club['name_bn'] : $club['name'];
    $pageTitle = $seo ? $site->seo($seo, 'title') : ($title ? $title.' · '.$clubName : $site->seo('home', 'title'));
    $pageDescription = $description ?? $site->seo($seo ?? 'home', 'description');
    $canonicalUrl = $canonical ?? url()->current();
    $ogImage = $image ?? asset('og-image.png');
    $mainMenu = $site->mainMenu();
    // Signed in: a link to the member app (or the console for committee who aren't members); otherwise Member login
    $account = auth()->user();
    $accountLink = match (true) {
        $account?->isMember() === true && Route::has('member.home') => ['url' => route('member.home'), 'label' => __('My account'), 'icon' => 'user'],
        $account?->isCommitteeMember() === true && Route::has('admin.dashboard') => ['url' => route('admin.dashboard'), 'label' => __('Committee console'), 'icon' => 'layout-dashboard'],
        Route::has('member.login') => ['url' => route('member.login'), 'label' => __('Member login'), 'icon' => 'log-in'],
        default => null,
    };
    // Phone menu: an icon for each page
    $menuIcons = ['events.index' => 'calendar-days', 'notices.index' => 'megaphone', 'news.index' => 'newspaper', 'catering' => 'utensils', 'nursery' => 'sprout', 'gallery' => 'camera', 'committee' => 'users', 'contact' => 'phone', 'panjika' => 'calendar', 'chanda' => 'hand-coins'];
    $moreMenu = array_values(array_filter([
        Route::has('panjika') ? ['route' => 'panjika', 'url' => route('panjika'), 'label' => __('Panjika'), 'active' => request()->routeIs('panjika')] : null,
        Route::has('chanda') ? ['route' => 'chanda', 'url' => route('chanda'), 'label' => __('Chanda & accounts'), 'active' => request()->routeIs('chanda')] : null,
    ]));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    {{-- Photos: a shimmer until each one has loaded (resources/js/images.js); shown anyway after 4 s if scripts fail --}}
    <script>document.documentElement.classList.add('js');setTimeout(function(){document.documentElement.classList.add('img-fallback')},4000)</script>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en" href="{{ $canonicalUrl }}?lang=en">
    <link rel="alternate" hreflang="bn" href="{{ $canonicalUrl }}?lang=bn">
    <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    {{-- The member app can be installed from any website page (the browser's install icon, or "Get the app") --}}
    <link rel="manifest" href="{{ asset('member.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/member-180.png?v=2') }}">
    <meta name="apple-mobile-web-app-title" content="Maitree Club">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $club['name'] }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="{{ $bn ? 'bn_IN' : 'en_IN' }}">
    <meta property="og:locale:alternate" content="{{ $bn ? 'en_IN' : 'bn_IN' }}">
    <meta name="twitter:card" content="summary_large_image">
    <script type="application/ld+json">@json($site->organisationJsonLd())</script>
    {{ $head ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh site">
    <a href="#main" class="skip-link">{{ __('Skip to content') }}</a>

    <header class="site-header sticky top-0 z-40 bg-paper border-b border-rule" x-data="siteHeader"
        x-init="scrolled = window.scrollY > 4" @scroll.window.passive="scrolled = window.scrollY > 4" :class="{ 'is-scrolled': scrolled }"
        @keydown.escape.window="close()">
        <div class="mx-auto max-w-[1160px] px-3 min-[380px]:px-4 sm:px-5 h-16 flex items-center gap-1.5 min-[380px]:gap-2 sm:gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 min-[380px]:gap-2.5 min-w-0 shrink-0" aria-label="{{ __('Gede Maitree Club home') }}">
                <x-seal :size="40" :estd="false" class="shrink-0 max-[379px]:size-[34px]" aria-hidden="true" />
                <span class="leading-none min-w-0">
                    <span class="block font-display text-[16px] min-[380px]:text-[18px] sm:text-[22px] text-sindoor whitespace-nowrap">{{ $clubName }}</span>
                    <span class="block text-[10px] min-[380px]:text-[11px] sm:text-[11.5px] font-semibold tracking-[0.1em] min-[380px]:tracking-[0.14em] text-ink-2 mt-0.5 uppercase truncate">{{ __('Gede Uttarpara · Est. 2014') }}</span>
                </span>
            </a>
            <nav class="hidden lg:flex items-center gap-0.5 ml-auto text-[15px] font-medium" aria-label="{{ __('Main') }}">
                @foreach ($mainMenu as $item)
                    <a href="{{ $item['url'] }}" class="nav-link {{ $item['class'] ?? '' }}" @if ($item['active']) aria-current="page" @endif><span>{{ $item['label'] }}</span></a>
                @endforeach
            </nav>
            <div class="flex items-center gap-1 ml-auto lg:ml-1 shrink-0">
                <x-lang-switch class="max-[379px]:[&>a]:px-1.5" />
                @if ($accountLink)
                    {{-- Desktop: an icon between 1024 and 1280px (the menu is full), icon and words from 1280px; phones: in the menu --}}
                    <a href="{{ $accountLink['url'] }}" class="hidden lg:inline-flex btn btn-ghost btn-sm px-2 xl:px-3 whitespace-nowrap" title="{{ $accountLink['label'] }}"><x-icon :name="$accountLink['icon']" /><span class="sr-only xl:not-sr-only">{{ $accountLink['label'] }}</span></a>
                @endif
                @if (Route::has('join') && ! $account?->isMember())
                    <a href="{{ route('join') }}" class="hidden sm:inline-flex btn btn-primary btn-sm ml-1 whitespace-nowrap"><span class="xl:hidden">{{ __('Join') }}</span><span class="hidden xl:inline">{{ __('Become a member') }}</span></a>
                @endif
                <button type="button" class="lg:hidden btn btn-ghost btn-sm px-2" x-ref="menuButton" @click="open ? close() : show()" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="mobile-nav" aria-label="{{ __('Menu') }}"><x-icon name="menu" /></button>
            </div>
        </div>
        {{-- Phone and tablet menu: a sidebar from the right (moved to <body> so nothing on the page covers it) --}}
        <template x-teleport="body">
            <div class="lg:hidden fixed inset-0 z-[90]" x-show="open" x-cloak @keydown.tab="trap($event)">
                <div class="absolute inset-0 bg-black/50" x-show="open" x-transition.opacity.duration.250ms @click="close()"></div>
                <nav id="mobile-nav" x-ref="drawer" aria-label="{{ __('Main mobile') }}"
                     class="absolute inset-y-0 right-0 w-[min(86vw,360px)] bg-paper shadow-2xl flex flex-col"
                     x-show="open" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full">
                    <div class="flex items-center gap-2.5 h-16 px-4 border-b border-rule shrink-0">
                        <x-seal :size="34" :estd="false" class="shrink-0" aria-hidden="true" />
                        <span class="font-display text-[1.15rem] text-sindoor truncate">{{ $clubName }}</span>
                        <button type="button" x-ref="drawerClose" @click="close()" class="ml-auto w-10 h-10 rounded-full hover:bg-paper-2 grid place-items-center shrink-0" aria-label="{{ __('Close') }}"><x-icon name="x" class="w-5 h-5" /></button>
                    </div>
                    <div class="flex-1 overflow-y-auto overscroll-contain px-3 py-3">
                        <ul class="space-y-0.5">
                            @foreach ($mainMenu as $item)
                                <li><a href="{{ $item['url'] }}" class="side-link" @if ($item['active']) aria-current="page" @endif><x-icon :name="$menuIcons[$item['route']] ?? 'chevron-right'" class="w-[18px] h-[18px]" /><span>{{ $item['label'] }}</span></a></li>
                            @endforeach
                        </ul>
                        @if ($moreMenu)
                            <p class="mt-4 px-3 text-[11.5px] font-semibold uppercase tracking-[0.14em] text-ink-3">{{ __('More') }}</p>
                            <ul class="mt-1 space-y-0.5">
                                @foreach ($moreMenu as $item)
                                    <li><a href="{{ $item['url'] }}" class="side-link" @if ($item['active']) aria-current="page" @endif><x-icon :name="$menuIcons[$item['route']]" class="w-[18px] h-[18px]" /><span>{{ $item['label'] }}</span></a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div class="shrink-0 border-t border-rule px-4 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))] space-y-2.5">
                        @if (Route::has('join') && ! $account?->isMember())
                            <a href="{{ route('join') }}" class="btn btn-primary w-full"><x-icon name="user-plus" />{{ __('Become a member') }}</a>
                        @endif
                        @if ($accountLink)
                            <a href="{{ $accountLink['url'] }}" class="btn {{ $account ? 'btn-primary' : 'btn-line' }} w-full"><x-icon :name="$accountLink['icon']" />{{ $accountLink['label'] }}</a>
                        @endif
                        <button type="button" x-show="! $store.install.installed" @click="close(); $store.install.start()" class="btn btn-ghost w-full text-ink-2"><x-icon name="download" />{{ __('Get the app') }}</button>
                        <div class="flex items-center gap-4 pt-1.5 text-[14px] text-ink-2">
                            <a href="tel:{{ preg_replace('/\s+/', '', $club['phone']) }}" class="inline-flex items-center gap-1.5 hover:text-ink"><x-icon name="phone" class="w-4 h-4" /><span class="num">{{ $club['phone'] }}</span></a>
                            <a href="https://wa.me/{{ $club['whatsapp'] }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-1.5 hover:text-ink"><x-icon name="message-circle" class="w-4 h-4" />WhatsApp</a>
                        </div>
                    </div>
                </nav>
            </div>
        </template>
    </header>

    <x-flash />

    <main id="main" tabindex="-1" class="focus:outline-none">
        {{ $slot }}
    </main>

    <footer class="site-footer bg-ink text-white/75 text-[15px]">
        {{-- The club letter: sign up with a WhatsApp number or an email address (saved as an enquiry for the committee) --}}
        <div class="news-band relative overflow-hidden">
            <div class="relative z-[1] mx-auto max-w-[1160px] px-5 py-12 sm:py-14 lg:py-16 grid lg:grid-cols-12 gap-8 lg:gap-14 lg:items-center">
                <div class="lg:col-span-6 relative">
                    <p class="flex items-center gap-2 text-[12.5px] leading-none font-semibold uppercase tracking-[0.14em] text-marigold"><span class="w-1.5 h-1.5 rounded-full bg-marigold"></span><span>{{ __('The club letter') }}</span></p>
                    <h2 class="mt-3 font-display text-white leading-[1.08] text-[clamp(1.9rem,4.2vw,2.75rem)]">{{ __('Never miss a puja, a match or a meeting') }}</h2>
                    <p class="mt-3 text-white/70 max-w-[46ch]">{{ __('Two or three short notes a month: the date, the time and what to bring. No ads, and your number is never shared.') }}</p>
                    <ul class="mt-5 flex flex-wrap gap-2 text-[13.5px]">
                        <li class="news-chip"><x-icon name="flame" class="w-4 h-4 text-marigold" /><span>{{ __('Puja dates') }}</span></li>
                        <li class="news-chip"><x-icon name="trophy" class="w-4 h-4 text-marigold" /><span>{{ __('Sports and contests') }}</span></li>
                        <li class="news-chip"><x-icon name="users" class="w-4 h-4 text-marigold" /><span>{{ __('Meeting notices') }}</span></li>
                    </ul>
                </div>
                @php($newsVia = old('via', session('news_done') === 'email' ? 'email' : 'wa'))
                <form method="POST" action="{{ route('forms.news') }}" id="club-letter" class="lg:col-span-5 lg:col-start-8 relative scroll-mt-24" novalidate
                      x-data="{ via: @js($newsVia) }">
                    @csrf
                    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <input type="hidden" name="via" :value="via">
                    <p class="text-[13.5px] font-semibold text-white/85" id="news-via-label">{{ __('Where should we send it?') }}</p>
                    <div class="mt-2.5 grid grid-cols-2 gap-1 p-1 rounded-full bg-white/[.08] border border-white/10 text-[14px]" role="radiogroup" aria-labelledby="news-via-label"
                         @keydown.arrow-left.prevent="via = via === 'wa' ? 'email' : 'wa'; $nextTick(() => $el.querySelector('[aria-checked=true]').focus())"
                         @keydown.arrow-right.prevent="via = via === 'wa' ? 'email' : 'wa'; $nextTick(() => $el.querySelector('[aria-checked=true]').focus())">
                        <button type="button" role="radio" :aria-checked="(via === 'wa').toString()" :tabindex="via === 'wa' ? 0 : -1" @click="via = 'wa'" class="news-tab"><x-icon name="message-circle" class="w-4 h-4" /><span>{{ __('WhatsApp') }}</span></button>
                        <button type="button" role="radio" :aria-checked="(via === 'email').toString()" :tabindex="via === 'email' ? 0 : -1" @click="via = 'email'" class="news-tab"><x-icon name="mail" class="w-4 h-4" /><span>{{ __('Email') }}</span></button>
                    </div>
                    <label class="sr-only" for="news-contact"><span x-show="via === 'wa'">{{ __('Mobile number') }}</span><span x-show="via === 'email'" x-cloak>{{ __('Email address') }}</span></label>
                    <div class="mt-3 sm:flex sm:items-center sm:rounded-full sm:bg-white sm:p-1.5 sm:focus-within:ring-4 sm:focus-within:ring-marigold/40">
                        <div class="flex-1 min-w-0 flex items-center rounded-full bg-white text-ink focus-within:ring-4 focus-within:ring-marigold/40 sm:focus-within:ring-0">
                            <span class="pl-5 pr-2 text-ink-3 font-semibold num shrink-0" x-show="via === 'wa'" aria-hidden="true">+91</span>
                            <input id="news-contact" name="contact" required value="{{ old('contact') }}"
                                   :type="via === 'wa' ? 'tel' : 'email'" :inputmode="via === 'wa' ? 'tel' : 'email'" :autocomplete="via === 'wa' ? 'tel' : 'email'"
                                   :placeholder="via === 'wa' ? '98300 00000' : 'name@example.com'" :class="via === 'wa' ? 'num' : 'pl-5'"
                                   @error('contact') aria-invalid="true" aria-describedby="news-contact-error" @enderror
                                   class="w-full min-w-0 h-12 pr-4 bg-transparent text-ink rounded-full placeholder:text-ink-3/70 focus:outline-none">
                        </div>
                        <button class="btn btn-primary h-12 px-6 rounded-full shrink-0 w-full sm:w-auto mt-2 sm:mt-0"><span>{{ __('Subscribe') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></button>
                    </div>
                    @error('contact')<p id="news-contact-error" class="mt-2 sm:pl-5 text-[13.5px] text-marigold">{{ $message }}</p>@enderror
                    @if (session('news_done'))
                        <p class="mt-3 flex items-center gap-2 text-[14px] text-white sm:pl-5" role="status"><x-icon name="circle-check" class="w-4 h-4 shrink-0 text-marigold" /><span>{{ session('news_done') === 'email' ? __('Done. Club news will come to your inbox. Unsubscribe any time.') : __('Done. You’ll get club notices on WhatsApp. Reply STOP to leave.') }}</span></p>
                    @else
                        <p class="mt-3 flex items-center gap-2 text-[12.5px] text-white/55 sm:pl-5"><x-icon name="shield-check" class="w-4 h-4 shrink-0" /><span x-show="via === 'wa'">{{ __('Reply STOP any time to leave.') }}</span><span x-show="via === 'email'" x-cloak>{{ __('Every email has a one-click unsubscribe link.') }}</span></p>
                    @endif
                </form>
            </div>
        </div>
        <div class="mx-auto max-w-[1160px] px-5">
            <div class="py-12 grid grid-cols-2 md:grid-cols-12 gap-x-6 gap-y-10">
                <div class="col-span-2 md:col-span-4">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-white">
                        <x-seal :size="52" :estd="false" color="#fff" aria-hidden="true" />
                        <span><span class="block font-display text-[22px] leading-none">{{ $clubName }}</span><span class="block mt-1 text-[12px] uppercase tracking-[0.12em] text-white/55">{{ __('Gede Uttarpara · since 2014') }}</span></span>
                    </a>
                    <p class="mt-5 max-w-[36ch] text-white/65">{{ __('Run by neighbours, for neighbours. Pujas, sports, a serving team and the para’s own nursery.') }}</p>
                    <p class="mt-6 text-[13px] font-semibold uppercase tracking-[0.1em] text-white">{{ __('Follow the club') }}</p>
                    <ul class="mt-3 flex flex-wrap gap-2.5">
                        @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube'] as $network => $label)
                            @if (! empty($club['social'][$network]))
                                <li><a href="{{ $club['social'][$network] }}" target="_blank" rel="noopener" class="social-btn social-{{ $network }}" aria-label="{{ $label }}" title="{{ $label }}"><x-social-icon :network="$network" /></a></li>
                            @endif
                        @endforeach
                        <li><a href="https://wa.me/{{ $club['whatsapp'] }}" target="_blank" rel="noopener" class="social-btn social-whatsapp" aria-label="WhatsApp" title="WhatsApp"><x-social-icon network="whatsapp" /></a></li>
                    </ul>
                    <x-lang-switch dark long class="mt-6 w-max" />
                    {{-- The member app on the phone (resources/js/install.js) --}}
                    <div x-data x-show="! $store.install.installed" class="mt-6">
                        <button type="button" @click="$store.install.start()" class="inline-flex items-center gap-2.5 h-11 pl-3 pr-4 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold ring-1 ring-white/15 transition">
                            <span class="w-7 h-7 rounded-lg bg-sindoor grid place-items-center"><x-icon name="smartphone" class="w-4 h-4" /></span>{{ __('Get the app') }}
                        </button>
                        <p class="mt-2 text-[13px] text-white/55 max-w-[34ch]">{{ __('Dues, your member card and event passes on your phone. Free; no app store needed.') }}</p>
                    </div>
                </div>
                <nav class="md:col-span-2" aria-label="{{ __('Footer: club') }}">
                    <h2 class="text-[13px] font-semibold uppercase tracking-[0.1em] text-white">{{ __('Club') }}</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach (\App\Support\SiteMenu::available(\App\Support\SiteMenu::FOOTER_CLUB) as $item)
                            <li><a href="{{ route($item['route']) }}" class="hover:text-white">{{ __($item['label']) }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                <nav class="md:col-span-2" aria-label="{{ __('Footer: services and members') }}">
                    <h2 class="text-[13px] font-semibold uppercase tracking-[0.1em] text-white">{{ __('Services') }}</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach (\App\Support\SiteMenu::available(\App\Support\SiteMenu::FOOTER_SERVICES) as $item)
                            <li><a href="{{ route($item['route']) }}" class="hover:text-white">{{ __($item['label']) }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                <div class="col-span-2 md:col-span-4">
                    <h2 class="text-[13px] font-semibold uppercase tracking-[0.1em] text-white">{{ __('Visit us') }}</h2>
                    <ul class="mt-4 space-y-3">
                        <li class="flex gap-3"><x-icon name="map-pin" class="size-[18px] mt-0.5 text-white/50" /><span>{{ $bn ? $club['address_bn'] : $club['address'] }}</span></li>
                        <li class="flex gap-3"><x-icon name="clock" class="size-[18px] mt-0.5 text-white/50" /><span>{{ $bn ? $club['hours_bn'] : $club['hours'] }}</span></li>
                        <li class="flex gap-3"><x-icon name="phone" class="size-[18px] mt-0.5 text-white/50" /><a href="tel:{{ preg_replace('/\s+/', '', $club['phone']) }}" class="num hover:text-white">{{ $club['phone'] }}</a></li>
                        <li class="flex gap-3"><x-icon name="message-circle" class="size-[18px] mt-0.5 text-white/50" /><a href="https://wa.me/{{ $club['whatsapp'] }}" target="_blank" rel="noopener" class="hover:text-white">{{ __('Message on WhatsApp') }}</a></li>
                    </ul>
                    @if (Route::has('contact'))
                        <a href="{{ route('contact') }}" class="mt-5 inline-flex items-center gap-1.5 font-semibold text-white hover:underline"><span>{{ __('Directions and contact form') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
                    @endif
                </div>
            </div>

            <div class="py-6 border-t border-white/10 flex flex-col md:flex-row gap-3 md:items-center md:justify-between text-[13px] text-white/55">
                <p>© <span class="num">{{ digits(now()->year) }}</span> {{ __('Gede Maitree Club, Gede Uttarpara') }} · {{ __('Reg. No.') }} <span class="num">{{ $club['reg_no'] }}</span></p>
                <div class="flex flex-wrap gap-x-5 gap-y-2">
                    @if (Route::has('privacy'))
                        <a href="{{ route('privacy') }}" class="hover:text-white">{{ __('Privacy') }}</a>
                    @endif
                    @if (Route::has('terms'))
                        <a href="{{ route('terms') }}" class="hover:text-white">{{ __('Terms') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <button type="button" class="to-top" x-data="{ shown: false }" @scroll.window.passive="shown = window.scrollY > 900" :class="{ 'is-shown': shown }"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })" aria-label="{{ __('Back to top') }}" tabindex="-1"><x-icon name="arrow-up" /></button>
    {{-- How to install the member app when the browser has no install prompt to show (iPhone, Firefox…) --}}
    <div x-data x-show="$store.install.help" x-cloak class="fixed inset-0 z-[95] flex items-end sm:items-center justify-center sm:p-6" role="dialog" aria-modal="true" aria-labelledby="install-h"
         @keydown.escape.window="$store.install.close()">
        <div class="absolute inset-0 bg-black/50" x-show="$store.install.help" x-transition.opacity @click="$store.install.close()"></div>
        <div class="relative w-full sm:max-w-[420px] bg-paper rounded-t-3xl sm:rounded-3xl shadow-2xl p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))]"
             x-show="$store.install.help" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95">
            <button type="button" @click="$store.install.close()" class="absolute right-3 top-3 w-10 h-10 rounded-full hover:bg-paper-2 grid place-items-center" aria-label="{{ __('Close') }}"><x-icon name="x" class="w-5 h-5" /></button>
            <div class="flex items-center gap-3">
                <img src="{{ asset('icons/member-192.png') }}" alt="" class="w-14 h-14 rounded-2xl shadow-sm">
                <div>
                    <h2 id="install-h" class="font-display text-[1.35rem] leading-tight">{{ __('Get the app') }}</h2>
                    <p class="text-[14px] text-ink-2">{{ __('Maitree Club on your home screen, like any other app.') }}</p>
                </div>
            </div>
            <ol class="mt-5 space-y-3 text-[15px]" x-show="$store.install.ios">
                <li class="flex gap-3 items-start"><span class="w-7 h-7 rounded-full bg-sindoor-wash text-sindoor grid place-items-center shrink-0 font-semibold num">{{ digits(1) }}</span><span>{{ __('Open this page in Safari.') }}</span></li>
                <li class="flex gap-3 items-start"><span class="w-7 h-7 rounded-full bg-sindoor-wash text-sindoor grid place-items-center shrink-0 font-semibold num">{{ digits(2) }}</span><span>{{ __('Tap the Share button') }} <x-icon name="share" class="inline w-4 h-4 -mt-1 text-[#0a84ff]" /> {{ __('at the bottom of the screen.') }}</span></li>
                <li class="flex gap-3 items-start"><span class="w-7 h-7 rounded-full bg-sindoor-wash text-sindoor grid place-items-center shrink-0 font-semibold num">{{ digits(3) }}</span><span>{{ __('Choose Add to Home Screen') }} <x-icon name="square-plus" class="inline w-4 h-4 -mt-1" />{{ __(', then Add.') }}</span></li>
            </ol>
            <ol class="mt-5 space-y-3 text-[15px]" x-show="! $store.install.ios">
                <li class="flex gap-3 items-start"><span class="w-7 h-7 rounded-full bg-sindoor-wash text-sindoor grid place-items-center shrink-0 font-semibold num">{{ digits(1) }}</span><span>{{ __('Open the browser menu') }} <x-icon name="ellipsis-vertical" class="inline w-4 h-4 -mt-1" /></span></li>
                <li class="flex gap-3 items-start"><span class="w-7 h-7 rounded-full bg-sindoor-wash text-sindoor grid place-items-center shrink-0 font-semibold num">{{ digits(2) }}</span><span>{{ __('Choose Install app or Add to Home screen.') }}</span></li>
                <li class="flex gap-3 items-start"><span class="w-7 h-7 rounded-full bg-sindoor-wash text-sindoor grid place-items-center shrink-0 font-semibold num">{{ digits(3) }}</span><span>{{ __('On a computer, look for the install icon at the right of the address bar.') }}</span></li>
            </ol>
            <p class="mt-5 text-[13px] text-ink-3">{{ __('Members sign in with their mobile number or email. Pages you have opened still show without the internet.') }}</p>
            <button type="button" @click="$store.install.close()" class="mt-5 btn btn-line w-full">{{ __('Got it') }}</button>
        </div>
    </div>
</body>
</html>
