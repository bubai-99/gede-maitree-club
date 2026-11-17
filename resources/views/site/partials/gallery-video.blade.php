{{-- One video from a gallery album; opens the album on that item. $href, $row: {item, day, i}. --}}
@php
    $item = $row['item'];
    $day = $row['day'];
    $caption = local_text($item['caption'] ?? '', $item['caption_bn'] ?? null);
@endphp
<a href="{{ $href }}" class="group block" aria-label="{{ $caption }}">
    <div class="relative aspect-video rounded-xl overflow-hidden bg-paper-2">
        <x-photo :key="$item['photo']" :width="800" sizes="(min-width: 1280px) 270px, (min-width: 640px) 50vw, 100vw" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03]" />
        @if (! empty($item['duration']))
            <span class="absolute right-1.5 bottom-1.5 rounded-md bg-black/80 text-white text-[12px] font-semibold px-1.5 py-0.5 num">{{ digits($item['duration']) }}</span>
        @endif
    </div>
    <div class="mt-3 flex gap-3">
        <span class="w-9 h-9 rounded-full bg-white border border-rule grid place-items-center shrink-0"><x-seal :size="32" :rotate="-6" aria-hidden="true" /></span>
        <span class="min-w-0"><span class="block text-[15px] font-semibold leading-snug line-clamp-2 group-hover:text-sindoor">{{ $caption }}</span><span class="block text-[13px] text-ink-3 mt-0.5">{{ __('Gede Maitree Club') }}</span><span class="block text-[13px] text-ink-3 num">{{ local_text($day['title'], $day['title_bn'] ?? null) }} · {{ local_date(\Illuminate\Support\Carbon::parse($day['date']), 'j M Y') }}</span></span>
    </div>
</a>
