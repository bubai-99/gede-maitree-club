@php($total = (int) $dues->sum('amount'))
<x-layouts.member :title="__('Home')">
    <p class="text-ink-2">{{ local_date(now(), 'l, j F') }}</p>
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Namaskar') }}, {{ \Illuminate\Support\Str::before($user->name, ' ') }}</h1>
    <p class="text-[14px] text-ink-3 num">{{ __('Family') }} {{ $family->family_no }} · {{ $family->tr('area') }}</p>

    <div class="mt-6 grid lg:grid-cols-2 gap-5">
        {{-- Dues card --}}
        <section class="card p-6 {{ $total > 0 ? 'border-sindoor/40' : '' }}" aria-labelledby="dues-h">
            <h2 id="dues-h" class="text-[13px] font-semibold uppercase tracking-wide text-ink-3">{{ __('Your dues') }}</h2>
            @if ($total > 0)
                <p class="mt-2 font-display text-[2.6rem] leading-none num text-sindoor">{{ rupees($total) }}</p>
                <p class="mt-2 text-ink-2">{{ $dues->map(fn ($due) => local_date($due->period, 'M Y'))->implode(', ') }}</p>
                <a href="{{ route('member.pay') }}" class="mt-5 btn btn-primary"><x-icon name="indian-rupee" /><span>{{ __('Pay now') }}</span></a>
            @else
                <p class="mt-2 font-display text-[2rem] leading-none text-tulsi">{{ __('All paid up') }}</p>
                @if ($lastPayment)
                    <p class="mt-2 text-ink-2">{{ __('Last paid :date', ['date' => local_date($lastPayment->paid_at)]) }}</p>
                @endif
                <a href="{{ route('member.dues') }}" class="mt-5 btn btn-line">{{ __('Statement') }}</a>
            @endif
        </section>

        {{-- Next event --}}
        <section class="card p-6" aria-labelledby="next-h">
            <h2 id="next-h" class="text-[13px] font-semibold uppercase tracking-wide text-ink-3">{{ __('Coming up') }}</h2>
            @if ($nextEvent)
                <div class="mt-3 flex items-center gap-5">
                    <div class="text-center leading-none w-16 shrink-0">
                        <span class="block font-display text-[2.4rem] num text-sindoor">{{ local_date($nextEvent->starts_at, 'd') }}</span>
                        <span class="block text-[13px] font-semibold uppercase">{{ local_date($nextEvent->starts_at, 'M') }}</span>
                    </div>
                    <div class="min-w-0">
                        <a href="{{ route('member.events.show', $nextEvent) }}" class="font-display text-[1.4rem] leading-snug hover:text-sindoor">{{ $nextEvent->tr('title') }}</a>
                        <p class="text-[14px] text-ink-2">{{ $nextEvent->tr('time_label') }} · {{ $nextEvent->tr('place') }}</p>
                        @if ($nextEvent->pass_required)
                            <a href="{{ route('member.card') }}" class="mt-2 inline-flex chip bg-marigold-wash text-turmeric"><x-icon name="ticket" />{{ __('Show your card at the gate') }}</a>
                        @endif
                    </div>
                </div>
            @else
                <p class="mt-3 text-ink-2">{{ __('No events are planned yet. Check back soon.') }}</p>
            @endif
            <a href="{{ route('member.events.index') }}" class="mt-5 inline-flex items-center gap-1 text-[14px] font-semibold text-sindoor hover:underline">{{ __('All events') }}<x-icon name="arrow-right" class="w-4 h-4" /></a>
        </section>
    </div>

    {{-- Notices --}}
    <section class="mt-6 card p-6" aria-labelledby="notices-h">
        <div class="flex items-center justify-between gap-3">
            <h2 id="notices-h" class="font-display text-[1.5rem]">{{ __('Notice board') }}</h2>
            <a href="{{ route('member.notices.index') }}" class="text-[14px] font-semibold text-sindoor hover:underline">{{ __('See all') }}</a>
        </div>
        <ul class="mt-2 divide-y divide-rule">
            @forelse ($notices as $notice)
                <li class="py-3">
                    <p class="text-[12.5px] text-ink-3 num">{{ local_date($notice->published_at, 'j M') }} · {{ $notice->tr('tag') }}
                        @if ($notice->audience === \App\Enums\NoticeAudience::Members) · <span class="text-sindoor-deep font-semibold">{{ __('Members only') }}</span>@endif</p>
                    <a href="{{ route('member.notices.show', $notice) }}" class="font-semibold hover:text-sindoor">{{ $notice->tr('title') }}</a>
                </li>
            @empty
                <li class="py-3 text-ink-2">{{ __('No notices right now.') }}</li>
            @endforelse
        </ul>
    </section>

    <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach ([['member.card', 'id-card', __('Member cards')], ['member.receipts.index', 'receipt-text', __('Receipts')], ['member.profile', 'users', __('Your family')], ['member.dues', 'indian-rupee', __('Statement')]] as [$route, $icon, $label])
            <a href="{{ route($route) }}" class="card card-hover p-4 flex flex-col items-start gap-2"><x-icon :name="$icon" class="w-6 h-6 text-sindoor" /><span class="font-semibold">{{ $label }}</span></a>
        @endforeach
    </div>
</x-layouts.member>
