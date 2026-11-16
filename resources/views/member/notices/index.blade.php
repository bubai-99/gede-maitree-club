<x-layouts.member :title="__('Notices')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Notices') }}</h1>
    <ul class="mt-6 space-y-3">
        @forelse ($notices as $notice)
            <li>
                <a href="{{ route('member.notices.show', $notice) }}" class="card card-hover p-5 block">
                    <span class="block text-[12.5px] text-ink-3 num">{{ local_date($notice->published_at, 'j M Y') }} · {{ $notice->tr('tag') }}
                        @if ($notice->pinned) · <span class="text-sindoor font-semibold">{{ __('Pinned') }}</span>@endif
                        @if ($notice->audience === \App\Enums\NoticeAudience::Members) · <span class="text-sindoor-deep font-semibold">{{ __('Members only') }}</span>@endif
                    </span>
                    <span class="mt-1 block font-display text-[1.3rem] leading-snug">{{ $notice->tr('title') }}</span>
                    <span class="mt-1 block text-[15px] text-ink-2">{{ $notice->tr('summary') }}</span>
                </a>
            </li>
        @empty
            <li class="text-ink-2">{{ __('No notices right now.') }}</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $notices->links() }}</div>
</x-layouts.member>
