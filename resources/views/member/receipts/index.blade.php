<x-layouts.member :title="__('Receipts')">
    {{-- Filters and pages swap in place (resources/js/instant.js) --}}
    <div id="member-receipts" data-instant class="instant-region">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Receipts') }}</h1>
    <p class="text-ink-2">{{ __('Every subscription receipt in one place.') }}</p>

    <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_260px] items-center">
        <p class="text-sm text-ink-2">{{ __('Paid to the club in :year:', ['year' => digits(now()->year)]) }} <span class="font-semibold text-ink num">{{ rupees($thisYear) }}</span></p>
        <form method="GET" role="search">
            <label class="relative block"><span class="sr-only">{{ __('Search by receipt number') }}</span>
                <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-3" />
                <input name="q" value="{{ $search }}" class="field pl-9 h-10" placeholder="MC-26-0388" autocomplete="off" />
            </label>
        </form>
    </div>

    @if ($payments->isEmpty())
        <div class="mt-6 card p-6 text-ink-2">
            <p>{{ $search !== '' ? __('No receipts match. Try another receipt number.') : __('Nothing here yet.') }}</p>
            @if ($search !== '')
                <a href="{{ route('member.receipts.index') }}" class="btn btn-line btn-sm mt-3">{{ __('Clear search') }}</a>
            @endif
        </div>
    @else
        @foreach ($payments->groupBy(fn ($payment) => $payment->paid_at?->format('Y')) as $year => $group)
            <h2 class="mt-5 text-[13px] font-bold uppercase tracking-wide text-ink-3 num">{{ digits((string) $year) }}</h2>
            <ul class="mt-1 rounded-lg border border-rule bg-card divide-y divide-rule overflow-hidden">
                @foreach ($group as $payment)
                    <li><a href="{{ route('member.receipts.show', $payment) }}" class="flex items-center gap-3 px-4 py-3.5 hover:bg-paper-2/60">
                        <span class="w-10 h-10 rounded-full bg-sindoor-wash text-sindoor-deep grid place-items-center shrink-0"><x-icon name="indian-rupee" class="w-[18px] h-[18px]" /></span>
                        <span class="flex-1 min-w-0">
                            <span class="block font-semibold truncate">{{ __('Subscription') }} · {{ __(':count months', ['count' => digits($payment->dues->count())]) }}</span>
                            <span class="block text-sm text-ink-3"><span class="num">{{ $payment->receipt_no }}</span> · {{ local_date($payment->paid_at, 'j M Y') }} · {{ $payment->modeLabel() }}</span>
                        </span>
                        @if ($payment->status === \App\Enums\PaymentStatus::Cancelled)
                            <span class="chip bg-ink text-white">{{ __('Cancelled') }}</span>
                        @endif
                        <span class="font-semibold num {{ $payment->status === \App\Enums\PaymentStatus::Cancelled ? 'line-through text-ink-3' : '' }}">{{ rupees($payment->amount) }}</span><x-icon name="chevron-right" class="w-4 h-4 text-ink-3" />
                    </a></li>
                @endforeach
            </ul>
        @endforeach
        <div class="mt-6">{{ $payments->links() }}</div>
    @endif
    </div>
</x-layouts.member>
