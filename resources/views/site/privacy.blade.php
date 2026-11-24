@php($club = $site->club())
<x-layouts.site :title="__('Privacy')" :description="__('What the club keeps about members, why, and who can see it.')">
    <article class="mx-auto max-w-[760px] px-5 pt-12 pb-20">
        <h1 class="poster text-[clamp(2.2rem,6vw,3.25rem)]">{{ __('Privacy') }}</h1>
        <p class="mt-3 text-[14px] text-ink-3">{{ __('Last updated 13 November 2026') }}</p>
        <div class="mt-8 space-y-8 text-[17px] leading-relaxed text-ink-2">
            @foreach ([
                [__('What we keep'), __('What the membership form asks: your name, photo, date of birth, blood group, mobile, email (optional), address and only the last 4 digits of your Aadhaar; the names and relation of family joining on your form; and your subscription and receipt history. We never store a full Aadhaar number.')],
                [__('Why'), __('To run membership, receipts, event passes and the AGM accounts. Blood group is only looked at when a blood donation camp needs it.')],
                [__('Who can see it'), __('Only committee members who need it for their job: the treasurer sees accounts, volunteers see only gate passes. Nothing is sold or shared outside the club.')],
                [__('Maps'), __('The map on the Contact page is shown by Google Maps; Google may set its own cookies when it loads. The rest of the site does not use cookies from other companies.')],
                [__('Your choices'), __('See and change your details in the app’s Settings. If you leave the club, ask and we delete your details, keeping only the accounts the law requires.')],
            ] as [$heading, $text])
                <section><h2 class="font-display text-[1.6rem] text-ink">{{ $heading }}</h2><p class="mt-2">{{ $text }}</p></section>
            @endforeach
        </div>
        @include('site.partials.privacy-contact', ['club' => $club])
        <p class="mt-8"><a href="{{ route('terms') }}" class="font-semibold text-sindoor hover:underline">{{ __('Terms') }} →</a></p>
    </article>
</x-layouts.site>
