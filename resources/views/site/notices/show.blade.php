@php
    $url = route('notices.show', $notice);
    $club = $site->club();
@endphp
<x-layouts.site :title="$notice->tr('title')" :description="\Illuminate\Support\Str::limit((string) $notice->tr('summary'), 155)" og-type="article">
    <article class="mx-auto max-w-[760px] px-5 pt-8 pb-20">
        <a href="{{ route('notices.index') }}" class="inline-flex items-center gap-1.5 text-[15px] font-semibold text-ink-2 hover:text-ink" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ __('All notices') }}</a>
        <p class="mt-8 flex flex-wrap items-center gap-3 text-[13px]">
            @if ($notice->tag)<span class="font-bold uppercase tracking-wide text-sindoor-deep">{{ $notice->tr('tag') }}</span>@endif
            <time class="text-ink-2" datetime="{{ $notice->published_at?->toDateString() }}">{{ local_date($notice->published_at, 'l, j F Y') }}</time>
        </p>
        <h1 class="mt-3 font-display leading-[1.05] text-[clamp(2.2rem,5.5vw,3.4rem)]">{{ $notice->tr('title') }}</h1>
        <div class="mt-6 text-[18px] leading-relaxed text-ink-2 prose-site max-w-[62ch]">
            <p class="text-ink">{{ $notice->tr('summary') }}</p>
            @if ($notice->tr('body'))
                @foreach (preg_split('/\R{2,}/', (string) $notice->tr('body')) ?: [] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            @endif
        </div>
        <div class="mt-8 pt-6 border-t border-rule flex flex-wrap gap-2" data-no-print>
            <a href="https://wa.me/?text={{ rawurlencode($notice->title.': '.$url) }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-icon name="message-circle" />{{ __('Send on WhatsApp') }}</a>
            <button type="button" class="btn btn-line btn-sm" x-data @click="window.print()"><x-icon name="printer" />{{ __('Print') }}</button>
        </div>

        @if ($more->isNotEmpty())
            <nav class="mt-10 grid sm:grid-cols-2 gap-3" aria-label="{{ __('More notices') }}" data-no-print>
                @foreach ($more->take(2) as $other)
                    @php $older = $other->published_at && $notice->published_at && $other->published_at->lt($notice->published_at); @endphp
                    <a href="{{ route('notices.show', $other) }}" class="block card card-hover p-4 {{ $older ? 'sm:text-right sm:order-2' : '' }}">
                        <span class="text-[13px] text-ink-3">{{ $older ? __('Older') : __('Newer') }}</span>
                        <span class="block font-semibold leading-snug mt-0.5">{{ $other->tr('title') }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
        <p class="print-only mt-8 text-[11px]">{{ $club['name'] }} · {{ __('Reg. No.') }} {{ $club['reg_no'] }} · {{ $club['phone'] }}</p>
    </article>
</x-layouts.site>
