<x-layouts.member :title="__('Events & passes')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Events & passes') }}</h1>
    <ul class="mt-6 space-y-3">
        @forelse ($upcoming as $event)
            <li>
                <a href="{{ route('member.events.show', $event) }}" class="card card-hover p-5 flex items-center gap-5">
                    <span class="w-14 text-center leading-none shrink-0"><span class="block font-display text-[2rem] num text-sindoor">{{ local_date($event->starts_at, 'd') }}</span><span class="block text-[12.5px] font-semibold uppercase">{{ local_date($event->starts_at, 'M') }}</span></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-display text-[1.3rem] leading-snug">{{ $event->tr('title') }}</span>
                        <span class="block text-[14px] text-ink-2">{{ $event->tr('time_label') }} · {{ $event->tr('place') }}</span>
                    </span>
                    <span class="chip {{ $event->pass_required ? 'bg-marigold-wash text-turmeric' : 'bg-tulsi-wash text-tulsi' }}">{{ $event->pass_required ? __('Member pass') : __('Open to all') }}</span>
                </a>
            </li>
        @empty
            <li class="text-ink-2">{{ __('No events are planned yet. Check back soon.') }}</li>
        @endforelse
    </ul>

    @if ($past->isNotEmpty())
        <h2 class="mt-10 font-display text-[1.5rem]">{{ __('Recently') }}</h2>
        <ul class="mt-3 divide-y divide-rule">
            @foreach ($past as $event)
                <li class="py-3 flex justify-between gap-3"><span>{{ $event->tr('title') }}</span><span class="text-ink-3 num">{{ local_date($event->starts_at, 'j M Y') }}</span></li>
            @endforeach
        </ul>
    @endif
</x-layouts.member>
