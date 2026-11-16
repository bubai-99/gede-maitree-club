{{-- A committee member card. --}}
<li class="card p-5 flex items-center gap-4">
    @if ($person->photoUrl())
        <img src="{{ $person->photoUrl(200) }}" alt="" class="w-14 h-14 rounded-full object-cover shrink-0" loading="lazy">
    @else
        <span class="w-14 h-14 rounded-full bg-sindoor-wash text-sindoor-deep grid place-items-center font-display text-lg shrink-0" aria-hidden="true">{{ $person->initials() }}</span>
    @endif
    <span class="min-w-0">
        <span class="block font-semibold text-[17px] leading-snug">{{ $person->tr('name') }}</span>
        <span class="block text-[14px] text-ink-2">{{ $person->tr('role') }}</span>
        @if ($person->since)
            <span class="block text-[13px] text-ink-3 num">{{ __('Since :year', ['year' => digits($person->since)]) }}</span>
        @endif
    </span>
</li>
