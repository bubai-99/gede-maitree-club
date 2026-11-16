<x-layouts.site :title="__('Panjika')" :description="__('Puja dates and auspicious days from the club’s panjika.')">
    <div class="pj-banner relative isolate overflow-hidden bg-night text-white">
        <div class="mx-auto max-w-[1160px] px-5 pt-16 pb-14">
            <nav aria-label="{{ __('Breadcrumb') }}" class="text-[14px] text-white/70"><a href="{{ route('home') }}" class="hover:text-white">{{ __('Home') }}</a> / <span aria-current="page">{{ __('Panjika') }}</span></nav>
            <p class="mt-5 eyebrow text-marigold">{{ __('Bengali calendar') }}</p>
            <h1 class="mt-3 font-display text-[clamp(2.4rem,6vw,4rem)] leading-[1.04]">{{ __('Panjika') }}</h1>
            <p class="mt-4 text-[17px] text-white/85 max-w-[56ch]">{{ __('Puja dates, sankranti and wedding days, checked by the committee against the club’s panjika.') }}</p>
        </div>
    </div>
    <div class="mx-auto max-w-[760px] px-5 py-16 text-center">
        <x-icon name="moon-star" class="w-10 h-10 text-sindoor mx-auto" />
        <h2 class="mt-4 font-display text-[2rem]">{{ __('The panjika is coming soon') }}</h2>
        <p class="mt-3 text-ink-2">{{ __('The committee is checking every date and time against the panjika before it goes online. Until then, ask at the club room or see the events calendar.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('events.index') }}" class="btn btn-primary h-12 px-6">{{ __('Events') }}</a>
            <a href="{{ route('contact') }}" class="btn btn-line h-12 px-6">{{ __('Contact') }}</a>
        </div>
    </div>
</x-layouts.site>
