<x-layouts.site seo="events" :title="app(\App\Support\PageContent::class)->text('events', 'title')">
    <x-page-banner page="events">
        <ul class="mt-7 flex flex-wrap gap-x-6 gap-y-2 text-[15px] text-white/85">
            <li class="flex items-center gap-2"><x-icon name="calendar-days" class="w-4 h-4 text-marigold" /><span>{{ trans_choice(':count event coming up|:count events coming up', $upcoming->count(), ['count' => digits($upcoming->count())]) }}</span></li>
            <li class="flex items-center gap-2"><x-icon name="ticket" class="w-4 h-4 text-marigold" /><span>{{ __('Passes in the member app') }}</span></li>
            <li class="flex items-center gap-2"><x-icon name="hand-heart" class="w-4 h-4 text-marigold" /><span>{{ __('Volunteers always wanted') }}</span></li>
        </ul>
    </x-page-banner>

    <div class="mx-auto max-w-[1160px] px-5 pt-12 pb-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="font-display leading-tight text-[clamp(1.9rem,4vw,2.6rem)]">{{ __('Coming up') }}</h2>
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Filter events') }}">
                <a href="{{ route('events.index') }}" class="chip h-9 px-4 text-[15px] border border-rule bg-card hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white aria-[current=page]:border-ink" @if (! $kind) aria-current="page" @endif>{{ __('All') }}</a>
                @foreach (\App\Enums\EventKind::cases() as $option)
                    <a href="{{ route('events.index', ['kind' => $option->value]) }}" class="chip h-9 px-4 text-[15px] border border-rule bg-card hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white aria-[current=page]:border-ink" @if ($kind === $option) aria-current="page" @endif>{{ $option->label() }}</a>
                @endforeach
            </nav>
        </div>
        <ol class="mt-6 border-t border-ink/80">
            @forelse ($upcoming as $event)
                @include('site.partials.event-row', ['event' => $event, 'full' => true])
            @empty
                <li class="py-10 text-center text-ink-3">{{ __('No events are planned yet. Check back soon.') }}</li>
            @endforelse
        </ol>

        @if ($past->isNotEmpty())
            <h2 class="mt-16 font-display leading-tight text-[clamp(1.9rem,4vw,2.6rem)]">{{ __('Recently') }}</h2>
            <div class="mt-6 grid md:grid-cols-2 gap-6">
                @foreach ($past->take(2) as $event)
                    @php
                        $key = $event->samplePhotoKey();
                        $url = $key ? null : $event->photoUrl(1200);
                    @endphp
                    <article>
                        <a href="{{ route('events.show', $event) }}" class="frame aspect-[16/9] bg-ink flex items-end p-5 isolate">
                            @if ($key)
                                <x-photo :key="$key" :width="1200" sizes="(min-width: 768px) 560px, 100vw" class="absolute inset-0 w-full h-full object-cover -z-10" />
                            @elseif ($url)
                                <img src="{{ $url }}" alt="" class="absolute inset-0 w-full h-full object-cover -z-10" loading="lazy">
                            @endif
                            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>
                            @if ($key)
                                <span class="absolute top-3 right-3 text-[11.5px] text-white bg-black/50 px-1.5 rounded"><x-photo-credit :key="$key" /></span>
                            @endif
                            <p class="poster text-white text-4xl">{{ $event->tr('title') }}</p>
                        </a>
                        <div class="pt-4">
                            <p class="text-[13px] font-semibold text-ink-3 num">{{ local_date($event->starts_at, 'j M Y') }}</p>
                            @if ($event->tr('summary'))
                                <p class="mt-1 text-[17px]">{{ $event->tr('summary') }}</p>
                            @endif
                            <a href="{{ route('gallery') }}" class="mt-2 inline-flex items-center gap-1.5 text-[15px] font-semibold text-sindoor hover:underline"><x-icon name="image" class="w-4 h-4" />{{ __('See the photos') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="mt-20 grid md:grid-cols-2 gap-5">
            <div class="rounded-2xl bg-ink text-white p-7">
                <x-icon name="hand-heart" class="w-7 h-7 text-marigold" />
                <h2 class="mt-4 font-display text-[1.9rem] leading-tight">{{ __('Lend a hand') }}</h2>
                <p class="mt-2 text-white/80 max-w-[44ch]">{{ __('Pandal, bhog, the gate, the sports ground: every event runs on volunteers. Even one evening helps.') }}</p>
                <a href="{{ route('volunteer') }}" class="btn bg-white text-ink hover:bg-paper-2 mt-6"><span>{{ __('See where help is needed') }}</span><x-icon name="arrow-right" /></a>
            </div>
            <div class="card p-7">
                <x-icon name="bell-ring" class="w-7 h-7 text-sindoor" />
                <h2 class="mt-4 font-display text-[1.9rem] leading-tight">{{ __('Never miss one') }}</h2>
                <p class="mt-2 text-ink-2 max-w-[44ch]">{{ __('Members get every notice in the app. Everyone else can get the news on WhatsApp.') }}</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="https://wa.me/{{ $site->club()['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-primary"><x-icon name="message-circle" /><span>{{ __('News on WhatsApp') }}</span></a>
                    <a href="{{ route('notices.index') }}" class="btn btn-line"><span>{{ __('Notice board') }}</span></a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.site>
