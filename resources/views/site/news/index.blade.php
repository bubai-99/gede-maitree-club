<x-layouts.site seo="news" :title="app(\App\Support\PageContent::class)->text('news', 'title')">
    {{-- Filters and pages swap in place (resources/js/instant.js) --}}
    <div id="news-page" data-instant class="instant-region">
    <x-page-banner page="news" />

    <div class="mx-auto max-w-[1160px] px-5 pt-10 pb-20">
        @if ($categories->isNotEmpty())
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('News categories') }}" data-instant-top>
                <a href="{{ route('news.index') }}" class="chip h-9 px-4 text-[15px] border border-rule bg-card hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white aria-[current=page]:border-ink" @if (! $category) aria-current="page" @endif>{{ __('All') }} <span class="opacity-60 num">{{ digits($total) }}</span></a>
                @foreach ($categories as $option)
                    <a href="{{ route('news.index', ['category' => $option->slug]) }}" class="chip h-9 px-4 text-[15px] border border-rule bg-card hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white aria-[current=page]:border-ink" @if ($category?->id === $option->id) aria-current="page" @endif>{{ $option->tr('name') }} <span class="opacity-60 num">{{ digits($option->posts_count) }}</span></a>
                @endforeach
            </nav>
        @endif

        @if ($posts->isEmpty())
            <p class="mt-10 text-ink-3">{{ __('No stories here yet. Come back after the next puja.') }}</p>
        @else
            @php
                $lead = $posts->currentPage() === 1 ? $posts->first() : null;
                $rest = $lead ? $posts->getCollection()->slice(1) : $posts->getCollection();
            @endphp

            @if ($lead)
                @php($cover = $lead->photoUrl(1400))
                <article class="group relative mt-8 grid lg:grid-cols-12 gap-6 lg:gap-10 items-center">
                    <div class="lg:col-span-7 relative aspect-[16/10] rounded-[22px] overflow-hidden bg-paper-3">
                        @if ($cover)
                            <img src="{{ $cover }}" alt="" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.03]">
                        @else
                            <div class="w-full h-full grid place-items-center bg-sindoor-wash text-sindoor"><x-icon name="newspaper" class="w-14 h-14" /></div>
                        @endif
                        @if ($lead->featured)<span class="absolute left-4 top-4 chip h-7 px-3 bg-sindoor text-white text-[12.5px]">{{ __('Featured') }}</span>@endif
                    </div>
                    <div class="lg:col-span-5">
                        <p class="flex flex-wrap items-center gap-x-2 text-[13.5px] text-ink-3">
                            @if ($lead->category)<span class="font-semibold uppercase tracking-[0.12em] text-sindoor">{{ $lead->category->tr('name') }}</span><span aria-hidden="true">·</span>@endif
                            <time datetime="{{ $lead->published_at?->toDateString() }}">{{ local_date($lead->published_at) }}</time>
                            <span aria-hidden="true">·</span><span>{{ trans_choice(':count min read|:count min read', $lead->readingMinutes(), ['count' => digits($lead->readingMinutes())]) }}</span>
                        </p>
                        <h2 class="mt-2 font-display text-[clamp(1.8rem,3.6vw,2.6rem)] leading-tight"><a href="{{ route('news.show', $lead) }}" class="after:absolute after:inset-0 hover:text-sindoor-deep">{{ $lead->tr('title') }}</a></h2>
                        <p class="mt-3 text-[17px] text-ink-2 leading-relaxed">{{ $lead->tr('summary') }}</p>
                        <span class="mt-5 inline-flex items-center gap-1.5 font-semibold text-sindoor"><span>{{ __('Read the story') }}</span><x-icon name="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1" /></span>
                    </div>
                </article>
            @endif

            @if ($rest->isNotEmpty())
                <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-12">
                    @foreach ($rest as $post)
                        @include('site.news.partials.card', ['post' => $post])
                    @endforeach
                </div>
            @endif

            <div class="mt-12">{{ $posts->links() }}</div>
        @endif
    </div>
    </div>
</x-layouts.site>
