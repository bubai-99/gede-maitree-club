<x-layouts.site seo="notices" :title="app(\App\Support\PageContent::class)->text('notices', 'title')">
    {{-- Filters and pages swap in place (resources/js/instant.js) --}}
    <div id="notices-page" data-instant class="instant-region">
    <x-page-banner page="notices">
        <div class="mt-7"><a href="https://wa.me/{{ $site->club()['whatsapp'] }}?text={{ rawurlencode(__('Please add me to the club news')) }}" target="_blank" rel="noopener" class="btn btn-glass h-12 px-5"><x-icon name="message-circle" /><span>{{ __('Get notices on WhatsApp') }}</span></a></div>
    </x-page-banner>

    <div class="mx-auto max-w-[1160px] px-5 pt-10 pb-20">
        @if ($tags->isNotEmpty())
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Filter notices') }}" data-instant-top>
                <a href="{{ route('notices.index') }}" class="chip-filter" @if ($tag === '') aria-current="page" @endif>{{ __('All') }} <span class="opacity-60 num">{{ digits($total) }}</span></a>
                @foreach ($tags as $option)
                    <a href="{{ route('notices.index', ['tag' => $option['tag']]) }}" class="chip-filter" @if ($tag === $option['tag']) aria-current="page" @endif>{{ $option['label'] }} <span class="opacity-60 num">{{ digits($option['count']) }}</span></a>
                @endforeach
            </nav>
        @endif
        <p class="mt-5 text-sm text-ink-3">{{ trans_choice(':count notice|:count notices', $notices->total(), ['count' => digits($notices->total())]) }}</p>
        <ol class="mt-3 border-t border-rule">
            @forelse ($notices as $notice)
                <li class="border-b border-rule">
                    <a href="{{ route('notices.show', $notice) }}" class="group grid grid-cols-[56px_1fr_auto] sm:grid-cols-[80px_1fr_auto] gap-x-4 sm:gap-x-6 py-5 items-start">
                        <span class="text-sindoor leading-none text-center">
                            <span class="block font-display text-[2.4rem] num">{{ local_date($notice->published_at, 'j') }}</span>
                            <span class="block text-[12.5px] font-semibold tracking-wide mt-1 uppercase">{{ local_date($notice->published_at, 'M') }}</span>
                        </span>
                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-2 text-[12.5px]">
                                @if ($notice->tag)<span class="font-bold uppercase tracking-wide text-sindoor-deep">{{ $notice->tr('tag') }}</span>@endif
                                @if ($notice->pinned)<span class="chip h-6 px-2 text-[12px] bg-marigold-wash text-ink"><x-icon name="pin" class="!w-3 !h-3" />{{ __('Pinned') }}</span>@endif
                            </span>
                            <span class="block mt-1 text-[19px] font-semibold leading-snug group-hover:underline decoration-1">{{ $notice->tr('title') }}</span>
                            <span class="block mt-1 text-[15px] text-ink-2 leading-snug">{{ $notice->tr('summary') }}</span>
                        </span>
                        <x-icon name="chevron-right" class="w-5 h-5 text-ink-3 mt-2 transition-transform group-hover:translate-x-0.5" />
                    </a>
                </li>
            @empty
                <li class="py-10 text-center text-ink-3">{{ __('No notices right now.') }}</li>
            @endforelse
        </ol>
        <div class="mt-8">{{ $notices->links() }}</div>
        <p class="mt-8 text-[15px] text-ink-2"><span>{{ __('Older notices, meeting minutes and polls are in the member app.') }}</span> <a href="{{ route('member.login') }}" class="font-semibold text-sindoor hover:underline">{{ __('Member login') }}</a></p>
    </div>
    </div>
</x-layouts.site>
