@php
    /** @var \App\Support\PageContent $content */
    $content = app(\App\Support\PageContent::class);
    $stats = config('club.stats');
    $spendTotal = $lastPuja ? array_sum(array_column($lastPuja['spend'], 'amount')) : 0;
    $strip = collect($gallery)->flatMap(fn ($day) => collect($day['items'])->map(fn ($item) => $item + ['day' => $day]))->take(8);
@endphp
<x-layouts.site seo="home">
    {{-- Hero --}}
    <div class="banner min-h-[56svh] lg:min-h-[60svh] flex items-end md:items-center">
        <div class="banner-media" aria-hidden="true">
            @if ($content->samplePhotoKey('home'))
                <x-photo :key="$content->samplePhotoKey('home')" :width="1800" eager />
            @elseif ($content->photoUrl('home'))
                <img src="{{ $content->photoUrl('home') }}" alt="" class="w-full h-full object-cover" fetchpriority="high">
            @endif
        </div>
        <div class="banner-shade"></div>
        <div class="relative w-full mx-auto max-w-[1160px] px-5 pt-20 pb-14 md:pb-16 grid lg:grid-cols-12 gap-10 lg:gap-8 items-end">
            <div class="lg:col-span-8 min-w-0">
                <p class="eyebrow text-marigold">{{ $content->text('home', 'hero_kicker') }}</p>
                <h1 class="mt-4 font-display leading-[1.04] text-[clamp(2rem,4.2vw,3.1rem)] max-w-[20ch]">{{ $content->text('home', 'hero_title') }}</h1>
                <p class="mt-5 text-[17px] sm:text-[19px] leading-relaxed text-white/85 max-w-[50ch]">{{ $content->text('home', 'hero_sub') }}</p>
                <div class="mt-8 flex flex-wrap gap-2.5">
                    <a href="{{ route('join') }}" class="btn btn-primary h-12 px-6 text-base">{{ __('Become a member') }}</a>
                    <a href="{{ route('events.index') }}" class="btn btn-glass h-12 px-6 text-base"><span>{{ __('See what’s on') }}</span><x-icon name="arrow-right" /></a>
                </div>
            </div>
            <div class="lg:col-span-4 lg:justify-self-end w-full lg:max-w-[360px]">
                <div class="hero-glass rounded-[22px] p-5 sm:p-6">
                    <p class="flex items-center justify-between gap-3 text-[12px] font-semibold uppercase tracking-[0.12em] text-white/75"><span>{{ __('The club in numbers') }}</span><span class="num normal-case tracking-normal font-medium text-white/55">{{ digits($stats['year']) }}</span></p>
                    <dl class="mt-4 grid grid-cols-3 lg:grid-cols-1 gap-3 lg:gap-0 lg:divide-y lg:divide-white/15">
                        @foreach ([[__('Member families'), $families], [__('Volunteers'), $stats['volunteers']], [__('Bhog plates'), $stats['bhog_plates']]] as [$label, $value])
                            <div class="lg:flex lg:items-baseline lg:justify-between lg:py-3"><dt class="text-[12.5px] text-white/70 lg:order-2">{{ $label }}</dt><dd class="mt-1 lg:mt-0 font-display text-[1.75rem] sm:text-[2rem] leading-none num">{{ \App\Support\Bangla::number((int) $value) }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
        @if ($content->samplePhotoKey('home'))
            <p class="banner-credit">{{ __('Photo') }}: <x-photo-credit :key="$content->samplePhotoKey('home')" /></p>
        @endif
    </div>

    {{-- Next event: a ticket --}}
    @if ($nextEvent)
        <div class="mx-auto max-w-[1160px] px-5 pt-10">
            <div class="gz-ticket flex flex-col md:flex-row md:items-stretch">
                <div class="flex items-center gap-5 px-6 py-5 md:pr-8">
                    <div class="text-center leading-none">
                        <span class="block text-[11.5px] font-bold uppercase tracking-[0.16em]">{{ __('Next up') }}</span>
                        <span class="block mt-2 font-display text-[3rem] num">{{ local_date($nextEvent->starts_at, 'd') }}</span>
                        <span class="block mt-1 text-[13px] font-semibold">{{ local_date($nextEvent->starts_at, 'F') }}</span>
                    </div>
                    <div class="min-w-0 border-l border-ink/25 pl-5">
                        <p class="font-display text-[1.6rem] leading-tight"><a href="{{ route('events.show', $nextEvent) }}" class="hover:underline decoration-2">{{ $nextEvent->tr('title') }}</a></p>
                        <p class="mt-1 text-[14px]">{{ local_date($nextEvent->starts_at, 'l') }} · {{ $nextEvent->tr('time_label') }}</p>
                        <p class="text-[14px] flex items-center gap-1.5"><x-icon name="map-pin" class="w-4 h-4" /><span>{{ $nextEvent->tr('place') }}</span></p>
                    </div>
                </div>
                <div class="md:ml-auto flex flex-wrap items-center gap-4 px-6 py-5 border-t md:border-t-0 gz-perf md:border-l-2 border-dashed border-ink/30" style="border-left-style:dashed">
                    @php
                        $secs = max(0, $nextEvent->starts_at->getTimestamp() - now()->getTimestamp());
                        $cd = [[intdiv($secs, 86400), __('days')], [intdiv($secs % 86400, 3600), __('hours')], [intdiv($secs % 3600, 60), __('minutes')]];
                    @endphp
                    @if ($secs === 0 || $nextEvent->starts_at->isToday())
                        <div class="bg-white/60 rounded-[4px] px-3 py-2 font-semibold">{{ __('Happening today') }}</div>
                    @else
                        <div class="flex gap-1.5 text-center num" role="timer" aria-label="{{ __(':days days :hours hours to go', ['days' => digits($cd[0][0]), 'hours' => digits($cd[1][0])]) }}">
                            @foreach ($cd as [$value, $unit])
                                <div class="bg-white/55 rounded-lg px-2.5 py-1.5 min-w-[56px]" aria-hidden="true"><div class="font-display text-[1.6rem] leading-none">{{ digits(str_pad((string) $value, 2, '0', STR_PAD_LEFT)) }}</div><div class="text-[11.5px] font-semibold mt-0.5 text-ink">{{ $unit }}</div></div>
                            @endforeach
                        </div>
                    @endif
                    <div class="flex gap-2">
                        <a href="{{ route('events.show', $nextEvent) }}" class="btn btn-ink h-11">{{ __('Details') }}</a>
                        <a href="https://wa.me/{{ $site->club()['whatsapp'] }}" target="_blank" rel="noopener" class="btn h-11 border border-ink/30 hover:bg-white/40"><x-icon name="message-circle" /><span>{{ __('Ask') }}</span></a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Today's panjika: worked out in the browser (resources/js/panjika) --}}
    <div class="mx-auto max-w-[1160px] px-5 {{ $nextEvent ? 'pt-3' : 'pt-10' }}">
        <div data-panjika-today class="min-h-[132px]"></div>
    </div>
    @include('partials.panjika-data')

    {{-- What the club does --}}
    <div class="mx-auto max-w-[1160px] px-5 pt-20">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
            <div>
                <p class="gz-kicker">{{ __('What we do') }}</p>
                <h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('Three things, one set of books') }}</h2>
            </div>
            <p class="text-[16px] text-ink-2 max-w-[42ch]">{{ __('What the serving team and the nursery earn pays for the pujas and the service work.') }}</p>
        </div>
        <div class="grid md:grid-cols-3 gap-5">
            @foreach ([
                ['events.index', 'book-club', 'pujaCrowd', '01', 'text-sindoor', __('Club life'), __('Pujas, games and service'), __('Durga, Kali and Saraswati Puja, sports day, blood donation camps and the winter picnic.'), __('See the calendar')],
                ['catering', 'book-catering', 'feast', '02', 'text-turmeric', __('Maitree Serving'), __('Serving at your occasion'), __('The team that serves bhog to thousands, at your annaprashan or wedding. You bring the food; from ₹35 a guest.'), __('See the packages')],
                ['nursery', 'book-nursery', 'potted', '03', 'text-tulsi', __('Maitree Nursery'), __('Plants by the club gate'), __('Joba, shiuli, tulsi, fruit saplings and winter flowers. Members get 10% off.'), __('Browse plants')],
            ] as [$route, $book, $photo, $no, $colour, $kicker, $title, $text, $cta])
                <a href="{{ route($route) }}" class="{{ $book }} group block">
                    <div class="relative aspect-[4/3] md:aspect-[4/5] rounded-[18px] overflow-hidden bg-paper-3">
                        <div class="absolute inset-0 transition-transform duration-700 group-hover:scale-[1.04]"><x-photo :key="$photo" sizes="(min-width: 768px) 33vw, 100vw" /></div>
                        <span class="absolute left-4 top-4 font-display text-[2.6rem] leading-none text-white drop-shadow-[0_2px_8px_rgba(0,0,0,.5)]">{{ digits($no) }}</span>
                        <span class="absolute right-3 top-3 text-[11.5px] text-white bg-black/50 px-1.5 rounded"><x-photo-credit :key="$photo" plain /></span>
                    </div>
                    <p class="mt-4 text-[13px] font-bold tracking-[0.14em] uppercase {{ $colour }}">{{ $kicker }}</p>
                    <h3 class="mt-1 font-display text-[1.6rem] leading-snug">{{ $title }}</h3>
                    <p class="mt-2 text-[15px] text-ink-2">{{ $text }}</p>
                    <span class="mt-3 inline-flex items-center gap-1.5 text-[15px] font-semibold"><span>{{ $cta }}</span><x-icon name="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1" /></span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Our story --}}
    <div class="mt-20 gz border-y gz-hair">
        <div class="mx-auto max-w-[1160px] px-5 py-16 grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
            <div class="lg:col-span-6">
                <p class="gz-kicker">{{ __('Our story') }}</p>
                <h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ $content->text('home', 'story_title') }}</h2>
                <p class="mt-6 text-[17px] leading-relaxed text-ink-2 max-w-[52ch] gz-dropcap">{{ $content->text('home', 'story_text') }}</p>
                <p class="mt-6 text-[14px] text-ink-2 num">{{ __('Registered society · Reg. No.') }} {{ $site->club()['reg_no'] }}</p>
                <a href="{{ route('committee') }}" class="mt-5 inline-flex items-center gap-1.5 text-[15px] font-semibold text-sindoor hover:underline"><span>{{ __('About the club and committee') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            @include('site.partials.timeline', ['class' => 'lg:col-span-6'])
        </div>
    </div>

    {{-- Coming up + notice board --}}
    <div class="mx-auto max-w-[1160px] px-5 pt-20 grid lg:grid-cols-12 gap-10 lg:gap-14 items-start">
        <div class="lg:col-span-7">
            <div class="flex items-end justify-between gap-4 mb-4">
                <div><p class="gz-kicker">{{ __('Calendar') }}</p><h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('Coming up') }}</h2></div>
                <a href="{{ route('events.index') }}" class="text-[15px] font-semibold text-sindoor hover:underline pb-1 inline-flex items-center gap-1"><span>{{ __('All events') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            <ol class="border-t border-ink/80">
                @forelse ($upcoming as $event)
                    @include('site.partials.event-row', ['event' => $event])
                @empty
                    <li class="py-6 text-ink-2">{{ __('No events are planned yet. Check back soon.') }}</li>
                @endforelse
            </ol>
        </div>
        <aside class="lg:col-span-5" aria-labelledby="board-h">
            <div class="relative gz rounded-[18px] p-6 pt-8 border gz-hair">
                <span class="absolute -top-2.5 left-7 w-5 h-5 rounded-full bg-sindoor shadow-[0_2px_3px_rgba(0,0,0,.3)]" aria-hidden="true"></span>
                <div class="flex items-center justify-between gap-3">
                    <h2 id="board-h" class="font-display text-[1.7rem] leading-tight">{{ __('Notice board') }}</h2>
                    <a href="{{ route('notices.index') }}" class="text-[14px] font-semibold text-sindoor hover:underline">{{ __('All notices') }}</a>
                </div>
                <ul class="mt-2">
                    @foreach ($notices as $notice)
                        <li class="border-b border-ink/15 last:border-0">
                            <a href="{{ route('notices.show', $notice) }}" class="group block py-3.5">
                                <span class="text-[12.5px] flex items-center gap-2">@if ($notice->tag)<span class="font-bold uppercase tracking-wide text-sindoor-deep">{{ $notice->tr('tag') }}</span>@endif<span class="text-ink-2 num">{{ local_date($notice->published_at, 'j M') }}</span>@if ($notice->pinned)<x-icon name="pin" class="w-3.5 h-3.5 text-ink-3" /><span class="sr-only">{{ __('Pinned') }}</span>@endif</span>
                                <span class="block font-semibold leading-snug mt-1 group-hover:underline">{{ $notice->tr('title') }}</span>
                                <span class="block text-[15px] text-ink-2 mt-0.5 leading-snug">{{ $notice->tr('summary') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>

    {{-- Gallery strip --}}
    @if ($strip->isNotEmpty())
        <div class="pt-20">
            <div class="mx-auto max-w-[1160px] px-5 flex items-end justify-between gap-4 mb-6">
                <div><p class="gz-kicker">{{ __('From the gallery') }}</p><h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('The para in pictures') }}</h2></div>
                <a href="{{ route('gallery') }}" class="text-[15px] font-semibold text-sindoor hover:underline pb-1 inline-flex items-center gap-1"><span>{{ __('Open the gallery') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            <div class="swiper gallery-strip [mask-image:linear-gradient(90deg,transparent,#000_5%,#000_95%,transparent)]" data-gallery-strip="{{ $strip->count() }}">
                <div class="swiper-wrapper">
                    {{-- Twice over, so the moving strip never runs out; the copy is hidden from screen readers and the tab order. --}}
                    @foreach ([false, true] as $copy)
                        @foreach ($strip as $item)
                            <a href="{{ route('gallery') }}" class="swiper-slide relative !w-[210px] sm:!w-[260px] aspect-[4/5] rounded-[18px] overflow-hidden group bg-paper-3"
                               @if ($copy) aria-hidden="true" tabindex="-1" @else aria-label="{{ local_text($item['day']['title'], $item['day']['title_bn'] ?? null) }}" @endif>
                                <x-photo :key="$item['photo']" :width="700" sizes="300px" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.04]" />
                                <span class="absolute left-2 bottom-2 rounded bg-black/45 px-1.5 py-0.5 text-[10.5px] text-white/90"><x-photo-credit :key="$item['photo']" plain /></span>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Where the chanda went --}}
    @if ($lastPuja && $spendTotal > 0)
        <div class="mx-auto max-w-[1160px] px-5 pt-20 grid lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-5">
                <p class="gz-kicker">{{ __('Open books') }}</p>
                <h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('Where your chanda went') }}</h2>
                <p class="mt-4 text-[17px] text-ink-2 leading-relaxed max-w-[42ch]">{{ __('How each ₹100 of chanda was spent at :puja. Every bill is entered in the club khata, and the accounts are audited and presented at the AGM.', ['puja' => local_text($lastPuja['title'], $lastPuja['title_bn'] ?? null)]) }}</p>
                <p class="mt-6 flex items-baseline gap-3"><span class="font-display text-[clamp(2.6rem,6vw,3.6rem)] leading-none text-sindoor num">₹{{ digits(100) }}</span><span class="text-[15px] text-ink-2">{{ __('of chanda, shared out') }}</span></p>
                <p class="mt-2 text-[14px] text-ink-3">{{ __('The full figures are on the chanda page.') }}</p>
                <a href="{{ route('chanda') }}" class="mt-5 inline-flex items-center gap-1.5 text-[15px] font-semibold text-sindoor hover:underline"><span>{{ __('Full accounts and this year’s chanda') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            <div class="lg:col-span-7">
                @php $shares = \App\Support\Shares::of100(array_column($lastPuja['spend'], 'amount')); @endphp
                <div class="gz-ledger rounded-[18px] border gz-hair shadow-[0_1px_2px_rgba(0,0,0,.04)] overflow-hidden">
                    <div class="pl-12 pr-5 h-12 flex items-center justify-between border-b border-ink/25 text-[12.5px] font-bold uppercase tracking-wide text-ink-2"><span>{{ __('Spent on') }}</span><span>{{ __('Of every ₹100') }}</span></div>
                    <ol class="pl-12 pr-5">
                        @foreach ($lastPuja['spend'] as $i => $row)
                            @php $colour = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($row['colour'] ?? '')) ? $row['colour'] : '#2b2f36'; @endphp
                            <li class="flex items-center gap-3 text-[15.5px]"><span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $colour }}" aria-hidden="true"></span><span class="flex-1 min-w-0">{{ local_text($row['head'], $row['head_bn'] ?? null) }}</span><span class="hidden sm:block w-28 h-1.5 rounded-full bg-ink/10 overflow-hidden" aria-hidden="true"><span class="block h-full" style="width:{{ min(100, $shares[$i] * 2.5) }}%;background:{{ $colour }}"></span></span><span class="w-14 text-right font-semibold num">₹{{ digits($shares[$i]) }}</span></li>
                        @endforeach
                        <li class="flex items-center gap-3 font-semibold border-t-2 border-ink/70"><span class="flex-1">{{ __('Total') }}</span><span class="w-14 text-right text-sindoor num">₹{{ digits(100) }}</span></li>
                    </ol>
                </div>
            </div>
        </div>
    @endif

    {{-- Who runs the club --}}
    @if ($committee->isNotEmpty())
        <div class="mx-auto max-w-[1160px] px-5 pt-20">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
                <div><p class="gz-kicker">{{ __('Committee 2025–27') }}</p><h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('Who runs the club') }}</h2></div>
                <a href="{{ route('committee') }}" class="text-[15px] font-semibold text-sindoor hover:underline pb-1 inline-flex items-center gap-1"><span>{{ __('Full committee') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            <ul class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($committee as $person)
                    <li class="card p-5 flex items-center gap-4">
                        <span class="w-12 h-12 shrink-0 rounded-full bg-sindoor-wash text-sindoor-deep grid place-items-center font-semibold" aria-hidden="true">{{ $person->initials() }}</span>
                        <span class="min-w-0"><span class="block font-semibold leading-snug">{{ $person->tr('name') }}</span><span class="block text-[14px] text-ink-2">{{ $person->tr('role') }}</span></span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-[14px] text-ink-3">{{ __('Elected by members at the AGM every two years. All volunteers.') }}</p>
        </div>
    @endif

    {{-- Questions --}}
    <div class="mx-auto max-w-[1160px] px-5 pt-20 grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
        <div class="lg:col-span-4">
            <p class="gz-kicker">{{ __('Questions') }}</p>
            <h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('Common questions') }}</h2>
            <p class="mt-4 text-[16px] text-ink-2">{{ __('Anything else? Call the secretary, or drop in at the club any evening.') }}</p>
            <a href="tel:{{ preg_replace('/\s+/', '', $site->club()['phone']) }}" class="mt-5 btn btn-line"><x-icon name="phone" /><span class="num">{{ $site->club()['phone'] }}</span></a>
        </div>
        <div class="lg:col-span-8 border-t border-rule">
            @include('site.partials.faq')
        </div>
    </div>

    {{-- Join band --}}
    <div class="join-band mt-20 relative overflow-hidden bg-sindoor text-white">
        <div class="absolute -right-16 -top-16 opacity-15 pointer-events-none" aria-hidden="true"><x-seal :size="320" color="#fff" /></div>
        <div class="relative mx-auto max-w-[1160px] px-5 py-16 sm:py-20 grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7">
                <p class="gz-nameplate text-[clamp(1.5rem,3.2vw,2.1rem)]">{{ __('Become a member') }}</p>
                <h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('₹100 a month for your whole household') }}</h2>
                <p class="mt-4 text-[17px] text-white/90 leading-relaxed max-w-[48ch]">{{ __('The form takes five minutes and the committee approves it at the next meeting. The yearly plan and the admission fee are explained on the next step.') }}</p>
                <a href="{{ route('join') }}" class="btn bg-white text-ink hover:bg-paper-2 h-12 px-6 text-base mt-7"><span>{{ __('Apply to join') }}</span><x-icon name="arrow-right" /></a>
            </div>
            <ul class="lg:col-span-5 space-y-4 text-[17px]">
                <li class="flex gap-3"><x-icon name="ticket" class="w-6 h-6 text-marigold" /><span>{{ __('Member passes for bhog, the picnic and sports day') }}</span></li>
                <li class="flex gap-3"><x-icon name="badge-percent" class="w-6 h-6 text-marigold" /><span>{{ __('Member prices on serving and plants') }}</span></li>
                <li class="flex gap-3"><x-icon name="vote" class="w-6 h-6 text-marigold" /><span>{{ __('A vote at the AGM') }}</span></li>
                <li class="flex gap-3"><x-icon name="id-card" class="w-6 h-6 text-marigold" /><span>{{ __('A digital ID card in the app') }}</span></li>
            </ul>
        </div>
    </div>
</x-layouts.site>
