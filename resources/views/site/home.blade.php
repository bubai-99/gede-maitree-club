<x-layouts.site>
    <div class="banner min-h-[80svh] lg:min-h-[86svh] flex items-end md:items-center">
        <div class="banner-media" aria-hidden="true"><x-photo key="pujaNight" :width="1800" eager /></div>
        <div class="banner-shade"></div>
        <div class="relative w-full mx-auto max-w-[1160px] px-5 pt-28 pb-20 md:pb-24">
            <p class="eyebrow text-marigold">{{ __('Netaji Para · Est. 2014') }}</p>
            <h1 class="mt-4 font-display leading-[1.04] text-[clamp(2.6rem,6.4vw,4.8rem)] max-w-[15ch]">{{ __('Twelve years of pujas, play and looking out for each other') }}</h1>
            <p class="mt-5 text-[17px] sm:text-[19px] leading-relaxed text-white/85 max-w-[50ch]">{{ __('A club run by neighbours. Durga, Kali and Saraswati Puja, sports day, blood donation, a serving team for the para’s weddings and a nursery by the gate.') }}</p>
        </div>
        <x-photo-credit key="pujaNight" class="banner-credit" />
    </div>
</x-layouts.site>
