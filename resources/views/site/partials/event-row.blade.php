{{-- One event in a dated list. --}}
<li class="border-b border-rule">
    <a href="{{ route('events.show', $event) }}" class="group flex items-center gap-4 sm:gap-6 py-4">
        <span class="w-16 shrink-0 text-center leading-none">
            <span class="block font-display text-[2.2rem] num text-sindoor">{{ local_date($event->starts_at, 'd') }}</span>
            <span class="block mt-1 text-[12.5px] font-semibold uppercase tracking-wide text-ink-2">{{ local_date($event->starts_at, 'M') }}</span>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block font-display text-[1.35rem] leading-snug group-hover:text-sindoor">{{ $event->tr('title') }}</span>
            <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[14px] text-ink-2">
                <span>{{ local_date($event->starts_at, 'l') }} · {{ $event->tr('time_label') }}</span>
                <span class="inline-flex items-center gap-1"><x-icon name="map-pin" class="w-3.5 h-3.5" />{{ $event->tr('place') }}</span>
            </span>
        </span>
        <span class="hidden sm:inline-flex chip {{ $event->pass_required ? 'bg-marigold-wash text-turmeric' : 'bg-tulsi-wash text-tulsi' }}">
            {{ $event->pass_required ? __('Member pass') : __('Open to all') }}
        </span>
        <x-icon name="chevron-right" class="w-5 h-5 text-ink-3 group-hover:text-sindoor" />
    </a>
</li>
