@php
    $photoKey = $event->samplePhotoKey();
    $photoUrl = $event->photoUrl(1800);
    $calendar = 'https://calendar.google.com/calendar/render?'.http_build_query([
        'action' => 'TEMPLATE',
        'text' => $event->title,
        'dates' => $event->starts_at->copy()->utc()->format('Ymd\THis\Z').'/'.$event->starts_at->copy()->addHours(3)->utc()->format('Ymd\THis\Z'),
        'location' => $event->place,
    ]);
    $eventLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $event->title,
        'startDate' => $event->starts_at->toAtomString(),
        'eventStatus' => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'location' => ['@type' => 'Place', 'name' => $event->place, 'address' => $site->club()['address']],
        'organizer' => ['@type' => 'NGO', 'name' => $site->club()['name'], 'url' => url('/')],
        'description' => (string) $event->about,
    ];
@endphp
<x-layouts.site :title="$event->tr('title')" :description="\Illuminate\Support\Str::limit((string) $event->tr('about'), 155)" og-type="article" :image="$photoUrl">
    <x-slot:head>
        <script type="application/ld+json">@json($eventLd)</script>
    </x-slot:head>

    <div class="banner">
        @if ($photoUrl)
            <div class="banner-media" aria-hidden="true">
                @if ($photoKey)
                    <x-photo :key="$photoKey" :width="1800" eager />
                @else
                    <img src="{{ $photoUrl }}" alt="" class="w-full h-full object-cover">
                @endif
            </div>
        @endif
        <div class="banner-shade"></div>
        <div class="relative mx-auto max-w-[1160px] px-5 pt-20 pb-14">
            <nav aria-label="{{ __('Breadcrumb') }}" class="crumbs text-[14px] mb-5">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ route('home') }}">{{ __('Home') }}</a></li><li aria-hidden="true" class="text-white/50">/</li>
                    <li><a href="{{ route('events.index') }}">{{ __('Events') }}</a></li><li aria-hidden="true" class="text-white/50">/</li>
                    <li aria-current="page" class="text-white/90">{{ $event->tr('title') }}</li>
                </ol>
            </nav>
            <p class="eyebrow text-marigold">{{ $event->kind->label() }}</p>
            <h1 class="mt-3 font-display leading-[1.04] text-[clamp(2.4rem,6vw,4.2rem)] max-w-[18ch]">{{ $event->tr('title') }}</h1>
            <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-[16px] text-white/90">
                <li class="flex items-center gap-2"><x-icon name="calendar-days" class="w-4 h-4 text-marigold" />{{ local_date($event->starts_at, 'l, j F Y') }}</li>
                <li class="flex items-center gap-2"><x-icon name="clock" class="w-4 h-4 text-marigold" />{{ $event->tr('time_label') }}</li>
                <li class="flex items-center gap-2"><x-icon name="map-pin" class="w-4 h-4 text-marigold" />{{ $event->tr('place') }}</li>
            </ul>
        </div>
        @if ($photoKey)
            <p class="banner-credit">{{ __('Photo') }}: <x-photo-credit :key="$photoKey" /></p>
        @endif
    </div>

    <div class="mx-auto max-w-[1160px] px-5 py-14 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-7">
            <p class="text-[18px] leading-relaxed text-ink-2">{{ $event->tr('about') }}</p>

            @if ($event->schedule)
                <h2 class="mt-10 font-display text-[1.8rem]">{{ __('Programme') }}</h2>
                <ol class="mt-4 border-t border-ink/80">
                    @foreach ($event->schedule as $item)
                        <li class="leader py-3 border-b border-rule text-[16px]"><span>{{ local_text($item['what'] ?? '', $item['what_bn'] ?? null) }}</span><span class="font-semibold num">{{ local_text($item['time'] ?? '', $item['time_bn'] ?? null) }}</span></li>
                    @endforeach
                </ol>
            @endif

            @if ($event->bring)
                <h2 class="mt-10 font-display text-[1.8rem]">{{ __('What to bring') }}</h2>
                <ul class="mt-4 space-y-2">
                    @foreach ($event->bring as $item)
                        <li class="flex gap-3"><x-icon name="check" class="w-5 h-5 text-tulsi mt-0.5" /><span>{{ local_text($item['text'] ?? '', $item['text_bn'] ?? null) }}</span></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <aside class="lg:col-span-5 space-y-5">
            <div class="card p-6">
                <p class="chip {{ $event->pass_required ? 'bg-marigold-wash text-turmeric' : 'bg-tulsi-wash text-tulsi' }}">{{ $event->pass_required ? __('Member pass needed') : __('Open to everyone') }}</p>
                <p class="mt-3 text-ink-2">{{ $event->pass_required ? __('Members show their pass from the app at the gate.') : __('Everyone is welcome. No pass needed.') }}</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ $calendar }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-icon name="calendar-plus" /><span>{{ __('Add to calendar') }}</span></a>
                    @if ($event->pass_required)
                        <a href="{{ route('member.login') }}" class="btn btn-primary btn-sm"><x-icon name="ticket" /><span>{{ __('Get your pass') }}</span></a>
                    @endif
                </div>
            </div>
            @if ($contact)
                <div class="card p-6">
                    <p class="text-[13px] font-semibold uppercase tracking-wide text-ink-3">{{ __('Questions about this event') }}</p>
                    <div class="mt-3 flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-sindoor-wash text-sindoor-deep grid place-items-center font-display text-lg shrink-0" aria-hidden="true">{{ $contact->initials() }}</span>
                        <span><span class="block font-semibold text-[17px]">{{ $contact->tr('name') }}</span><span class="block text-[15px] text-ink-2">{{ $contact->tr('role') }}</span></span>
                    </div>
                    <a href="https://wa.me/{{ $site->club()['whatsapp'] }}" target="_blank" rel="noopener" class="mt-4 btn btn-line btn-sm"><x-icon name="message-circle" /><span>{{ __('Ask on WhatsApp') }}</span></a>
                </div>
            @endif
            @if ($event->chanda_box)
                <a href="{{ route('chanda') }}" class="card p-6 block hover:border-ink">
                    <p class="font-display text-[1.4rem]">{{ __('Chanda for this puja') }}</p>
                    <p class="mt-1 text-ink-2">{{ __('See what we are raising, lane by lane, and how to give.') }}</p>
                </a>
            @endif
        </aside>
    </div>

    @if ($more->isNotEmpty())
        <div class="mx-auto max-w-[1160px] px-5 pb-16">
            <h2 class="gz-headline text-[clamp(1.6rem,3.5vw,2.2rem)]">{{ __('Also coming up') }}</h2>
            <ol class="mt-4 border-t border-ink/80">
                @foreach ($more as $other)
                    @include('site.partials.event-row', ['event' => $other])
                @endforeach
            </ol>
        </div>
    @endif
</x-layouts.site>
