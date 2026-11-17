@php
    $photoKey = $event->samplePhotoKey();
    $photoUrl = $event->photoUrl(1800);
    $club = $site->club();
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
        'location' => ['@type' => 'Place', 'name' => $event->place, 'address' => $club['address']],
        'organizer' => ['@type' => 'NGO', 'name' => $club['name'], 'url' => url('/')],
        'description' => (string) $event->about,
    ];
    $left = (int) now()->startOfDay()->diffInDays($event->starts_at->copy()->startOfDay(), false);
    $daysLabel = $left < 0 ? __('Finished') : ($left === 0 ? __('Today') : trans_choice('Tomorrow|In :count days', $left, ['count' => digits($left)]));
    $wa = fn (string $text) => 'https://wa.me/'.$club['whatsapp'].'?text='.rawurlencode($text);
    $dayMon = local_date($event->starts_at, 'j M');
@endphp
<x-layouts.site :title="$event->tr('title')" :description="\Illuminate\Support\Str::limit((string) $event->tr('about'), 155)" og-type="article" :image="$photoUrl">
    <x-slot:head>
        <script type="application/ld+json">@json($eventLd)</script>
    </x-slot:head>

    <div class="page-hero bg-sindoor-wash/50">
        <div class="mx-auto max-w-[1160px] px-5 pt-8 pb-12 lg:pb-16">
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1.5 text-[15px] font-semibold text-ink-2 hover:text-ink" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ __('All events') }}</a>
            <div class="mt-6 grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-7">
                    <p class="eyebrow text-sindoor flex flex-wrap items-center gap-3"><span>{{ $event->kind->label() }}</span><span class="chip h-7 px-3 normal-case tracking-normal text-[13px] {{ $left >= 0 && $left <= 14 ? 'bg-marigold text-ink' : 'bg-card text-ink-2 border border-rule' }}">{{ $daysLabel }}</span></p>
                    <h1 class="mt-3 font-display leading-[1.05] text-[clamp(2.4rem,6vw,3.75rem)] text-sindoor">{{ $event->tr('title') }}</h1>
                    @if ($event->isCancelled())
                        <p class="mt-4 inline-flex flex-wrap items-center gap-2 rounded-lg bg-sindoor px-4 py-2 text-white font-semibold" role="status"><x-icon name="ban" class="w-5 h-5" />{{ __('This event has been cancelled.') }} <span class="font-normal">{{ local_text($event->cancel_reason, $event->cancel_reason_bn) }}</span></p>
                    @endif
                    <ul class="mt-6 space-y-2.5 text-[17px]">
                        <li class="flex gap-3"><x-icon name="calendar-days" class="w-5 h-5 text-sindoor shrink-0 mt-0.5" /><span>{{ local_date($event->starts_at, 'l, j F Y') }}</span></li>
                        <li class="flex gap-3"><x-icon name="clock" class="w-5 h-5 text-sindoor shrink-0 mt-0.5" /><span>{{ $event->tr('time_label') }}</span></li>
                        <li class="flex gap-3"><x-icon name="map-pin" class="w-5 h-5 text-sindoor shrink-0 mt-0.5" /><span>{{ $event->tr('place') }} · <a href="{{ route('contact') }}" class="font-semibold text-sindoor hover:underline">{{ __('Directions') }}</a></span></li>
                        <li class="flex gap-3"><x-icon :name="$event->pass_required ? 'ticket' : 'users'" class="w-5 h-5 text-sindoor shrink-0 mt-0.5" /><span>{{ $event->pass_required ? __('Member pass needed, from the member app') : __('Open to everyone, no pass needed') }}</span></li>
                    </ul>
                    <div class="mt-8 flex flex-wrap gap-2.5" data-no-print>
                        @if ($event->pass_required)
                            <a href="{{ route('member.events.show', $event) }}" class="btn btn-primary h-12 px-6"><x-icon name="ticket" />{{ __('Get your pass') }}</a>
                        @endif
                        <a href="{{ $calendar }}" target="_blank" rel="noopener" class="btn {{ $event->pass_required ? 'btn-line' : 'btn-primary' }} h-12 px-5"><x-icon name="calendar-plus" />{{ __('Add to calendar') }}</a>
                        <a href="https://wa.me/?text={{ rawurlencode($event->title.' · '.route('events.show', $event)) }}" target="_blank" rel="noopener" class="btn btn-line h-12 px-5"><x-icon name="share-2" />{{ __('Share') }}</a>
                    </div>
                </div>
                @if ($photoKey || $photoUrl)
                    <figure class="lg:col-span-5 relative aspect-[4/3] rounded-2xl overflow-hidden bg-paper-2">
                        @if ($photoKey)
                            <x-photo :key="$photoKey" :width="1200" sizes="(min-width: 1024px) 460px, 100vw" class="absolute inset-0 w-full h-full object-cover" eager />
                            <figcaption class="absolute bottom-2 right-2 text-[11.5px] text-white bg-black/50 px-1.5 rounded"><x-photo-credit :key="$photoKey" /></figcaption>
                        @else
                            <img src="{{ $photoUrl }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                        @endif
                    </figure>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[1160px] px-5 pt-12 pb-20 grid lg:grid-cols-12 gap-12 items-start">
        <div class="lg:col-span-7">
            <h2 class="font-display leading-tight text-[1.85rem]">{{ __('About') }}</h2>
            <p class="mt-3 text-[18px] leading-relaxed text-ink-2 max-w-[60ch]">{{ $event->tr('about') ?: __('Details will be posted soon.') }}</p>

            @if ($event->schedule)
                <h2 class="mt-12 font-display leading-tight text-[1.85rem]">{{ __('Programme') }}</h2>
                <ol class="mt-4 space-y-3 text-[17px] max-w-[560px]">
                    @foreach ($event->schedule as $item)
                        <li class="leader"><span>{{ local_text($item['what'] ?? '', $item['what_bn'] ?? null) }}</span><span class="font-semibold num whitespace-nowrap">{{ local_text($item['time'] ?? '', $item['time_bn'] ?? null) }}</span></li>
                    @endforeach
                </ol>
                <p class="mt-3 text-sm text-ink-3">{{ __('Times may shift a little on the day.') }}</p>
            @endif

            @if ($event->bring)
                <h2 class="mt-12 font-display leading-tight text-[1.85rem]">{{ __('What to bring') }}</h2>
                <ul class="mt-4 space-y-2.5 text-[17px]">
                    @foreach ($event->bring as $item)
                        <li class="flex gap-3"><x-icon name="check" class="w-5 h-5 text-tulsi shrink-0 mt-0.5" /><span>{{ local_text($item['text'] ?? '', $item['text_bn'] ?? null) }}</span></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <aside class="lg:col-span-5 space-y-5">
            @if ($contact)
                <div class="card p-6">
                    <p class="eyebrow text-ink-2">{{ __('Questions? Ask') }}</p>
                    <div class="mt-3 flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-sindoor-wash text-sindoor-deep grid place-items-center font-display text-lg shrink-0" aria-hidden="true">{{ $contact->initials() }}</span>
                        <span><span class="block font-semibold text-[17px]">{{ $contact->tr('name') }}</span><span class="block text-[15px] text-ink-2">{{ $contact->tr('role') }}</span></span>
                    </div>
                    <a href="{{ $wa(__('Namaskar, a question about :event (:date):', ['event' => $event->title, 'date' => $dayMon])) }}" target="_blank" rel="noopener" class="btn btn-line btn-sm mt-5" data-no-print><x-icon name="message-circle" />{{ __('Ask on WhatsApp') }}</a>
                </div>
            @endif
            <div class="rounded-2xl bg-ink text-white p-6" data-no-print>
                <x-icon name="hand-heart" class="w-7 h-7 text-marigold" />
                <h2 class="mt-3 font-display text-[1.6rem] leading-tight">{{ __('Lend a hand') }}</h2>
                <p class="mt-1.5 text-white/80 text-[15px]">{{ __('Setting up, the gate, serving, clearing up. Even two hours helps.') }}</p>
                <a href="{{ route('volunteer') }}#vol-form" class="btn bg-white text-ink hover:bg-paper-2 btn-sm mt-5"><x-icon name="hand-heart" />{{ __('I’ll volunteer') }}</a>
            </div>
            @if ($event->chanda_box)
                <a href="{{ route('chanda') }}" class="block rounded-2xl bg-sindoor-wash/70 p-6 hover:bg-sindoor-wash" data-no-print>
                    <p class="font-semibold flex items-center gap-2"><x-icon name="hand-coins" class="w-5 h-5 text-sindoor" />{{ __('Puja chanda') }}</p>
                    <p class="mt-1.5 text-[15px] text-ink-2">{{ __('See what we are raising, lane by lane, and how to give.') }}</p>
                </a>
            @endif
        </aside>

        @if ($more->isNotEmpty())
            <div class="lg:col-span-12 pt-4" data-no-print>
                <h2 class="font-display leading-tight text-[1.85rem]">{{ __('Also coming up') }}</h2>
                <ul class="mt-5 grid sm:grid-cols-3 gap-4">
                    @foreach ($more as $other)
                        <li><a href="{{ route('events.show', $other) }}" class="group flex gap-4 items-center card card-hover p-4 h-full">
                            <span class="text-sindoor leading-none text-center w-12 shrink-0"><span class="block font-display text-[2rem] num">{{ local_date($other->starts_at, 'd') }}</span><span class="block text-[12px] font-semibold mt-0.5 uppercase">{{ local_date($other->starts_at, 'M') }}</span></span>
                            <span class="min-w-0"><span class="block font-semibold leading-snug group-hover:underline">{{ $other->tr('title') }}</span><span class="block text-sm text-ink-2 mt-0.5">{{ $other->tr('place') }}</span></span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-layouts.site>
