<x-layouts.site :title="__('Panjika')" :description="__('Today’s tithi, nakshatra, sunrise and Bengali date for Gede, and the puja and wedding days from the club’s panjika.')">
    <section data-panjika-page>
        <div class="banner pj-banner">
            <div class="relative mx-auto max-w-[1160px] px-5 pt-14 pb-12 sm:pt-20 sm:pb-16 min-h-[360px] sm:min-h-[420px] grid md:grid-cols-12 gap-8 items-end">
                <div class="md:col-span-8 min-w-0">
                    <nav class="crumbs text-[13px] mb-5" aria-label="{{ __('Breadcrumb') }}"><a href="{{ route('home') }}">{{ __('Home') }}</a><span class="mx-2 text-white/40">/</span><span class="text-white" aria-current="page">{{ __('Panjika') }}</span></nav>
                    <p class="eyebrow text-marigold" id="pj-eyebrow">{{ __('Panjika') }}</p>
                    <h1 class="mt-3 font-display leading-[1.02] text-[clamp(2.4rem,6vw,4.4rem)]" id="pj-title">{{ __('Panjika') }}</h1>
                    <p class="mt-3 text-[17px] sm:text-[19px] text-white/80" id="pj-sub">{{ local_date(now(), 'l, j F Y') }}</p>
                    <div class="mt-7 flex flex-wrap items-center gap-2">
                        <button type="button" class="btn btn-glass h-11 w-11 px-0" data-pj-step="-1" aria-label="{{ __('Previous day') }}"><x-icon name="chevron-left" /></button>
                        <label class="btn btn-glass h-11 px-4 cursor-pointer relative"><x-icon name="calendar-days" /><span>{{ __('Pick a date') }}</span><input type="date" id="pj-date" class="absolute inset-0 opacity-0 cursor-pointer" aria-label="{{ __('Pick a date') }}"></label>
                        <button type="button" class="btn btn-glass h-11 w-11 px-0" data-pj-step="1" aria-label="{{ __('Next day') }}"><x-icon name="chevron-right" /></button>
                        <button type="button" class="btn bg-white text-ink hover:bg-paper-2 h-11 px-4" id="pj-today">{{ __('Today') }}</button>
                    </div>
                </div>
                <div class="md:col-span-4 flex md:justify-end">
                    <figure class="flex items-center gap-4 md:flex-col md:items-end md:text-right">
                        <span id="pj-moon" class="block w-24 h-24 sm:w-32 sm:h-32 md:w-44 md:h-44"></span>
                        <figcaption class="text-[14px] text-white/75" id="pj-moon-cap"></figcaption>
                    </figure>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-[1160px] px-5 py-12">
            <noscript><p class="rounded-2xl bg-paper-2 p-5 mb-6">{{ __('The panjika is worked out in your browser. Turn on JavaScript to see the tithi and the calendar.') }}</p></noscript>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4" id="pj-day" aria-live="polite"></div>
            <div id="pj-on-day"></div>

            <div class="mt-14 flex flex-wrap items-end justify-between gap-4">
                <div><p class="gz-kicker">{{ __('The month') }}</p><h2 class="mt-2 gz-headline text-[clamp(1.9rem,4vw,2.6rem)]" id="pj-month-title"></h2><p class="mt-1 text-[15px] text-ink-2" id="pj-month-bn"></p></div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-line h-10 w-10 px-0" id="pj-mprev" aria-label="{{ __('Previous month') }}"><x-icon name="chevron-left" /></button>
                    <button type="button" class="btn btn-line h-10 w-10 px-0" id="pj-mnext" aria-label="{{ __('Next month') }}"><x-icon name="chevron-right" /></button>
                </div>
            </div>
            <div class="mt-5 pj-cal" id="pj-cal"></div>
            <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-[13.5px] text-ink-2">
                <li class="flex items-center gap-2"><span class="pj-moon-full"></span>{{ __('Purnima') }}</li>
                <li class="flex items-center gap-2"><span class="pj-moon-new"></span>{{ __('Amavasya') }}</li>
                <li class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-sindoor"></span>{{ __('Puja') }}</li>
                <li class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-marigold"></span>{{ __('Parbon, sankranti') }}</li>
                <li class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-turmeric"></span>{{ __('Wedding day') }}</li>
                <li class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-ink"></span>{{ __('Club event') }}</li>
            </ul>

            <div class="mt-16 grid lg:grid-cols-12 gap-8 lg:gap-10">
                <div class="lg:col-span-7">
                    <p class="gz-kicker">{{ __('Pujas, parbon and club events') }}</p>
                    <h2 class="mt-2 gz-headline text-[clamp(1.9rem,4vw,2.6rem)]">{{ __('Coming up') }}</h2>
                    <ol class="mt-6 border-t border-rule" id="pj-pujas"></ol>
                </div>
                <div class="lg:col-span-5">
                    <div class="rounded-[22px] bg-turmeric-wash/60 p-6 sm:p-7 book-catering">
                        <p class="gz-kicker !text-turmeric">{{ __('Wedding days') }}</p>
                        <h2 class="mt-2 font-display leading-tight text-[clamp(1.7rem,3.4vw,2.2rem)]">{{ __('Dates and lagna') }}</h2>
                        <ul class="mt-5 space-y-2.5" id="pj-weddings"></ul>
                        <a href="{{ route('catering') }}#quote" class="btn btn-ink mt-6 w-full sm:w-auto"><x-icon name="utensils" /><span>{{ __('Book Maitree Serving') }}</span></a>
                    </div>
                </div>
            </div>

            <aside class="mt-14 rounded-2xl bg-paper-2 p-5 sm:p-6 flex gap-4 text-[14.5px] text-ink-2">
                <x-icon name="info" class="w-5 h-5 shrink-0 mt-0.5" />
                <div class="space-y-2" id="pj-note"></div>
            </aside>
        </div>
    </section>
    @include('partials.panjika-data')
</x-layouts.site>
