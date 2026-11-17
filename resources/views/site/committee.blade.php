@php
    $content = app(\App\Support\PageContent::class);
    $stats = config('club.stats');
    $club = $site->club();
    $years = now()->year - (int) $club['estd'];
@endphp
<x-layouts.site seo="committee" :title="$content->text('committee', 'title')">
    <x-page-banner page="committee" :crumb="__('About')" />

    {{-- What we stand for: mission and values --}}
    <div class="mx-auto max-w-[1160px] px-5 pt-14">
        <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            <div class="lg:col-span-5">
                <p class="text-[13px] font-semibold uppercase tracking-[0.12em] text-sindoor">{{ __('Our mission') }}</p>
                <p class="mt-4 font-display text-[clamp(1.7rem,3.4vw,2.3rem)] leading-[1.2]">{{ $content->text('committee', 'mission') }}</p>
            </div>
            <ul class="lg:col-span-7 grid sm:grid-cols-2 gap-4">
                @foreach ([1 => 'door-open', 2 => 'book-open-check', 3 => 'vote', 4 => 'hand-heart'] as $n => $icon)
                    <li class="rounded-2xl border border-rule p-5"><x-icon :name="$icon" class="w-6 h-6 text-sindoor" /><h3 class="mt-3 font-semibold text-[17px]">{{ $content->text('committee', "value{$n}_title") }}</h3><p class="mt-1 text-[15px] text-ink-2">{{ $content->text('committee', "value{$n}_text") }}</p></li>
                @endforeach
            </ul>
        </div>
        <div class="mt-8 rounded-2xl bg-ink text-white p-5 sm:p-6 flex flex-wrap items-center gap-4">
            <x-icon name="hand-heart" class="w-6 h-6 text-marigold shrink-0" />
            <p class="flex-1 min-w-[16rem] text-[16px]"><span class="font-semibold">{{ __('Everyone here is a volunteer.') }}</span> <span class="text-white/80">{{ __('See where the next events need a hand.') }}</span></p>
            <a href="{{ route('volunteer') }}" class="btn bg-white text-ink hover:bg-paper-2"><span>{{ __('Get involved') }}</span><x-icon name="arrow-right" /></a>
        </div>
    </div>

    {{-- Our story: history, numbers, timeline --}}
    <div class="mx-auto max-w-[1160px] px-5 pt-12 grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
        <div class="lg:col-span-6">
            <p class="text-[13px] font-semibold uppercase tracking-[0.12em] text-sindoor">{{ __('Our story') }}</p>
            <h2 class="mt-3 font-display text-[clamp(1.9rem,4vw,2.6rem)] leading-tight">{{ $content->text('home', 'story_title') }}</h2>
            <p class="mt-6 text-[17px] text-ink-2 leading-relaxed max-w-[52ch]">{{ $content->text('home', 'story_text') }}</p>
            <dl class="mt-8 grid grid-cols-2 gap-3">
                @foreach ([[__('Years together'), $years], [__('Member families'), $families], [__('Bhog plates, Durga Puja'), $stats['bhog_plates']], [__('Volunteers'), $stats['volunteers']]] as [$label, $value])
                    <div class="rounded-xl bg-paper-2 p-5"><dt class="text-[14px] text-ink-2">{{ $label }}</dt><dd class="mt-1 font-display text-[2.6rem] leading-none text-ink num">{{ \App\Support\Bangla::number((int) $value) }}</dd></div>
                @endforeach
            </dl>
        </div>
        <div class="lg:col-span-6">
            @include('site.partials.timeline', ['class' => 'lg:mt-10'])
        </div>
    </div>

    {{-- What the club does, in pictures --}}
    <div class="mx-auto max-w-[1160px] px-5 pt-16">
        <h2 class="font-display text-[clamp(1.9rem,4vw,2.6rem)] leading-tight mb-6">{{ __('The club through the year') }}</h2>
        <div class="grid sm:grid-cols-3 gap-5">
            @foreach ([['pujaCrowd', __('Pujas'), __('Durga, Kali and Saraswati Puja on the para ground')], ['race', __('Sports day'), __('Races and games for every age each December')], ['blood', __('Service'), __('Blood donation camps and help when the para needs it')]] as [$key, $title, $text])
                <figure><div class="relative aspect-[4/3] rounded-2xl overflow-hidden bg-paper-3 group"><div class="absolute inset-0 transition-transform duration-700 group-hover:scale-[1.04]"><x-photo :key="$key" :width="700" sizes="(min-width: 640px) 33vw, 100vw" /></div></div><figcaption class="mt-3"><span class="block font-semibold">{{ $title }}</span><span class="block text-[14px] text-ink-2">{{ $text }}</span></figcaption></figure>
            @endforeach
        </div>
        <p class="mt-4 text-[12.5px] text-ink-3">{{ __('Sample photos, to be replaced with the club’s own.') }} <x-photo-credit key="pujaCrowd" />, <x-photo-credit key="race" />, <x-photo-credit key="blood" />.</p>
    </div>

    <div class="mx-auto max-w-[1160px] px-5 pt-16 pb-20">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
            <h2 class="font-display text-[clamp(1.9rem,4vw,2.6rem)] leading-tight">{{ __('Committee 2025–27') }}</h2>
            <p class="text-[15px] text-ink-2">{{ __('Find us in the club room, any evening 6–9 pm') }}</p>
        </div>
        <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($committee as $person)
                <li class="card card-hover p-6">
                    <div class="flex items-center gap-4">
                        @if ($person->photoUrl())
                            <img src="{{ $person->photoUrl(200) }}" alt="" class="w-14 h-14 rounded-full object-cover shrink-0" loading="lazy">
                        @else
                            <span class="w-14 h-14 rounded-full bg-sindoor-wash text-sindoor-deep grid place-items-center font-display text-xl shrink-0" aria-hidden="true">{{ $person->initials() }}</span>
                        @endif
                        <div class="min-w-0"><p class="font-display text-xl leading-tight">{{ $person->tr('name') }}</p><p class="text-[15px] text-sindoor font-semibold">{{ $person->tr('role') }}</p></div>
                    </div>
                    @if ($person->since)
                        <p class="mt-4 text-sm text-ink-3 num">{{ __('On the committee since :year', ['year' => digits($person->since)]) }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-[13px] text-ink-3">{{ __('Committee members’ photos will be added with their permission.') }}</p>
        <div class="mt-16 grid lg:grid-cols-3 gap-5">
            <div class="card p-6">
                <x-icon name="book-open-check" class="w-7 h-7 text-sindoor" />
                <h2 class="mt-4 font-display text-[1.6rem] leading-tight">{{ __('The books are open') }}</h2>
                <p class="mt-2 text-[15px] text-ink-2">{{ __('Members see every receipt and expense, with the bill photo, in the app. The yearly accounts are audited and presented at the AGM.') }}</p>
            </div>
            <div class="card p-6">
                <x-icon name="vote" class="w-7 h-7 text-sindoor" />
                <h2 class="mt-4 font-display text-[1.6rem] leading-tight">{{ __('Next election') }}</h2>
                <p class="mt-2 text-[15px] text-ink-2">{{ __('AGM 2027, in April. One vote per paid-up family. Want to stand? Tell the secretary.') }}</p>
            </div>
            <div class="rounded-2xl bg-ink text-white p-6">
                <x-icon name="map-pin" class="w-7 h-7 text-marigold" />
                <h2 class="mt-4 font-display text-[1.6rem] leading-tight">{{ __('The club room') }}</h2>
                <p class="mt-2 text-[15px] text-white/80">{{ $content->text('committee', 'clubroom_text') }}</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="tel:{{ preg_replace('/\s+/', '', $club['phone']) }}" class="btn btn-sm bg-white text-ink hover:bg-paper-2"><x-icon name="phone" /><span class="num">{{ $club['phone'] }}</span></a>
                    <a href="https://wa.me/{{ $club['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-sm border border-white/30 hover:bg-white/10"><x-icon name="message-circle" />WhatsApp</a>
                </div>
            </div>
        </div>
    </div>

    {{-- The club together: text first, then the photo edge to edge --}}
    <figure class="mt-24 group-band" x-data x-init="new IntersectionObserver((e, o) => { if (e[0].isIntersecting) { $el.classList.add('is-in'); o.disconnect() } }, { rootMargin: '0px 0px -20% 0px' }).observe($el)">
        <div class="mx-auto max-w-[1160px] px-5 pb-10 sm:pb-12 grid lg:grid-cols-12 gap-6 lg:gap-10 items-end group-band-text">
            <div class="lg:col-span-7">
                <p class="text-[13px] font-semibold uppercase tracking-[0.12em] text-sindoor">{{ $content->text('committee', 'group_kicker') }}</p>
                <h2 class="mt-3 font-display leading-[1.02] text-[clamp(2.6rem,6.4vw,5rem)]">{{ $content->text('committee', 'group_title') }}</h2>
            </div>
            <div class="lg:col-span-5">
                <figcaption class="text-[17px] text-ink-2 leading-relaxed max-w-[44ch]">{{ $content->text('committee', 'group_caption') }}</figcaption>
                <dl class="mt-5 flex flex-wrap gap-x-7 gap-y-2">
                    @foreach ([[$years, __('years')], [$families, __('families')], [$stats['volunteers'], __('volunteers')]] as [$value, $label])
                        <div><dt class="sr-only">{{ $label }}</dt><dd class="flex items-baseline gap-1.5"><span class="font-display text-[1.9rem] leading-none text-sindoor num">{{ \App\Support\Bangla::number((int) $value) }}</span><span class="text-[14px] text-ink-3">{{ $label }}</span></dd></div>
                    @endforeach
                </dl>
            </div>
        </div>
        <div class="group-band-media"><x-photo key="group" :width="1800" sizes="100vw" /></div>
        <p class="mx-auto max-w-[1160px] px-5 py-3 text-[12px] text-ink-3">{{ __('Photo') }}: <x-photo-credit key="group" /> · {{ __('Sample photo, to be replaced with the club’s own.') }}</p>
    </figure>
</x-layouts.site>
