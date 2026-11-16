<x-layouts.member :title="$notice->tr('title')">
    <a href="{{ route('member.notices.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Notices') }}</a>
    <article class="mt-4 max-w-[680px]">
        <p class="text-[13px] text-ink-3 num">{{ local_date($notice->published_at) }} · {{ $notice->tr('tag') }}</p>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ $notice->tr('title') }}</h1>
        <div class="mt-4 prose-site text-[17px] leading-relaxed text-ink-2">
            <p>{{ $notice->tr('summary') }}</p>
            @foreach (preg_split('/\R{2,}/', (string) $notice->tr('body')) ?: [] as $paragraph)
                @if (trim($paragraph) !== '')
                    <p>{{ $paragraph }}</p>
                @endif
            @endforeach
        </div>
    </article>
</x-layouts.member>
