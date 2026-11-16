<x-layouts.site seo="events" :title="app(\App\Support\PageContent::class)->text('events', 'title')">
    <x-page-banner page="events">
        <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-[15px] text-white/85">
            <li class="flex items-center gap-2"><x-icon name="calendar-days" class="w-4 h-4 text-marigold" /><span>{{ trans_choice(':count event coming up|:count events coming up', $upcoming->count(), ['count' => digits($upcoming->count())]) }}</span></li>
            <li class="flex items-center gap-2"><x-icon name="ticket" class="w-4 h-4 text-marigold" /><span>{{ __('Passes in the member app') }}</span></li>
            <li class="flex items-center gap-2"><x-icon name="hand-heart" class="w-4 h-4 text-marigold" /><span>{{ __('Volunteers always wanted') }}</span></li>
        </ul>
    </x-page-banner>

    <div class="mx-auto max-w-[1160px] px-5 py-14">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Coming up') }}</h2>
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Filter events') }}">
                <a href="{{ route('events.index') }}" class="chip border border-rule hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white" @if (! $kind) aria-current="page" @endif>{{ __('All') }}</a>
                @foreach (\App\Enums\EventKind::cases() as $option)
                    <a href="{{ route('events.index', ['kind' => $option->value]) }}" class="chip border border-rule hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white" @if ($kind === $option) aria-current="page" @endif>{{ $option->label() }}</a>
                @endforeach
            </nav>
        </div>
        <ol class="mt-6 border-t border-ink/80">
            @forelse ($upcoming as $event)
                @include('site.partials.event-row', ['event' => $event])
            @empty
                <li class="py-8 text-ink-2">{{ __('No events are planned yet. Check back soon.') }}</li>
            @endforelse
        </ol>

        @if ($past->isNotEmpty())
            <h2 class="mt-16 gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Recently') }}</h2>
            <ul class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($past as $event)
                    <li class="card p-5">
                        <p class="text-[13px] text-ink-3 num">{{ local_date($event->starts_at) }}</p>
                        <a href="{{ route('events.show', $event) }}" class="mt-1 block font-display text-[1.3rem] leading-snug hover:text-sindoor">{{ $event->tr('title') }}</a>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-16 grid md:grid-cols-2 gap-5">
            <div class="card p-6">
                <x-icon name="hand-heart" class="w-7 h-7 text-sindoor" />
                <h2 class="mt-3 font-display text-[1.6rem]">{{ __('Lend a hand') }}</h2>
                <p class="mt-2 text-ink-2">{{ __('Pandal, bhog, the gate, the sports ground: every event runs on volunteers. Even one evening helps.') }}</p>
                <a href="{{ route('volunteer') }}" class="mt-4 inline-flex items-center gap-1.5 font-semibold text-sindoor hover:underline"><span>{{ __('See where help is needed') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            <div class="card p-6">
                <x-icon name="bell-ring" class="w-7 h-7 text-sindoor" />
                <h2 class="mt-3 font-display text-[1.6rem]">{{ __('Never miss one') }}</h2>
                <p class="mt-2 text-ink-2">{{ __('Members get every notice in the app. Everyone else can get the news on WhatsApp.') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="https://wa.me/{{ $site->club()['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-icon name="message-circle" /><span>{{ __('News on WhatsApp') }}</span></a>
                    <a href="{{ route('notices.index') }}" class="btn btn-ghost btn-sm">{{ __('Notice board') }}</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.site>
