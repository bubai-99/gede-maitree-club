@php($club = $site->club())
<x-layouts.site :title="__('Terms')" :description="__('Terms for members, cards and the club’s services.')">
    <article class="mx-auto max-w-[760px] px-5 pt-12 pb-20">
        <h1 class="poster text-[clamp(2.2rem,6vw,3.25rem)]">{{ __('Terms') }}</h1>
        <p class="mt-3 text-[14px] text-ink-3">{{ __('Last updated 13 November 2026') }}</p>
        <div class="mt-8 space-y-6 text-[17px] leading-relaxed text-ink-2">
            <p>{{ __('Member cards are not transferable. If one is lost, tell the club; the old QR is cancelled and a new card is issued.') }}</p>
            <p>{{ __('Maitree Serving serves food that the host provides; the club does not cook. A 20% advance books the date, and the final bill is on the actual guest count.') }}</p>
            <p>{{ __('Plants are sold at the club gate. Members get 10% off. Prices and stock on the website can change.') }}</p>
            <p>{{ __('Membership is approved by the committee and follows the club’s bye-laws, adopted at the 2022 AGM.') }}</p>
        </div>
        @include('site.partials.privacy-contact', ['club' => $club])
        <p class="mt-8"><a href="{{ route('privacy') }}" class="font-semibold text-sindoor hover:underline">{{ __('Privacy') }} →</a></p>
    </article>
</x-layouts.site>
