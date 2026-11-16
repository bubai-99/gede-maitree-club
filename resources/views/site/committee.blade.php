@php
    $content = app(\App\Support\PageContent::class);
    $stats = config('club.stats');
    $club = $site->club();
    $years = now()->year - (int) $club['estd'];
@endphp
<x-layouts.site seo="committee" :title="$content->text('committee', 'title')">
    <x-page-banner page="committee" :crumb="__('About')" />

    <div class="mx-auto max-w-[1160px] px-5 py-14">
        <div class="grid lg:grid-cols-12 gap-10">
            <div class="lg:col-span-5">
                <p class="gz-kicker">{{ __('Our mission') }}</p>
                <p class="mt-3 font-display text-[clamp(1.6rem,3.5vw,2.2rem)] leading-snug">{{ $content->text('committee', 'mission') }}</p>
            </div>
            <ul class="lg:col-span-7 grid sm:grid-cols-2 gap-4">
                @foreach ([1, 2, 3, 4] as $n)
                    <li class="card p-5"><h2 class="font-display text-[1.3rem]">{{ $content->text('committee', "value{$n}_title") }}</h2><p class="mt-1 text-ink-2">{{ $content->text('committee', "value{$n}_text") }}</p></li>
                @endforeach
            </ul>
        </div>

        <div class="mt-16 grid lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-6">
                <p class="gz-kicker">{{ __('Our story') }}</p>
                <h2 class="mt-2 gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ $content->text('home', 'story_title') }}</h2>
                <p class="mt-4 text-[17px] leading-relaxed text-ink-2">{{ $content->text('home', 'story_text') }}</p>
                <dl class="mt-6 grid grid-cols-2 gap-4">
                    @foreach ([[__('Years together'), $years], [__('Member families'), $families], [__('Bhog plates, Durga Puja'), $stats['bhog_plates']], [__('Volunteers'), $stats['volunteers']]] as [$label, $value])
                        <div class="card p-4"><dt class="text-[13px] text-ink-3">{{ $label }}</dt><dd class="font-display text-[1.8rem] num">{{ \App\Support\Bangla::number((int) $value) }}</dd></div>
                    @endforeach
                </dl>
            </div>
            @include('site.partials.timeline', ['class' => 'lg:col-span-6'])
        </div>

        <section class="mt-16" aria-labelledby="committee-h">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 id="committee-h" class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Committee 2025–27') }}</h2>
                <p class="text-ink-2">{{ __('Find us in the club room, any evening 6–9 pm') }}</p>
            </div>
            <ul class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($committee as $person)
                    @include('site.partials.person', ['person' => $person])
                @endforeach
            </ul>
            <p class="mt-3 text-[14px] text-ink-3">{{ __('Committee members’ photos will be added with their permission.') }}</p>
        </section>

        <div class="mt-16 grid md:grid-cols-3 gap-5">
            <div class="card p-6"><h2 class="font-display text-[1.4rem]">{{ __('The books are open') }}</h2><p class="mt-2 text-ink-2">{{ __('Members see every receipt and expense, with the bill photo, in the app. The yearly accounts are audited and presented at the AGM.') }}</p></div>
            <div class="card p-6"><h2 class="font-display text-[1.4rem]">{{ __('Next election') }}</h2><p class="mt-2 text-ink-2">{{ __('AGM 2027, in April. One vote per paid-up family. Want to stand? Tell the secretary.') }}</p></div>
            <div class="card p-6">
                <h2 class="font-display text-[1.4rem]">{{ __('The club room') }}</h2>
                <p class="mt-2 text-ink-2">{{ $content->text('committee', 'clubroom_text') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="tel:{{ preg_replace('/\s+/', '', $club['phone']) }}" class="btn btn-line btn-sm"><x-icon name="phone" /><span class="num">{{ $club['phone'] }}</span></a>
                    <a href="https://wa.me/{{ $club['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-icon name="message-circle" /><span>WhatsApp</span></a>
                </div>
            </div>
        </div>
    </div>

    <figure class="group-band bg-ink text-white">
        <div class="mx-auto max-w-[1160px] px-5 pt-14 pb-8">
            <p class="gz-kicker text-marigold">{{ $content->text('committee', 'group_kicker') }}</p>
            <h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ $content->text('committee', 'group_title') }}</h2>
            <figcaption class="mt-3 text-white/80 max-w-[56ch]">{{ $content->text('committee', 'group_caption') }}</figcaption>
        </div>
        <div class="group-band-media"><x-photo key="group" :width="1800" /></div>
        <p class="mx-auto max-w-[1160px] px-5 py-3 text-[12px] text-white/60">{{ __('Photo') }}: <x-photo-credit key="group" /> · {{ __('Sample photo, to be replaced with the club’s own.') }}</p>
    </figure>
</x-layouts.site>
