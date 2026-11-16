<x-layouts.member :title="$event->tr('title')">
    <a href="{{ route('member.events.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Events & passes') }}</a>
    <p class="mt-4 eyebrow text-sindoor">{{ $event->kind->label() }}</p>
    <h1 class="mt-1 font-display text-[2.2rem] leading-tight">{{ $event->tr('title') }}</h1>
    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-ink-2">
        <li class="flex items-center gap-1.5"><x-icon name="calendar-days" class="w-4 h-4" />{{ local_date($event->starts_at, 'l, j F Y') }}</li>
        <li class="flex items-center gap-1.5"><x-icon name="clock" class="w-4 h-4" />{{ $event->tr('time_label') }}</li>
        <li class="flex items-center gap-1.5"><x-icon name="map-pin" class="w-4 h-4" />{{ $event->tr('place') }}</li>
    </ul>

    @if ($event->pass_required)
        <a href="{{ route('member.card') }}" class="mt-5 card p-5 flex items-center gap-4 border-marigold">
            <x-icon name="ticket" class="w-7 h-7 text-turmeric" />
            <span><span class="block font-semibold">{{ __('Member pass needed') }}</span><span class="block text-[14px] text-ink-2">{{ __('Show your card at the gate') }}</span></span>
        </a>
    @endif

    <p class="mt-6 text-[17px] leading-relaxed text-ink-2 max-w-[62ch]">{{ $event->tr('about') }}</p>

    @if ($event->schedule)
        <h2 class="mt-8 font-display text-[1.5rem]">{{ __('Programme') }}</h2>
        <ol class="mt-3 border-t border-ink/80 max-w-[640px]">
            @foreach ($event->schedule as $item)
                <li class="leader py-3 border-b border-rule"><span>{{ local_text($item['what'] ?? '', $item['what_bn'] ?? null) }}</span><span class="font-semibold num">{{ local_text($item['time'] ?? '', $item['time_bn'] ?? null) }}</span></li>
            @endforeach
        </ol>
    @endif
</x-layouts.member>
