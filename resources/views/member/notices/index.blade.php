<x-layouts.member :title="__('Notices')">
    <h1 class="font-display text-[1.7rem] lg:text-[2.3rem] leading-tight">{{ __('Notice board') }}</h1>
    <p class="text-[15px] text-ink-2 mt-1">{{ __('From the committee. Important notices also come on WhatsApp.') }}</p>

    @if ($tags->isNotEmpty())
        <nav class="mt-5 flex gap-2 overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap" aria-label="{{ __('Filter notices') }}">
            <a href="{{ route('member.notices.index') }}" @if ($tag === '') aria-current="page" @endif class="chip h-9 px-4 text-[14.5px] whitespace-nowrap border border-rule bg-card aria-[current=page]:bg-ink aria-[current=page]:text-paper aria-[current=page]:border-ink">{{ __('All') }}</a>
            @foreach ($tags as $option)
                <a href="{{ route('member.notices.index', ['tag' => $option['tag']]) }}" @if ($tag === $option['tag']) aria-current="page" @endif class="chip h-9 px-4 text-[14.5px] whitespace-nowrap border border-rule bg-card aria-[current=page]:bg-ink aria-[current=page]:text-paper aria-[current=page]:border-ink">{{ $option['label'] }}</a>
            @endforeach
        </nav>
    @endif

    <ul class="mt-5 rounded-lg border border-rule bg-card divide-y divide-rule">
        @forelse ($notices as $notice)
            @php $fresh = $notice->published_at && $notice->published_at->gt(now()->subDays(7)); @endphp
            <li>
                <a href="{{ route('member.notices.show', $notice) }}" class="flex gap-3 px-4 py-3.5 group">
                    <span class="mt-2 w-2 h-2 rounded-full shrink-0 {{ $fresh ? 'bg-sindoor' : 'bg-transparent' }}" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2 text-[12.5px]">
                            @if ($notice->pinned)<span class="inline-flex items-center gap-1 font-semibold text-sindoor"><x-icon name="pin" class="w-3.5 h-3.5" />{{ __('Pinned') }}</span><span class="text-ink-3">·</span>@endif
                            @if ($notice->tag)<span class="font-semibold text-sindoor">{{ $notice->tr('tag') }}</span>@endif
                            <span class="text-ink-3 num">{{ local_date($notice->published_at, 'j M') }}</span>
                            @if ($notice->audience === \App\Enums\NoticeAudience::Members)<span class="text-sindoor-deep font-semibold">· {{ __('Members only') }}</span>@endif
                        </span>
                        <span class="block font-semibold mt-0.5 leading-snug group-hover:underline">{{ $notice->tr('title') }}</span>
                        <span class="block text-[15px] text-ink-2 mt-0.5 leading-snug line-clamp-2">{{ $notice->tr('summary') }}</span>
                    </span>
                    <x-icon name="chevron-right" class="w-4 h-4 text-ink-3 mt-1 shrink-0" />
                </a>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-ink-2">{{ __('No notices right now.') }}</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $notices->links() }}</div>
</x-layouts.member>
