@php($content = app(\App\Support\PageContent::class))
<x-layouts.site seo="gallery" :title="$content->text('gallery', 'title')">
    <x-page-banner page="gallery">
        <a href="#send-photos" class="mt-6 btn btn-glass h-11"><x-icon name="upload" /><span>{{ __('Send photos or videos') }}</span></a>
    </x-page-banner>

    <div class="mx-auto max-w-[1160px] px-5 py-14">
        <nav class="flex flex-wrap gap-2" aria-label="{{ __('Albums') }}">
            <a href="{{ route('gallery') }}" class="chip border border-rule hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white" @if ($album === '') aria-current="page" @endif>{{ __('All') }}</a>
            @foreach ($albums as $option)
                <a href="{{ route('gallery', ['album' => $option['key']]) }}" class="chip border border-rule hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white" @if ($album === $option['key']) aria-current="page" @endif>{{ local_text($option['title'], $option['title_bn'] ?? null) }}</a>
            @endforeach
        </nav>

        @forelse ($days as $day)
            <section class="mt-12" aria-labelledby="day-{{ $day['id'] }}">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-ink/80 pb-2">
                    <h2 id="day-{{ $day['id'] }}" class="font-display text-[1.6rem]">{{ local_text($day['title'], $day['title_bn'] ?? null) }}</h2>
                    <p class="text-[14px] text-ink-3 num">{{ local_date(\Illuminate\Support\Carbon::parse($day['date'])) }}</p>
                </div>
                <ul class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach ($day['items'] as $item)
                        <li>
                            <figure>
                                <div class="relative aspect-square rounded-[12px] overflow-hidden bg-paper-3">
                                    <x-photo :key="$item['photo']" :width="640" sizes="(min-width: 1024px) 25vw, 50vw" />
                                    @if ($item['type'] === 'video')
                                        <span class="absolute inset-0 grid place-items-center bg-black/25" aria-hidden="true"><span class="w-12 h-12 rounded-full bg-white/90 text-ink grid place-items-center"><x-icon name="play" class="w-5 h-5" /></span></span>
                                        <span class="absolute bottom-2 right-2 chip h-6 bg-black/70 text-white num">{{ digits($item['duration'] ?? '') }}</span>
                                    @endif
                                </div>
                                <figcaption class="mt-1.5 text-[12.5px] text-ink-3">
                                    @if ($item['type'] === 'video')
                                        <span class="block text-[14px] text-ink-2">{{ __('Video') }}: {{ local_text($item['caption'] ?? '', $item['caption_bn'] ?? null) }}</span>
                                    @endif
                                    <x-photo-credit :key="$item['photo']" />
                                </figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="mt-10 text-ink-2">{{ __('No photos in this album yet.') }}</p>
        @endforelse

        <p class="mt-10 text-[14px] text-ink-3">{{ __('In a photo and want it removed? Tell the club and we’ll take it down.') }}</p>

        <div id="send-photos" class="mt-14 grid lg:grid-cols-12 gap-8 scroll-mt-24">
            <div class="lg:col-span-5">
                <h2 class="font-display text-[2rem] leading-tight">{{ __('Send photos or videos') }}</h2>
                <p class="mt-3 text-ink-2">{{ __('Took photos at a puja or the sports day? Share a link to the album and the committee will add the best ones.') }}</p>
                <a href="https://wa.me/{{ $site->club()['whatsapp'] }}" target="_blank" rel="noopener" class="mt-5 btn btn-line"><x-icon name="message-circle" /><span>{{ __('Or send them on WhatsApp') }}</span></a>
            </div>
            <form method="POST" action="{{ route('forms.photos') }}" class="lg:col-span-7 card p-6 grid sm:grid-cols-2 gap-4">
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
    </div>
</x-layouts.site>
