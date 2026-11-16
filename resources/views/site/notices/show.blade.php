<x-layouts.site :title="$notice->tr('title')" :description="\Illuminate\Support\Str::limit((string) $notice->tr('summary'), 155)" og-type="article">
    <article class="mx-auto max-w-[760px] px-5 pt-12 pb-16">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-[14px] text-ink-2">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:text-ink">{{ __('Home') }}</a></li><li aria-hidden="true">/</li>
                <li><a href="{{ route('notices.index') }}" class="hover:text-ink">{{ __('Notices') }}</a></li>
            </ol>
        </nav>
        <p class="mt-6 flex flex-wrap items-center gap-2 text-[13px] text-ink-3 num">
            <time datetime="{{ $notice->published_at?->toDateString() }}">{{ local_date($notice->published_at) }}</time>
            @if ($notice->tag)
                <span class="chip h-6 bg-paper-2 text-ink-2">{{ $notice->tr('tag') }}</span>
            @endif
        </p>
        <h1 class="mt-3 font-display text-[clamp(2rem,5vw,3rem)] leading-tight">{{ $notice->tr('title') }}</h1>
        <div class="mt-6 prose-site text-[18px] leading-relaxed text-ink-2">
            <p>{{ $notice->tr('summary') }}</p>
            @if ($notice->tr('body'))
                @foreach (preg_split('/\R{2,}/', (string) $notice->tr('body')) ?: [] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            @endif
        </div>
        <p class="mt-10"><a href="{{ route('notices.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-sindoor hover:underline"><x-icon name="arrow-left" class="w-4 h-4" /><span>{{ __('All notices') }}</span></a></p>

        @if ($more->isNotEmpty())
            <h2 class="mt-14 font-display text-[1.6rem]">{{ __('More notices') }}</h2>
            <ul class="mt-3 border-t border-ink/80">
                @foreach ($more as $other)
                    <li class="py-3 border-b border-rule"><a href="{{ route('notices.show', $other) }}" class="font-semibold hover:text-sindoor">{{ $other->tr('title') }}</a> <span class="text-[13px] text-ink-3 num">· {{ local_date($other->published_at, 'j M') }}</span></li>
                @endforeach
            </ul>
        @endif
    </article>
</x-layouts.site>
