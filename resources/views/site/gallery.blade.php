@php
    $content = app(\App\Support\PageContent::class);
    $club = $site->club();
    $clubName = app()->getLocale() === 'bn' ? $club['name_bn'] : $club['name'];
    $state = ['album' => $album, 'type' => $type, 'year' => $year];
    $link = fn (array $over = []) => route('gallery', array_filter(array_merge($state, $over), fn ($v) => $v !== '' && $v !== null));
    $albumName = function (string $key) use ($albums): string {
        $a = collect($albums)->firstWhere('key', $key);

        return $a ? local_text($a['title'], $a['title_bn'] ?? null) : '';
    };
    $when = fn (array $day): string => local_date(\Illuminate\Support\Carbon::parse($day['date']), 'j M Y');
    $title = fn (array $day): string => local_text($day['title'], $day['title_bn'] ?? null);
    $isVideo = fn (array $item): bool => ($item['type'] ?? 'photo') === 'video';
    $chip = 'swiper-slide !w-auto shrink-0 h-8 px-3 rounded-lg text-[14px] font-medium whitespace-nowrap bg-paper-2 text-ink hover:bg-ink/10 aria-[current=page]:bg-ink aria-[current=page]:text-white inline-flex items-center';
    $items = collect($days)->flatMap(fn ($day) => collect($day['items'])->map(fn ($item, $i) => ['item' => $item, 'day' => $day, 'i' => $i]));
    $photos = $items->filter(fn ($row) => ! $isVideo($row['item']));
    $videos = $items->filter(fn ($row) => $isVideo($row['item']));
    $grid = 'grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-9';
    $whatsappText = rawurlencode(__('I have photos or videos for the club gallery'));
