@php
    $modes = ['' => __('All modes'), 'cash' => __('Cash'), 'upi' => __('UPI'), 'online' => __('Online'), 'cancelled' => __('Cancelled only')];
    $gaps = $series['missing'];
@endphp
<x-layouts.admin :title="__('Receipts register')">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Receipts register') }}</h1>
            <p class="mt-1 text-ink-2">{{ __('Every receipt number in order. Numbers are never reused or deleted.') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.receipts.export', request()->only(['mode', 'q', 'year'])) }}" class="btn btn-line btn-sm"><x-icon name="file-down" /><span>{{ __('Export CSV') }}</span></a>
            <a href="{{ route('admin.collect') }}" class="btn btn-ink btn-sm"><x-icon name="plus" /><span>{{ __('New receipt') }}</span></a>
        </div>
    </div>

    <div class="mt-5 grid sm:grid-cols-3 gap-px bg-rule rounded-lg overflow-hidden border border-rule">
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __(':month so far', ['month' => local_date(now(), 'F')]) }}</p><p class="font-display text-2xl num">{{ rupees($monthTotal) }}</p><p class="text-[13px] text-ink-3">{{ __(':count valid receipts', ['count' => digits($monthReceipts)]) }}</p></div>
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __('Cash · UPI & online') }}</p><p class="font-display text-2xl num">{{ rupees($monthCash) }} <span class="text-ink-3 text-lg">·</span> {{ rupees($monthTotal - $monthCash) }}</p><p class="text-[13px] text-ink-3"><a href="{{ route('admin.cash') }}" class="hover:underline">{{ __('Cash goes to the cash box') }} →</a></p></div>
        <div class="bg-card p-4">
            <p class="text-sm text-ink-3">{{ __('Series check') }} · <span class="num">{{ \App\Enums\BookCode::Club->receiptPrefix() }}-{{ sprintf('%02d', $year % 100) }}</span></p>
            @if ($series['last'] === 0)
                <p class="font-semibold mt-1 text-ink-2">{{ __('No receipts yet this year') }}</p>
            @elseif ($gaps === [])
                <p class="font-semibold mt-1 flex items-center gap-2 text-tulsi"><x-icon name="circle-check" class="w-5 h-5" />{{ __(':first to :last complete', ['first' => $series['first'], 'last' => $series['latest']]) }}</p>
            @else
                <p class="font-semibold mt-1 flex items-center gap-2 text-sindoor"><x-icon name="triangle-alert" class="w-5 h-5" />{{ __(':count missing numbers', ['count' => digits(count($gaps))]) }}</p>
                <p class="text-[12.5px] text-sindoor-deep num break-all">{{ implode(', ', array_slice($gaps, 0, 12)) }}@if (count($gaps) > 12) …@endif</p>
            @endif
            <p class="text-[13px] text-ink-3">{{ __(':count cancelled, kept with a reason', ['count' => digits($cancelled)]) }}</p>
        </div>
    </div>

    <form method="GET" class="mt-5 flex flex-wrap items-center gap-2">
        <label for="r-year" class="sr-only">{{ __('Year') }}</label>
        <select id="r-year" name="year" class="field h-9 text-sm w-auto">
            @foreach ($years as $option)
                <option value="{{ $option }}" @selected($year === $option)>{{ digits($option) }}</option>
            @endforeach
        </select>
        <label for="r-mode" class="sr-only">{{ __('Payment mode') }}</label>
        <select id="r-mode" name="mode" class="field h-9 text-sm w-auto">
            @foreach ($modes as $value => $label)
                <option value="{{ $value }}" @selected((string) $mode === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label for="r-q" class="sr-only">{{ __('Receipt no., name or family no.') }}</label>
        <input id="r-q" name="q" value="{{ $search }}" class="field h-9 text-sm w-full sm:w-64" placeholder="{{ __('Receipt no., name or family no.') }}">
        <button class="btn btn-line btn-sm">{{ __('Filter') }}</button>
    </form>

    <div class="mt-4 paper-card overflow-x-auto">
        <table class="khata-table min-w-[680px]">
            <thead><tr><th scope="col">{{ __('Receipt') }}</th><th scope="col">{{ __('Received from') }}</th><th scope="col" class="hidden md:table-cell">{{ __('Mode') }}</th><th scope="col" class="hidden md:table-cell">{{ __('By') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th><th scope="col"><span class="sr-only">{{ __('Action') }}</span></th></tr></thead>
            <tbody>
                @forelse ($receipts as $payment)
                    @php($cancelledRow = $payment->status === \App\Enums\PaymentStatus::Cancelled)
                    <tr>
                        <td class="whitespace-nowrap"><span class="flex items-center gap-2"><span class="book-club inline-block w-2.5 h-2.5 rounded-full bg-[color:var(--book)] shrink-0"></span><span><span class="block font-semibold num">{{ $payment->receipt_no }}</span><span class="block text-[13px] text-ink-3 num">{{ local_date($payment->paid_at, 'j M') }}</span></span></span></td>
                        <td><span class="block {{ $cancelledRow ? 'line-through text-ink-3' : 'font-semibold' }}">{{ $payment->family->tr('head_name') }} <span class="num font-normal text-ink-3">{{ $payment->family->family_no }}</span></span><span class="block text-[13px] text-ink-3">{{ $cancelledRow ? $payment->note : __('Subscription').($payment->dues->isNotEmpty() ? ', '.$payment->monthsLabel() : '') }}</span></td>
                        <td class="hidden md:table-cell text-ink-2">{{ $payment->modeLabel() }}</td>
                        <td class="hidden md:table-cell text-[13px] text-ink-2 whitespace-nowrap">{{ $payment->receiver?->name ?? __('Online') }}</td>
                        <td class="amt {{ $cancelledRow ? 'line-through text-ink-3' : 'font-semibold' }}">{{ rupees($payment->amount) }}</td>
                        <td class="text-right whitespace-nowrap">@if ($cancelledRow)<span class="chip bg-ink text-white">{{ __('Cancelled') }}</span>@endif <a href="{{ route('admin.receipts.show', $payment) }}" class="btn btn-ghost btn-sm">{{ __('View') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="!py-12 text-center text-ink-3">{{ __('No receipt matches. Try the number printed on the counterfoil.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $receipts->links() }}</div>
</x-layouts.admin>
