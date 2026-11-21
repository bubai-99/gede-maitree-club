@php
    $cover = $post->photoUrl(1800);
    $images = $post->getMedia('images');
    $embeds = $post->embeds();
    $share = route('news.show', $post);
@endphp
<x-layouts.site :title="$post->tr('title')" :description="$post->tr('summary')" :image="$cover" og-type="article">
    <article>
        <header class="mx-auto max-w-[820px] px-5 pt-12 sm:pt-16">
            <nav aria-label="{{ __('Breadcrumb') }}" class="text-[14px] text-ink-3"><a href="{{ route('home') }}" class="hover:text-ink">{{ __('Home') }}</a> / <a href="{{ route('news.index') }}" class="hover:text-ink">{{ __('News') }}</a>@if ($post->category) / <a href="{{ route('news.index', ['category' => $post->category->slug]) }}" class="hover:text-ink">{{ $post->category->tr('name') }}</a>@endif</nav>
            <p class="mt-6 flex flex-wrap items-center gap-x-2 text-[14px] text-ink-3">
                @if ($post->category)<a href="{{ route('news.index', ['category' => $post->category->slug]) }}" class="font-semibold uppercase tracking-[0.12em] text-sindoor hover:underline">{{ $post->category->tr('name') }}</a><span aria-hidden="true">·</span>@endif
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ local_date($post->published_at) }}</time>
                <span aria-hidden="true">·</span><span>{{ trans_choice(':count min read|:count min read', $post->readingMinutes(), ['count' => digits($post->readingMinutes())]) }}</span>
            </p>
            <h1 class="mt-3 font-display text-[clamp(2.2rem,5vw,3.4rem)] leading-[1.08]">{{ $post->tr('title') }}</h1>
            <p class="mt-4 text-[19px] text-ink-2 leading-relaxed">{{ $post->tr('summary') }}</p>
        </header>

        @if ($cover)
            <figure class="mx-auto max-w-[1080px] px-5 mt-10">
                <img src="{{ $cover }}" alt="" class="w-full max-h-[620px] object-cover rounded-[22px] bg-paper-3" fetchpriority="high">
                @if ($post->samplePhotoKey())<figcaption class="mt-2 text-[13px] text-ink-3">{{ __('Photo') }}: <x-photo-credit :key="$post->samplePhotoKey()" /></figcaption>@endif
            </figure>
        @endif

        <div class="mx-auto max-w-[720px] px-5 mt-10 news-prose">
            {{ \App\Support\RichText::render($post->tr('body')) }}
        </div>

        @if ($embeds)
            <section class="mx-auto max-w-[820px] px-5 mt-12" aria-labelledby="videos-h">
                <h2 id="videos-h" class="font-display text-[1.8rem]">{{ __('Videos') }}</h2>
                @foreach ($embeds as $video)
                    @include('partials.video-embed', ['embed' => $video['embed'], 'caption' => $video['caption']])
                @endforeach
            </section>
        @endif

        @if ($images->isNotEmpty())
            <section class="mx-auto max-w-[1080px] px-5 mt-12" aria-labelledby="photos-h">
                <h2 id="photos-h" class="font-display text-[1.8rem]">{{ __('Photos') }}</h2>
                <ul class="mt-5 grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach ($images as $image)
                        <li><a href="{{ $image->getUrl() }}" target="_blank" rel="noopener" class="block aspect-[4/3] rounded-xl overflow-hidden bg-paper-3 group"><img src="{{ $image->getUrl() }}" alt="{{ __('Photo :n of :count', ['n' => digits($loop->iteration), 'count' => digits($images->count())]) }}" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.04]"></a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="mx-auto max-w-[720px] px-5 mt-12 flex flex-wrap items-center gap-2 border-t border-rule pt-6">
            <span class="text-[14px] font-semibold text-ink-2 mr-2">{{ __('Share') }}</span>
            <a href="https://wa.me/?text={{ rawurlencode($post->tr('title').' '.$share) }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-icon name="message-circle" />WhatsApp</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($share) }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-social-icon network="facebook" class="w-4 h-4" />Facebook</a>
            <button type="button" class="btn btn-line btn-sm" x-data="{ done: false }" @click="navigator.clipboard.writeText(@js($share)); done = true; setTimeout(() => done = false, 2000)"><x-icon name="link" /><span x-text="done ? @js(__('Copied')) : @js(__('Copy link'))">{{ __('Copy link') }}</span></button>
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="mx-auto max-w-[1160px] px-5 mt-20 pb-20" aria-labelledby="more-h">
            <div class="flex items-end justify-between gap-4">
                <h2 id="more-h" class="gz-headline text-[clamp(1.8rem,4vw,2.4rem)]">{{ __('More stories') }}</h2>
                <a href="{{ route('news.index') }}" class="text-[15px] font-semibold text-sindoor hover:underline inline-flex items-center gap-1"><span>{{ __('All news') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-12">
                @foreach ($more as $item)
                    @include('site.news.partials.card', ['post' => $item])
                @endforeach
            </div>
        </section>
    @else
        <div class="pb-20"></div>
    @endif
</x-layouts.site>
