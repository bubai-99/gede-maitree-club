<x-layouts.site :title="__('We couldn’t find that page')" noindex>
    <section class="mx-auto max-w-[760px] px-5 py-20 text-center">
        <p class="font-display text-[clamp(4rem,14vw,7rem)] leading-none text-sindoor num">{{ digits(404) }}</p>
        <h1 class="mt-4 poster text-[clamp(2rem,6vw,3.25rem)]">{{ __('We couldn’t find that page') }}</h1>
        <p class="mt-4 text-[18px] text-ink-2 max-w-[44ch] mx-auto">{{ __('The link may be old. Go to one of these pages instead.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('home') }}" class="btn btn-primary h-12 px-6">{{ __('Home') }}</a>
            <a href="{{ route('events.index') }}" class="btn btn-line h-12 px-6">{{ __('Events') }}</a>
            <a href="{{ route('notices.index') }}" class="btn btn-line h-12 px-6">{{ __('Notices') }}</a>
            <a href="{{ route('contact') }}" class="btn btn-line h-12 px-6">{{ __('Contact') }}</a>
        </div>
    </section>
</x-layouts.site>
