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
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
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

    <header class="site-header sticky top-0 z-40 bg-paper border-b border-rule" x-data="{ open: false, scrolled: false }"
        x-init="scrolled = window.scrollY > 4" @scroll.window.passive="scrolled = window.scrollY > 4" :class="{ 'is-scrolled': scrolled }"
        @keydown.escape.window="open = false">
        <div class="mx-auto max-w-[1160px] px-4 sm:px-5 h-16 flex items-center gap-2 sm:gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 min-w-0 shrink-0" aria-label="{{ __('Gede Maitree Club home') }}">
                <x-seal :size="40" :estd="false" class="shrink-0" aria-hidden="true" />
                <span class="leading-none min-w-0">
                    <span class="block font-display text-[18px] sm:text-[22px] text-sindoor whitespace-nowrap">{{ $clubName }}</span>
                    <span class="block text-[11px] sm:text-[11.5px] font-semibold tracking-[0.14em] text-ink-2 mt-0.5 uppercase truncate">{{ __('Netaji Para · Est. 2014') }}</span>
                </span>
            </a>
            <nav class="hidden lg:flex items-center gap-0.5 ml-auto text-[15px] font-medium" aria-label="{{ __('Main') }}">
                @foreach ($mainMenu as $item)
                    <a href="{{ $item['url'] }}" class="nav-link {{ $item['class'] ?? '' }}" @if ($item['active']) aria-current="page" @endif><span>{{ $item['label'] }}</span></a>
                @endforeach
            </nav>
            <div class="flex items-center gap-1 ml-auto lg:ml-1 shrink-0">
                <x-lang-switch class="hidden lg:flex" />
                @if (Route::has('join'))
                    <a href="{{ route('join') }}" class="hidden sm:inline-flex btn btn-primary btn-sm ml-1 whitespace-nowrap"><span class="xl:hidden">{{ __('Join') }}</span><span class="hidden xl:inline">{{ __('Become a member') }}</span></a>
                @endif
                <button type="button" class="lg:hidden btn btn-ghost btn-sm px-2" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="mobile-nav" aria-label="{{ __('Menu') }}"><x-icon name="menu" /></button>
            </div>
        </div>
        <nav id="mobile-nav" class="lg:hidden border-t border-rule bg-paper max-h-[calc(100dvh-4rem)] overflow-y-auto" x-show="open" x-cloak x-transition.opacity aria-label="{{ __('Main mobile') }}">
            <ul class="px-5 py-2 text-[17px] grid sm:grid-cols-2 sm:gap-x-8">
                @foreach ($mainMenu as $item)
                    <li><a href="{{ $item['url'] }}" class="mnav-link" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                @endforeach
                @if (Route::has('chanda'))
                    <li><a href="{{ route('chanda') }}" class="mnav-link" @if (request()->routeIs('chanda')) aria-current="page" @endif>{{ __('Chanda & accounts') }}</a></li>
                @endif
            </ul>
            <div class="px-5 pt-3 pb-5 flex flex-wrap items-center gap-3 border-t border-rule">
                @if (Route::has('join'))
                    <a href="{{ route('join') }}" class="btn btn-primary btn-sm sm:hidden">{{ __('Become a member') }}</a>
                @endif
                @if (Route::has('member.login'))
                    <a href="{{ route('member.login') }}" class="btn btn-line btn-sm"><x-icon name="log-in" /><span>{{ __('Member login') }}</span></a>
                @endif
                <x-lang-switch class="ml-auto" long />
            </div>
        </nav>
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
                        <span><span class="block font-display text-[22px] leading-none">{{ $clubName }}</span><span class="block mt-1 text-[12px] uppercase tracking-[0.12em] text-white/55">{{ __('Netaji Para · since 2014') }}</span></span>
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
                <p>© <span class="num">{{ digits(now()->year) }}</span> {{ __('Gede Maitree Club, Netaji Para') }} · {{ __('Reg. No.') }} <span class="num">{{ $club['reg_no'] }}</span></p>
                <div class="flex flex-wrap gap-x-5 gap-y-2">
                    @if (Route::has('privacy'))
                        <a href="{{ route('privacy') }}" class="hover:text-white">{{ __('Privacy') }}</a>
                    @endif
                    @if (Route::has('terms'))
                        <a href="{{ route('terms') }}" class="hover:text-white">{{ __('Terms') }}</a>
                    @endif
                    <span>{{ __('Photos: Unsplash (samples)') }}</span>
                    @if ($site->isSample())
                        <span class="text-marigold">{{ __('Sample content: names, numbers and photos are placeholders.') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <button type="button" class="to-top" x-data="{ shown: false }" @scroll.window.passive="shown = window.scrollY > 900" :class="{ 'is-shown': shown }"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })" aria-label="{{ __('Back to top') }}" tabindex="-1"><x-icon name="arrow-up" /></button>
</body>
</html>
