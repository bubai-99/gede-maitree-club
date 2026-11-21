@props(['kind'])
@php
    use App\Enums\ReviewKind;
    use App\Support\Reviews;

    $kind = $kind instanceof ReviewKind ? $kind : ReviewKind::from($kind);
    $data = Reviews::for($kind);
    $nursery = $kind === ReviewKind::Nursery;
    // Full class names, so Tailwind finds them
    $c = $nursery
        ? ['text' => 'text-tulsi', 'bar' => 'bg-tulsi', 'panel' => 'bg-tulsi-wash/60', 'soft' => 'bg-tulsi-wash', 'active' => '[&.swiper-slide-thumb-active]:border-tulsi', 'dot' => '[&_.swiper-pagination-bullet-active]:bg-tulsi']
        : ['text' => 'text-turmeric', 'bar' => 'bg-turmeric', 'panel' => 'bg-turmeric-wash/50', 'soft' => 'bg-turmeric-wash', 'active' => '[&.swiper-slide-thumb-active]:border-turmeric', 'dot' => '[&_.swiper-pagination-bullet-active]:bg-turmeric'];
    $tags = $nursery
        ? [__('Healthy plants'), __('Care advice'), __('Fair prices'), __('Ready next morning')]
        : [__('On time'), __('Polite team'), __('Clean tables'), __('Clear bill'), __('Looked after elders')];
    $photo = $nursery ? 'nurseryTable' : 'catBuffet';
    $ask = $nursery ? __('Namaskar, I would like to leave a review of the club nursery:') : __('Namaskar, I would like to leave a review of Maitree Serving:');
    $items = $data['items']->map(fn ($r) => [
        'n' => $r->rating,
        'text' => $r->tr('body'),
        'who' => $r->tr('who'),
        'what' => $r->tr('what'),
        'when' => $r->reviewed_on ? local_date($r->reviewed_on, 'M Y') : '',
    ])->values();
    $stars = fn (float $n) => collect([1, 2, 3, 4, 5])->map(fn ($i) => $i <= round($n));
