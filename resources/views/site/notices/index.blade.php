<x-layouts.site seo="notices" :title="app(\App\Support\PageContent::class)->text('notices', 'title')">
    <x-page-banner page="notices" />

    <div class="mx-auto max-w-[900px] px-5 py-14">
        <ul class="border-t border-ink/80">
            @forelse ($notices as $notice)
                <li class="border-b border-rule py-6">
                    <p class="flex flex-wrap items-center gap-2 text-[13px] text-ink-3 num">
                        <span>{{ local_date($notice->published_at) }}</span>
                        @if ($notice->tag)
                            <span class="chip h-6 bg-paper-2 text-ink-2">{{ $notice->tr('tag') }}</span>
                        @endif
                        @if ($notice->pinned)
                            <span class="chip h-6 bg-sindoor-wash text-sindoor-deep"><x-icon name="pin" />{{ __('Pinned') }}</span>
                        @endif
                    </p>
                    <h2 class="mt-2 font-display text-[1.6rem] leading-snug"><a href="{{ route('notices.show', $notice) }}" class="hover:text-sindoor">{{ $notice->tr('title') }}</a></h2>
                    <p class="mt-2 text-ink-2 max-w-[62ch]">{{ $notice->tr('summary') }}</p>
                    <a href="{{ route('notices.show', $notice) }}" class="mt-3 inline-flex items-center gap-1.5 text-[15px] font-semibold text-sindoor hover:underline"><span>{{ __('Read more') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
                </li>
            @empty
                <li class="py-8 text-ink-2">{{ __('No notices right now.') }}</li>
            @endforelse
        </ul>
        <div class="mt-8">{{ $notices->links() }}</div>
    </div>
</x-layouts.site>
