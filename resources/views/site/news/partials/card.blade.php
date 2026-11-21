{{-- One story in a list. --}}
@php($cover = $post->photoUrl(900))
<article class="group relative flex flex-col">
    <div class="relative aspect-[16/10] rounded-[18px] overflow-hidden bg-paper-3">
        @if ($cover)
            <img src="{{ $cover }}" alt="" loading="lazy" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.04]">
        @else
            <div class="w-full h-full grid place-items-center bg-sindoor-wash text-sindoor"><x-icon name="newspaper" class="w-10 h-10" /></div>
        @endif
        @if ($post->videos)
            <span class="absolute left-3 bottom-3 chip h-7 px-2.5 bg-black/60 text-white text-[12.5px]"><x-icon name="play" class="w-3.5 h-3.5" />{{ trans_choice(':count video|:count videos', count($post->videos), ['count' => digits(count($post->videos))]) }}</span>
        @endif
    </div>
    <p class="mt-4 flex flex-wrap items-center gap-x-2 text-[13px] text-ink-3">
        @if ($post->category)<span class="font-semibold uppercase tracking-[0.12em] text-sindoor">{{ $post->category->tr('name') }}</span><span aria-hidden="true">·</span>@endif
        <time datetime="{{ $post->published_at?->toDateString() }}">{{ local_date($post->published_at) }}</time>
    </p>
    <h3 class="mt-1.5 font-display text-[1.45rem] leading-snug"><a href="{{ route('news.show', $post) }}" class="after:absolute after:inset-0 hover:text-sindoor-deep">{{ $post->tr('title') }}</a></h3>
    <p class="mt-2 text-[15px] text-ink-2 line-clamp-3">{{ $post->tr('summary') }}</p>
</article>