@endphp
<x-layouts.site seo="gallery" :title="$content->text('gallery', 'title')">
    {{-- Filters and pages swap in place (resources/js/instant.js) --}}
    <div id="gallery-page" data-instant class="instant-region">
    @unless ($open)
        <x-page-banner page="gallery">
            <div class="mt-7"><a href="https://wa.me/{{ $club['whatsapp'] }}?text={{ $whatsappText }}" target="_blank" rel="noopener" class="btn btn-glass h-12 px-5"><x-icon name="image-plus" /><span>{{ __('Send photos or videos') }}</span></a></div>
        </x-page-banner>
    @endunless

    <div class="mx-auto max-w-[1160px] px-5 {{ $open ? 'pt-6' : 'pt-10' }} pb-24">
        @if ($open)
            {{-- Album as a watch page: the item large on the left, the album as a playlist on the right --}}
            @php
                $item = $open['items'][$index];
                $count = count($open['items']);
            @endphp
            <a href="{{ $link() }}" class="inline-flex items-center gap-1.5 text-[15px] font-semibold text-ink-2 hover:text-ink" data-instant-top><x-icon name="arrow-left" class="w-4 h-4" />{{ __('All albums') }}</a>
            <div class="mt-4 grid lg:grid-cols-[minmax(0,1fr)_360px] gap-6 lg:gap-7 items-start">
                <div class="min-w-0">
                    <div class="relative aspect-video bg-black rounded-xl overflow-hidden group/player">
                        @if ($isVideo($item))
                            <x-photo :key="$item['photo']" :width="1600" class="absolute inset-0 w-full h-full object-cover opacity-75" />
                            <div class="absolute inset-0 grid place-items-center p-6 text-center bg-black/40"><div class="max-w-md text-white">
                                <x-icon name="clapperboard" class="w-10 h-10 mx-auto text-marigold" />
                                <p class="mt-3 font-semibold text-lg">{{ __('The club’s video plays here') }}</p>
                                <p class="mt-1 text-white/80 text-[15px]">{{ __('The video file is not online yet. Ask at the club room to see it.') }}</p>
                            </div></div>
                        @else
                            <a href="{{ \App\Support\Photo::url($item['photo'], 1800) }}" data-lightbox="album-photos" data-order="{{ $index }}" data-thumb="{{ \App\Support\Photo::url($item['photo'], 300) }}" data-caption="{{ $title($open) }} · {{ $when($open) }}" class="absolute inset-0 cursor-zoom-in" aria-label="{{ __('See full screen') }}">
                                <x-photo :key="$item['photo']" :width="1600" class="absolute inset-0 w-full h-full object-contain" />
                            </a>
                            <span class="pointer-events-none absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-md bg-black/60 text-white text-[12.5px] font-semibold px-2 py-1"><x-icon name="maximize-2" class="w-3.5 h-3.5" />{{ __('Full screen') }}</span>
                            {{-- The album's other photos, for moving through it full screen --}}
                            <div hidden>
                                @foreach ($open['items'] as $j => $x)
                                    @if ($j !== $index && ! $isVideo($x))
                                        <a href="{{ \App\Support\Photo::url($x['photo'], 1800) }}" data-lightbox="album-photos" data-order="{{ $j }}" data-thumb="{{ \App\Support\Photo::url($x['photo'], 300) }}" data-caption="{{ $title($open) }} · {{ $when($open) }}" tabindex="-1"></a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <span class="absolute left-3 top-3 rounded-md bg-black/60 text-white text-[12.5px] font-semibold px-2 py-0.5 num">{{ digits(($index + 1).' / '.$count) }}</span>
                        @if ($count > 1)
                            <a href="{{ $link(['open' => $open['id'], 'i' => ($index - 1 + $count) % $count]) }}" class="w-11 h-11 grid place-items-center rounded-full bg-black/50 text-white hover:bg-black/70 backdrop-blur-sm transition absolute left-3 top-1/2 -translate-y-1/2" aria-label="{{ __('Previous') }}"><x-icon name="chevron-left" /></a>
                            <a href="{{ $link(['open' => $open['id'], 'i' => ($index + 1) % $count]) }}" class="w-11 h-11 grid place-items-center rounded-full bg-black/50 text-white hover:bg-black/70 backdrop-blur-sm transition absolute right-3 top-1/2 -translate-y-1/2" aria-label="{{ __('Next') }}"><x-icon name="chevron-right" /></a>
                        @endif
                    </div>
                    <h1 class="mt-4 text-[1.3rem] sm:text-[1.5rem] font-bold leading-snug">{{ $isVideo($item) ? local_text($item['caption'] ?? '', $item['caption_bn'] ?? null) : $title($open) }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-white border border-rule grid place-items-center shrink-0"><x-seal :size="36" :rotate="-6" aria-hidden="true" /></span>
                        <div class="min-w-0 mr-auto"><p class="font-semibold leading-tight">{{ $clubName }}</p><p class="text-[13px] text-ink-3">{{ __('Gede Uttarpara, since 2014') }}</p></div>
                        <div class="flex gap-2">
                            <a href="https://wa.me/?text={{ rawurlencode($title($open).' · '.$link(['open' => $open['id']])) }}" target="_blank" rel="noopener" class="btn btn-line btn-sm rounded-full"><x-icon name="share-2" />{{ __('Share') }}</a>
                            <a href="https://wa.me/{{ $club['whatsapp'] }}?text={{ rawurlencode(__('I have photos for the album: :album', ['album' => $open['title']])) }}" target="_blank" rel="noopener" class="btn btn-ink btn-sm rounded-full"><x-icon name="image-plus" />{{ __('Add yours') }}</a>
                        </div>
                    </div>
                    <div class="mt-4 rounded-xl bg-paper-2 p-4 text-[14.5px]">
                        <p class="font-semibold num">{{ $when($open) }} · {{ $albumName($open['album']) }} · {{ trans_choice(':count item|:count items', $count, ['count' => digits($count)]) }}</p>
                        <p class="mt-3 text-[13px] text-ink-3">{{ $isVideo($item) ? __('Cover photo') : __('Photo') }}: <x-photo-credit :key="$item['photo']" /> · {{ __('Sample photos, to be replaced with the club’s own.') }}</p>
                    </div>
                </div>
                <aside class="space-y-5" aria-label="{{ __('In this album') }}">
                    <div class="rounded-xl border border-rule bg-card overflow-hidden">
                        <div class="px-4 py-3 bg-paper-2">
                            <p class="font-bold leading-snug">{{ $title($open) }}</p>
                            <p class="text-[13px] text-ink-3 mt-0.5"><span>{{ $clubName }}</span> · <span class="num">{{ digits(($index + 1).' / '.$count) }}</span></p>
                        </div>
                        <ol class="max-h-[420px] overflow-y-auto py-1">
                            @foreach ($open['items'] as $j => $x)
                                <li><a href="{{ $link(['open' => $open['id'], 'i' => $j]) }}" @if ($j === $index) aria-current="true" @endif class="w-full flex items-center gap-2 pl-2 pr-3 py-1.5 text-left hover:bg-paper-2 aria-[current=true]:bg-sindoor-wash">
                                    <span class="w-5 shrink-0 text-center text-[12px] text-ink-3 num">@if ($j === $index)<x-icon name="play" class="w-3 h-3 mx-auto fill-current text-sindoor" />@else{{ digits($j + 1) }}@endif</span>
                                    <span class="relative w-[112px] shrink-0 aspect-video rounded-md overflow-hidden bg-paper-2"><x-photo :key="$x['photo']" :width="300" sizes="112px" class="absolute inset-0 w-full h-full object-cover" />@if ($isVideo($x))<span class="absolute right-1 bottom-1 rounded bg-black/80 text-white text-[11px] font-semibold px-1 num">{{ digits($x['duration'] ?? '') }}</span>@endif</span>
                                    <span class="min-w-0"><span class="block text-[14px] font-semibold leading-snug line-clamp-2">{{ $isVideo($x) ? local_text($x['caption'] ?? '', $x['caption_bn'] ?? null) : $title($open) }}</span><span class="block text-[12.5px] text-ink-3 mt-0.5">{{ $isVideo($x) ? __('Video') : __('Photo') }}</span></span>
                                </a></li>
                            @endforeach
                        </ol>
                    </div>
                    @if ($others)
                        <div><h2 class="font-bold mb-3">{{ __('More albums') }}</h2><div class="space-y-3">
                            @foreach ($others as $m)
                                <a href="{{ $link(['open' => $m['id']]) }}" class="group flex gap-2.5">
                                    <span class="relative w-[168px] shrink-0 aspect-video rounded-lg overflow-hidden bg-paper-2"><x-photo :key="$m['items'][0]['photo'] ?? ''" :width="400" sizes="168px" class="absolute inset-0 w-full h-full object-cover" /><span class="absolute right-1 bottom-1 rounded bg-black/80 text-white text-[11px] font-semibold px-1 flex items-center gap-0.5"><x-icon name="list-video" class="w-3 h-3" /><span class="num">{{ digits(count($m['items'])) }}</span></span></span>
                                    <span class="min-w-0"><span class="block text-[14px] font-semibold leading-snug line-clamp-2 group-hover:text-sindoor">{{ $title($m) }}</span><span class="block text-[12.5px] text-ink-3 mt-1">{{ $clubName }}</span><span class="block text-[12.5px] text-ink-3 num">{{ $when($m) }}</span></span>
                                </a>
                            @endforeach
                        </div></div>
                    @endif
                </aside>
            </div>
        @else
            {{-- Filters: full width and pinned under the header like the nursery's; a shadow while pinned --}}
            <div class="sticky top-[65px] z-20 mx-[calc(50%-50vw)] py-3 bg-paper/95 backdrop-blur-sm transition-shadow duration-200" data-instant-top
                 x-data="{ stuck: false }" x-init="stuck = $el.getBoundingClientRect().top <= 66" @scroll.window.passive="stuck = $el.getBoundingClientRect().top <= 66"
                 :class="stuck && 'shadow-[0_8px_18px_-14px_rgba(43,47,54,.45)]'">
                <div class="mx-auto max-w-[1160px] px-5">
                <div class="flex items-center gap-2">
                    <div class="carousel flex-1 min-w-0" data-bleed="left"><div class="swiper" data-carousel data-gap="8"><nav class="swiper-wrapper" aria-label="{{ __('Show') }}">
                        <a href="{{ $link(['type' => '']) }}" class="{{ $chip }}" @if ($type === '') aria-current="page" @endif>{{ __('All') }}</a>
                        <a href="{{ $link(['type' => 'video']) }}" class="{{ $chip }}" @if ($type === 'video') aria-current="page" @endif>{{ __('Videos') }}</a>
                        <a href="{{ $link(['type' => 'photo']) }}" class="{{ $chip }}" @if ($type === 'photo') aria-current="page" @endif>{{ __('Photos') }}</a>
                        <span class="swiper-slide !w-px !h-6 self-center shrink-0 bg-rule" aria-hidden="true"></span>
                        <a href="{{ $link(['album' => '']) }}" class="{{ $chip }}" @if ($album === '') aria-current="page" @endif>{{ __('Every album') }}</a>
                        @foreach ($albums as $option)
                            <a href="{{ $link(['album' => $option['key']]) }}" class="{{ $chip }}" @if ($album === $option['key']) aria-current="page" @endif>{{ local_text($option['title'], $option['title_bn'] ?? null) }}</a>
                        @endforeach
                    </nav></div></div>
                    <form method="GET" action="{{ route('gallery') }}" class="shrink-0">
                        @foreach (['album' => $album, 'type' => $type] as $k => $v)
                            @if ($v !== '')<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                        @endforeach
                        {{-- Phones: a calendar button that opens the list of years (a dot when one is chosen); wider: the menu --}}
                        <label class="relative block">
                            <span class="sr-only">{{ __('Year') }}</span>
                            <span class="sm:hidden pointer-events-none absolute inset-0 grid place-items-center text-ink" aria-hidden="true"><x-icon name="calendar-days" class="w-[18px] h-[18px]" />@if ($year)<span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-sindoor"></span>@endif</span>
                            <select name="year" class="field h-8 sm:pr-8 text-[14px] py-0 w-10 sm:w-auto max-sm:px-0 max-sm:appearance-none max-sm:text-transparent max-sm:cursor-pointer" onchange="this.form.requestSubmit()">
                                <option value="">{{ __('All years') }}</option>
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" @selected($year === $y)>{{ digits($y) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <noscript><button class="btn btn-line btn-sm">{{ __('Show') }}</button></noscript>
                    </form>
                </div>
            </div>
            </div>
            <div class="mt-5 flex items-center gap-3">
                <span class="w-12 h-12 rounded-full bg-white border border-rule grid place-items-center shrink-0"><x-seal :size="44" :rotate="-6" aria-hidden="true" /></span>
                <div class="min-w-0"><p class="font-bold text-[17px] leading-tight">{{ $clubName }}</p>
                    <p class="text-[13.5px] text-ink-3">{{ trans_choice(':count album|:count albums', count($days), ['count' => digits(count($days))]) }} · {{ trans_choice(':count photo|:count photos', $photos->count(), ['count' => digits($photos->count())]) }} · {{ trans_choice(':count video|:count videos', $videos->count(), ['count' => digits($videos->count())]) }}</p></div>
            </div>

            @if ($days === [])
                <div class="py-20 text-center"><x-icon name="image-off" class="w-8 h-8 mx-auto text-ink-3" />
                    <p class="mt-3 font-semibold">{{ __('Nothing here for this choice') }}</p>
                    <p class="mt-1 text-ink-2">{{ __('Try another album or year.') }}</p>
                    <a href="{{ route('gallery') }}" class="btn btn-line btn-sm mt-4">{{ __('Show everything') }}</a></div>
            @elseif ($type === 'photo')
                <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-3">
                    @foreach ($photos as $row)
                        <a href="{{ $link(['open' => $row['day']['id'], 'i' => $row['i']]) }}" data-lightbox="gallery-photos" data-src="{{ \App\Support\Photo::url($row['item']['photo'], 1800) }}" data-caption="{{ $title($row['day']) }} · {{ $when($row['day']) }}" class="group relative block aspect-square rounded-xl overflow-hidden bg-paper-2"><x-photo :key="$row['item']['photo']" :width="500" sizes="(min-width: 1024px) 25vw, 50vw" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03]" /></a>
                    @endforeach
                </div>
            @else
                @php
                    $videoCards = $videos->map(fn ($row) => ['href' => $link(['open' => $row['day']['id'], 'i' => $row['i']]), 'row' => $row]);
                @endphp
                @if ($type === 'video')
                    @if ($videoCards->isEmpty())
                        <p class="py-16 text-center text-ink-2">{{ __('No videos in this choice yet.') }}</p>
                    @else
                        <div class="mt-6 {{ $grid }}">
                            @foreach ($videoCards as $card)
                                @include('site.partials.gallery-video', $card)
                            @endforeach
                        </div>
                    @endif
                @else
                    @if ($videoCards->isNotEmpty())
                        <section class="mt-8" aria-label="{{ __('Videos') }}">
                            <div class="flex items-center gap-2 mb-4"><x-icon name="clapperboard" class="w-5 h-5 text-sindoor" /><h2 class="text-[1.25rem] font-bold">{{ __('Videos') }}</h2>
                                <a href="{{ $link(['type' => 'video']) }}" class="ml-auto text-[14px] font-semibold text-sindoor hover:underline">{{ __('See all') }}</a>
                                {{-- ‹ › for the row below, on computers (resources/js/carousel.js puts them here) --}}
                                <span class="carousel-nav ml-2" id="videos-nav"></span></div>
                            <div class="carousel" data-bleed="both" data-nav="videos-nav"><div class="swiper" data-carousel data-cards data-gap="16"><div class="swiper-wrapper pb-1">
                                @foreach ($videoCards as $card)
                                    <div class="swiper-slide">@include('site.partials.gallery-video', $card)</div>
                                @endforeach
                            </div></div></div>
                        </section>
                    @endif
                    @foreach (collect($days)->groupBy(fn ($day) => substr((string) $day['date'], 0, 4)) as $y => $group)
                        <section class="mt-12" aria-label="{{ digits($y) }}">
                            <div class="flex items-baseline gap-3 mb-4 pt-6 border-t border-rule"><h2 class="text-[1.25rem] font-bold num">{{ digits($y) }}</h2><span class="text-[14px] text-ink-3">{{ trans_choice(':count album|:count albums', $group->count(), ['count' => digits($group->count())]) }}</span></div>
                            <div class="{{ $grid }}">
                                @foreach ($group as $day)
                                    <a href="{{ $link(['open' => $day['id']]) }}" class="group block" aria-label="{{ $title($day) }}">
                                        <div class="mx-4 h-1 rounded-t-md bg-ink/15"></div><div class="mx-2 h-1 rounded-t-md bg-ink/30"></div>
                                        <div class="relative aspect-video rounded-xl overflow-hidden bg-paper-2">
                                            @if (isset($day['items'][0]))
                                                <x-photo :key="$day['items'][0]['photo']" :width="800" sizes="(min-width: 1280px) 270px, (min-width: 640px) 50vw, 100vw" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03]" />
                                            @endif
                                            <span class="absolute right-1.5 bottom-1.5 rounded-md bg-black/80 text-white text-[12px] font-semibold px-1.5 py-0.5 flex items-center gap-1"><x-icon name="list-video" class="w-3.5 h-3.5" /><span class="num">{{ digits(count($day['items'])) }}</span></span>
                                            <span class="absolute inset-0 bg-black/55 opacity-0 group-hover:opacity-100 transition-opacity grid place-items-center text-white text-[14px] font-semibold"><span class="flex items-center gap-1.5"><x-icon name="play" class="w-4 h-4 fill-current" />{{ __('Open album') }}</span></span>
                                        </div>
                                        <div class="mt-3 flex gap-3">
                                            <span class="w-9 h-9 rounded-full bg-white border border-rule grid place-items-center shrink-0"><x-seal :size="32" :rotate="-6" aria-hidden="true" /></span>
                                            <span class="min-w-0"><span class="block text-[15px] font-semibold leading-snug line-clamp-2 group-hover:text-sindoor">{{ $title($day) }}</span><span class="block text-[13px] text-ink-3 mt-0.5">{{ $clubName }}</span><span class="block text-[13px] text-ink-3 num">{{ $albumName($day['album']) }} · {{ $when($day) }}</span></span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                @endif
            @endif
        @endif

        <p class="mt-16 pt-6 border-t border-rule text-sm text-ink-3">{{ __('In a photo and want it removed? Tell the club and we’ll take it down.') }}</p>

        @unless ($open)
            {{-- Send a link to an album: kept from the website forms --}}
            <div id="send-photos" class="mt-14 grid lg:grid-cols-12 gap-8 scroll-mt-24">
                <div class="lg:col-span-5">
                    <h2 class="font-display text-[clamp(1.9rem,4vw,2.6rem)] leading-tight">{{ __('Send photos or videos') }}</h2>
                    <p class="mt-3 text-[17px] text-ink-2">{{ __('Took photos at a puja or the sports day? Share a link to the album and the committee will add the best ones.') }}</p>
                    <a href="https://wa.me/{{ $club['whatsapp'] }}?text={{ $whatsappText }}" target="_blank" rel="noopener" class="mt-5 btn btn-line"><x-icon name="message-circle" /><span>{{ __('Or send them on WhatsApp') }}</span></a>
                </div>
                <form method="POST" action="{{ route('forms.photos') }}" class="lg:col-span-7 grid sm:grid-cols-2 gap-4">
                    @csrf
                    @include('site.partials.honeypot', ['form' => 'photos'])
                    <x-field name="name" :label="__('Your name')" autocomplete="name" required />
                    <x-field name="phone" type="tel" :label="__('Mobile')" autocomplete="tel" inputmode="tel" required />
                    <x-field class="sm:col-span-2" name="event" :label="__('Which event?')" required />
                    <x-field class="sm:col-span-2" name="link" type="url" :label="__('Link to the photos (Google Photos, Drive)')" placeholder="https://" />
                    <x-field class="sm:col-span-2" name="message" :label="__('Anything else')" textarea rows="3" />
                    <div class="sm:col-span-2"><button type="submit" class="btn btn-primary h-12 px-6">{{ __('Send') }}</button></div>
                </form>
            </div>
        @endunless
    </div>
    </div>
</x-layouts.site>
