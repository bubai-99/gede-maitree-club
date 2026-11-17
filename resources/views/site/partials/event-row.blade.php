{{-- One event in a dated list (prototype eventRow / fullRow). $full adds the programme, calendar and pass links. --}}
@php
    $full ??= false;
    $calendar = 'https://calendar.google.com/calendar/render?'.http_build_query([
        'action' => 'TEMPLATE',
        'text' => $event->title,
        'dates' => $event->starts_at->copy()->utc()->format('Ymd\THis\Z').'/'.$event->starts_at->copy()->addHours(3)->utc()->format('Ymd\THis\Z'),
        'location' => $event->place,
    ]);
@endphp
<li class="grid grid-cols-[56px_1fr] sm:grid-cols-[64px_1fr_auto] gap-x-4 sm:gap-x-5 gap-y-2 py-4 border-b border-rule items-center">
    <div class="row-span-2 sm:row-span-1 self-start sm:self-center w-14 sm:w-16 rounded-xl border border-rule bg-card text-center overflow-hidden">
        <div class="bg-sindoor text-white text-[11px] font-bold tracking-wide py-0.5 uppercase">{{ local_date($event->starts_at, 'M') }}</div>
        <div class="font-display text-[1.6rem] sm:text-[1.8rem] leading-[1.35] num">{{ local_date($event->starts_at, 'd') }}</div>
    </div>
    <div class="min-w-0">
        <h3 class="font-display text-[1.2rem] sm:text-[1.35rem] leading-snug"><a href="{{ route('events.show', $event) }}" class="hover:text-sindoor">{{ $event->tr('title') }}</a></h3>
        <p class="text-[14.5px] text-ink-2 mt-0.5">{{ $event->tr('place') }} · {{ $event->tr('time_label') }}</p>
    </div>
    <div class="flex items-center gap-2 sm:justify-end">
        @if ($event->pass_required)
            <span class="chip bg-sindoor-wash text-sindoor-deep"><x-icon name="ticket" />{{ __('Pass in app') }}</span>
        @else
            <span class="chip bg-paper-2 text-ink-2"><x-icon name="users" />{{ __('Open to all') }}</span>
        @endif
    </div>
    @if ($full)
        <div class="col-start-2 sm:col-span-2 sm:col-start-2 flex flex-wrap gap-x-5 gap-y-1 text-sm">
            <a href="{{ route('events.show', $event) }}" class="font-semibold text-sindoor hover:underline inline-flex items-center gap-1.5"><x-icon name="arrow-right" class="w-4 h-4" />{{ __('Details & programme') }}</a>
            <a href="{{ $calendar }}" target="_blank" rel="noopener" class="font-semibold text-ink-2 hover:underline inline-flex items-center gap-1.5"><x-icon name="calendar-plus" class="w-4 h-4" />{{ __('Add to calendar') }}</a>
            @if ($event->pass_required)
                <a href="{{ route('member.events.show', $event) }}" class="font-semibold text-ink-2 hover:underline inline-flex items-center gap-1.5"><x-icon name="ticket" class="w-4 h-4" />{{ __('Get your pass') }}</a>
            @endif
        </div>
    @endif
</li>
