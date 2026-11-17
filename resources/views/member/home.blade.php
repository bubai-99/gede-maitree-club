@php
    $total = (int) $dues->sum('amount');
    $first = \Illuminate\Support\Str::before($user->name, ' ');
    $next = $nextEvent;
    $shortcuts = [
        ['member.card', 'id-card', __('Member cards'), 'text-sindoor'],
        ['member.events.passes', 'ticket', __('My passes'), 'text-sindoor'],
        ['member.dues', 'scroll-text', __('Statement'), 'text-ink'],
        ['member.receipts.index', 'receipt-text', __('Receipts'), 'text-ink'],
        ['member.events.index', 'calendar-days', __('Events'), 'text-turmeric'],
        ['member.notices.index', 'megaphone', __('Notices'), 'text-ink'],
        ['member.pay', 'indian-rupee', __('Pay dues'), 'text-tulsi'],
        ['member.profile', 'users', __('Family & profile'), 'text-ink'],
    ];
@endphp
<x-layouts.member :title="__('Home')">
    <div class="flex items-center gap-3 mb-5">
        <div class="min-w-0">
            <p class="text-sm text-ink-3">{{ local_date(now(), 'D j M Y') }}</p>
            <h1 class="font-display text-[1.7rem] lg:text-[2.3rem] leading-tight">{{ __('Namaskar') }}, {{ $first }}</h1>
            <p class="text-[15px] text-ink-2">{{ __('Family') }} <span class="num">{{ $family->family_no }}</span>@if ($family->joined_on) · {{ __('member since :year', ['year' => digits($family->joined_on->format('Y'))]) }}@elseif ($family->tr('area')) · {{ $family->tr('area') }}@endif</p>
        </div>
    </div>

    <div class="grid gap-4 lg:gap-6 lg:grid-cols-3 items-start">
        <div class="lg:col-span-2 space-y-4 lg:space-y-6 min-w-0">
            {{-- Dues card --}}
            @if ($pending)
                <div class="rounded-lg border-2 border-marigold bg-marigold-wash/60 p-5">
                    <div class="flex items-start gap-3">
                        <span class="w-11 h-11 rounded-full bg-white grid place-items-center shrink-0"><x-icon name="hourglass" class="w-5 h-5 text-turmeric" /></span>
                        <div class="min-w-0">
                            <p class="font-semibold">{{ __('Payment sent, waiting for the treasurer') }}</p>
                            <p class="text-[15px] text-ink-2 mt-0.5 num">{{ rupees($pending->amount) }} · {{ $pending->monthsLabel() }}@if ($pending->upi_ref) · UTR {{ digits($pending->upi_ref) }}@endif</p>
                            <p class="text-sm text-ink-2 mt-1">{{ __('Usually confirmed the same evening.') }}</p>
                        </div>
                    </div>
                </div>
            @elseif ($total > 0)
                <div class="rounded-lg bg-sindoor text-white p-5 lg:p-6 relative overflow-hidden">
                    <div class="absolute -right-10 -top-12 opacity-15 pointer-events-none" aria-hidden="true"><x-seal :size="160" color="#fff" :rotate="10" /></div>
                    <p class="relative text-[15px]">{{ __('Your dues') }}</p>
                    <p class="relative font-display text-[3rem] leading-none mt-1 num">{{ rupees($total) }}</p>
                    <p class="relative text-[15px] mt-2">{{ $dues->map(fn ($due) => local_date($due->period, 'M Y'))->implode(' · ') }} · {{ __('Family') }} <span class="num">{{ $family->family_no }}</span></p>
                    <div class="relative mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('member.pay') }}" class="btn bg-white text-sindoor-deep hover:bg-paper flex-1 sm:flex-none sm:px-6"><x-icon name="smartphone" />{{ __('Pay now') }}</a>
                        <a href="{{ route('member.dues') }}" class="btn text-white hover:bg-white/10 hidden sm:inline-flex">{{ __('Statement') }}</a>
                    </div>
                </div>
            @else
                <div class="rounded-lg bg-tulsi text-white p-5 relative overflow-hidden">
                    <div class="absolute -right-10 -top-12 opacity-15 pointer-events-none" aria-hidden="true"><x-seal :size="150" color="#fff" :rotate="10" /></div>
                    <p class="relative text-[15px] flex items-center gap-1.5"><x-icon name="badge-check" class="w-5 h-5" />{{ __('All paid up') }}</p>
                    @if ($lastPayment)
                        <p class="relative font-display text-[2.2rem] leading-tight mt-1">{{ __('Last paid :date', ['date' => local_date($lastPayment->paid_at, 'j M Y')]) }}</p>
                    @endif
                    <p class="relative text-[15px] mt-1 text-white/90">{{ __('Thank you. Your next month is due on the 1st.') }}</p>
                    <div class="relative mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('member.pay') }}" class="btn bg-white text-tulsi hover:bg-paper">{{ __('Pay ahead') }}</a>
                        <a href="{{ route('member.dues') }}" class="btn border border-white/40 text-white hover:bg-white/10">{{ __('Statement') }}</a>
                    </div>
                </div>
            @endif

            <nav aria-label="{{ __('Shortcuts') }}">
                <ul class="grid grid-cols-4 gap-2 text-center text-[12.5px] sm:text-[13.5px] font-medium">
                    @foreach ($shortcuts as [$route, $icon, $label, $colour])
                        <li><a href="{{ route($route) }}" class="flex flex-col items-center justify-center h-full rounded-lg bg-card border border-rule py-3 px-1 hover:border-ink/30 transition-colors"><x-icon :name="$icon" class="w-6 h-6 {{ $colour }}" /><span class="block mt-1.5 leading-tight">{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </nav>

            <div class="rounded-lg bg-card border border-rule p-4 lg:p-5">
                <div class="flex items-baseline justify-between"><h2 class="font-display text-xl">{{ __('Notice board') }}</h2><a href="{{ route('member.notices.index') }}" class="text-sm font-semibold text-sindoor hover:underline">{{ __('See all') }}</a></div>
                <ul class="mt-1 divide-y divide-rule">
                    @forelse ($notices as $notice)
                        <li><a href="{{ route('member.notices.show', $notice) }}" class="flex gap-3 py-3.5 group">
                            <span class="mt-2 w-2 h-2 rounded-full shrink-0 {{ $notice->published_at && $notice->published_at->gt(now()->subDays(7)) ? 'bg-sindoor' : 'bg-transparent' }}" aria-hidden="true"></span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2 text-[12.5px]">@if ($notice->pinned)<x-icon name="pin" class="w-3.5 h-3.5 text-sindoor" />@endif<span class="font-semibold text-sindoor">{{ $notice->tr('tag') }}</span><span class="text-ink-3 num">{{ local_date($notice->published_at, 'j M') }}</span>@if ($notice->audience === \App\Enums\NoticeAudience::Members)<span class="text-sindoor-deep font-semibold">· {{ __('Members only') }}</span>@endif</span>
                                <span class="block font-semibold mt-0.5 leading-snug group-hover:underline">{{ $notice->tr('title') }}</span>
                                @if ($notice->tr('summary'))<span class="block text-[15px] text-ink-2 mt-0.5 leading-snug line-clamp-2">{{ $notice->tr('summary') }}</span>@endif
                            </span>
                        </a></li>
                    @empty
                        <li class="py-3.5 text-ink-2">{{ __('No notices right now.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-4 lg:space-y-6 min-w-0">
            @if ($next)
                @php $left = (int) now()->startOfDay()->diffInDays($next->starts_at->copy()->startOfDay()); @endphp
                <a href="{{ route('member.events.show', $next) }}" class="flex items-center gap-4 rounded-lg bg-marigold p-4 hover:brightness-[.98]">
                    <div class="text-center w-14 shrink-0 leading-none"><div class="font-display text-3xl num">{{ local_date($next->starts_at, 'j') }}</div><div class="text-[12px] font-semibold mt-1 uppercase">{{ local_date($next->starts_at, 'M') }}</div></div>
                    <div class="min-w-0">
                        <p class="font-display text-xl leading-tight">{{ $next->tr('title') }}</p>
                        <p class="text-sm text-ink">
                            @if (in_array($next->id, $registered, true))
                                {{ __('You’re registered · pass ready') }}
                            @elseif ($left === 0)
                                {{ __('Today') }} · {{ $next->tr('time_label') }}
                            @else
                                {{ trans_choice('Tomorrow|In :count days', $left, ['count' => digits($left)]) }}@if ($next->pass_required) · {{ __('get your family pass') }}@endif
                            @endif
                        </p>
                    </div>
                    <x-icon name="chevron-right" class="ml-auto w-5 h-5 shrink-0" />
                </a>
            @endif

            <div class="rounded-lg border border-rule bg-card p-4">
                <div class="flex items-baseline justify-between"><h2 class="font-display text-xl">{{ __('Coming up') }}</h2><a href="{{ route('member.events.index') }}" class="text-sm font-semibold text-sindoor hover:underline">{{ __('All events') }}</a></div>
                <ul class="mt-1 divide-y divide-rule">
                    @forelse ($upcoming as $event)
                        <li><a href="{{ route('member.events.show', $event) }}" class="flex items-center gap-3 py-3 group">
                            <span class="w-10 text-center leading-none text-sindoor shrink-0"><span class="block font-display text-2xl num">{{ local_date($event->starts_at, 'd') }}</span><span class="block text-[11px] font-semibold mt-0.5 uppercase">{{ local_date($event->starts_at, 'M') }}</span></span>
                            <span class="min-w-0 flex-1"><span class="block font-semibold leading-tight group-hover:underline">{{ $event->tr('title') }}</span><span class="block text-sm text-ink-3">{{ $event->tr('place') }}</span></span>
                            @if (in_array($event->id, $registered, true))
                                <span class="chip bg-tulsi-wash text-tulsi"><x-icon name="check" />{{ __('Going') }}</span>
                            @endif
                        </a></li>
                    @empty
                        <li class="py-3 text-ink-2">{{ __('No events are planned yet. Check back soon.') }}</li>
                    @endforelse
                </ul>
            </div>

            <a href="{{ route('member.card') }}" class="block rounded-lg border border-rule bg-card p-4 hover:border-ink/30">
                <div class="flex items-center justify-between"><h2 class="font-display text-xl">{{ __('Your family') }}</h2><x-icon name="chevron-right" class="w-4 h-4 text-ink-3" /></div>
                <div class="mt-3 flex -space-x-2">
                    @foreach ($people as $i => $person)
                        <span class="ring-2 ring-card rounded-full"><span class="w-10 h-10 text-base rounded-full grid place-items-center font-display {{ $i ? 'bg-paper-2 text-ink' : 'bg-ink text-white' }}" aria-hidden="true">{{ mb_substr($person->tr('name'), 0, 1) }}</span></span>
                    @endforeach
                </div>
                <p class="text-sm text-ink-2 mt-2">{{ trans_choice(':count member card|:count member cards', $people->count(), ['count' => digits($people->count())]) }}</p>
            </a>
        </div>
    </div>
</x-layouts.member>