@endphp
@if ($data['count'] > 0)
    {{-- A full-width band (edge to edge from inside a page container too), its content in line with the page --}}
    <section {{ $attributes->merge(['class' => 'mx-[calc(50%-50vw)] '.$c['panel']]) }} aria-labelledby="reviews-{{ $kind->value }}" data-reviews>
        <div class="mx-auto max-w-[1160px] px-5 py-12 sm:py-16">
            <div class="grid lg:grid-cols-12 gap-8 lg:gap-10">
                {{-- Summary --}}
                <div class="lg:col-span-4">
                    <p class="eyebrow {{ $c['text'] }}">{{ __('Reviews') }}</p>
                    <h2 id="reviews-{{ $kind->value }}" class="mt-2 font-display leading-tight text-[clamp(1.9rem,4vw,2.5rem)]">{{ $nursery ? __('What gardeners say') : __('What families say') }}</h2>
                    <div class="mt-6 flex items-end gap-4">
                        <span class="font-display text-[4.25rem] leading-[.85] num">{{ digits(number_format($data['average'], 1)) }}</span>
                        <span class="pb-1">
                            <span class="inline-flex gap-0.5" role="img" aria-label="{{ __(':n out of 5 stars', ['n' => digits($data['average'])]) }}">
                                @foreach ($stars($data['average']) as $on)<x-icon name="star" class="w-5 h-5 {{ $on ? 'text-marigold fill-current' : 'text-ink/20' }}" />@endforeach
                            </span>
                            <span class="block mt-1 text-[14px] text-ink-2">{{ trans_choice('from :count review|from :count reviews', $data['count'], ['count' => digits($data['count'])]) }}</span>
                        </span>
                    </div>
                    <ul class="mt-6 space-y-1.5" aria-label="{{ __('Ratings breakdown') }}">
                        @foreach ($data['spread'] as $n => $count)
                            <li class="flex items-center gap-2.5 text-[13.5px]">
                                <span class="w-3 text-right num">{{ digits($n) }}</span><x-icon name="star" class="w-3.5 h-3.5 text-marigold fill-current" />
                                <span class="flex-1 h-2 rounded-full bg-white/80 overflow-hidden"><span class="block h-full rounded-full {{ $c['bar'] }}" style="width: {{ $data['count'] ? round($count / $data['count'] * 100) : 0 }}%"></span></span>
                                <span class="w-6 text-right text-ink-3 num">{{ digits($count) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-6 text-[13px] font-semibold text-ink-2">{{ __('What people mention') }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($tags as $tag)<span class="chip h-8 px-3 bg-white text-ink"><x-icon name="check" class="{{ $c['text'] }}" />{{ $tag }}</span>@endforeach
                    </div>
                    <a href="https://wa.me/{{ app(\App\Support\SiteSettings::class)->club()['whatsapp'] }}?text={{ rawurlencode($ask) }}" target="_blank" rel="noopener" class="btn btn-line bg-white mt-7"><x-icon name="message-square-plus" />{{ __('Leave a review') }}</a>
                </div>

                {{-- The review in focus (a Swiper slider) and the others to pick from (a linked Swiper row);
                     resources/js/reviews.js --}}
                <div class="lg:col-span-8 min-w-0">
                    <figure class="relative bg-card rounded-[22px] shadow-[0_1px_2px_rgba(16,24,40,.05),0_18px_40px_-22px_rgba(16,24,40,.35)] overflow-hidden grid sm:grid-cols-[minmax(0,1fr)_200px]">
                        <div class="min-w-0 flex flex-col">
                            <div class="swiper w-full" data-reviews-main aria-label="{{ __('Reviews') }}">
                                <div class="swiper-wrapper">
                                    @foreach ($items as $i => $r)
                                        <div class="swiper-slide !h-auto p-6 sm:p-9 pb-3 sm:pb-4 flex flex-col">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex gap-0.5" role="img" aria-label="{{ __(':n out of 5 stars', ['n' => digits($r['n'])]) }}">
                                                    @foreach ([1, 2, 3, 4, 5] as $star)<x-icon name="star" class="w-4 h-4 fill-current {{ $r['n'] >= $star ? 'text-marigold' : 'text-ink/20' }}" />@endforeach
                                                </span>
                                                <span class="text-[13px] text-ink-3 num">{{ $r['when'] }}</span>
                                            </div>
                                            <svg viewBox="0 0 48 36" class="mt-5 w-10 h-8 {{ $c['text'] }}" aria-hidden="true"><path fill="currentColor" d="M0 36V22C0 9.6 6.4 2.3 19.2 0l2 5.3C14.6 7 11.2 11 10.9 17H20v19H0zm28 0V22C28 9.6 34.4 2.3 47.2 0l.8 5.3C41.4 7 38 11 37.7 17H48v19H28z"/></svg>
                                            <blockquote class="mt-4 font-display text-[1.22rem] sm:text-[1.5rem] leading-snug text-ink flex-1">{{ $r['text'] }}</blockquote>
                                            <p class="mt-7 flex items-center gap-3">
                                                <span class="w-11 h-11 rounded-full {{ $c['soft'] }} {{ $c['text'] }} grid place-items-center shrink-0"><x-icon :name="$nursery ? 'sprout' : 'utensils'" class="w-5 h-5" /></span>
                                                <span class="min-w-0"><span class="block font-semibold leading-snug">{{ $r['who'] }}</span>@if ($r['what'])<span class="block text-[13.5px] text-ink-3">{{ $r['what'] }}</span>@endif</span>
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if ($data['count'] > 1)
                                <div class="flex items-center gap-3 px-6 sm:px-9 pb-6 sm:pb-8 pt-2">
                                    <div class="reviews-dots flex gap-1.5 {{ $c['text'] }}" data-reviews-dots></div>
                                    <span class="ml-auto flex gap-1.5">
                                        <button type="button" data-reviews-prev class="w-10 h-10 rounded-full border border-rule grid place-items-center hover:bg-paper-2 disabled:opacity-40" aria-label="{{ __('Previous review') }}"><x-icon name="arrow-left" class="w-4 h-4" /></button>
                                        <button type="button" data-reviews-next class="w-10 h-10 rounded-full border border-rule grid place-items-center hover:bg-paper-2 disabled:opacity-40" aria-label="{{ __('Next review') }}"><x-icon name="arrow-right" class="w-4 h-4" /></button>
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="hidden sm:block relative bg-paper-2"><x-photo :key="$photo" :width="500" sizes="200px" class="absolute inset-0 w-full h-full object-cover" /></div>
                    </figure>

                    @if ($data['count'] > 1)
                        <div class="swiper reviews-thumbs mt-4" data-reviews-thumbs aria-label="{{ __('More reviews') }}">
                            <div class="swiper-wrapper">
                                @foreach ($items as $i => $r)
                                    <div class="swiper-slide !w-[230px] sm:!w-[220px] !h-auto cursor-pointer text-left rounded-2xl bg-white/70 hover:bg-white border-2 border-transparent p-4 transition-colors [&.swiper-slide-thumb-active]:bg-white [&.swiper-slide-thumb-active]:shadow-sm {{ $c['active'] }}">
                                        <span class="flex gap-0.5" aria-hidden="true">
                                            @foreach ([1, 2, 3, 4, 5] as $star)<x-icon name="star" class="w-3.5 h-3.5 fill-current {{ $r['n'] >= $star ? 'text-marigold' : 'text-ink/20' }}" />@endforeach
                                        </span>
                                        <span class="mt-2 text-[14px] leading-snug text-ink-2 line-clamp-3">{{ $r['text'] }}</span>
                                        <span class="mt-2 block text-[13px] font-semibold truncate">{{ $r['who'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
