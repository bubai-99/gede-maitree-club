@php
    $fyLabel = fn (int $y) => local_date(\Illuminate\Support\Carbon::create($y, 4, 1), 'M Y').' – '.local_date(\Illuminate\Support\Carbon::create($y + 1, 3, 1), 'M Y');
    $sum = fn (string $key) => (int) $collections->sum($key);
@endphp
<x-layouts.admin :title="__('Reports')">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Reports') }}</h1>
            <p class="mt-1 text-ink-2">{{ __('Statements for the committee, the auditor and the AGM.') }}</p>
        </div>
        <form method="GET" class="flex items-center gap-2" data-no-print>
            <label for="rep-year" class="text-sm text-ink-2">{{ __('Financial year') }}</label>
            <select id="rep-year" name="year" class="field h-9 text-sm w-auto" onchange="this.form.submit()">
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected($year === $option)>{{ $fyLabel($option) }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-line btn-sm">{{ __('Show') }}</button></noscript>
            <button type="button" onclick="window.print()" class="btn btn-line btn-sm"><x-icon name="printer" /><span>{{ __('Print') }}</span></button>
        </form>
    </div>

    <section class="mt-6 paper-card overflow-hidden" aria-labelledby="col-h">
        <div class="px-5 pt-4 pb-3 border-b border-rule flex flex-wrap items-center gap-2">
            <h2 id="col-h" class="font-display text-xl">{{ __('Subscriptions collected, month by month') }}</h2>
            <span class="text-sm text-ink-3">{{ $fyLabel($year) }}</span>
            <a href="{{ route('admin.reports.collections', ['year' => $year]) }}" class="ml-auto btn btn-line btn-sm" data-no-print><x-icon name="file-down" /><span>CSV</span></a>
        </div>
        <div class="overflow-x-auto"><table class="khata-table min-w-[640px]">
            <thead><tr><th scope="col">{{ __('Month') }}</th><th scope="col" class="amt">{{ __('Receipts') }}</th><th scope="col" class="amt">{{ __('Cash') }}</th><th scope="col" class="amt">{{ __('UPI') }}</th><th scope="col" class="amt">{{ __('Online') }}</th><th scope="col" class="amt">{{ __('Total') }}</th></tr></thead>
            <tbody>
                @foreach ($collections as $row)
                    <tr>
                        <td>{{ local_date($row['month'], 'F Y') }}</td>
                        <td class="amt">{{ digits($row['receipts']) }}</td>
                        <td class="amt">{{ rupees($row['cash']) }}</td>
                        <td class="amt">{{ rupees($row['upi']) }}</td>
                        <td class="amt">{{ rupees($row['online']) }}</td>
                        <td class="amt font-semibold">{{ rupees($row['total']) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-paper-2/50 font-semibold [&>td]:border-t [&>td]:border-t-ink/20 [&>td]:border-b-[3px] [&>td]:border-double [&>td]:border-b-ink/25">
                    <td>{{ __('Year total') }}</td><td class="amt">{{ digits($sum('receipts')) }}</td><td class="amt">{{ rupees($sum('cash')) }}</td><td class="amt">{{ rupees($sum('upi')) }}</td><td class="amt">{{ rupees($sum('online')) }}</td><td class="amt">{{ rupees($sum('total')) }}</td>
                </tr>
            </tbody>
        </table></div>
    </section>

    <section class="mt-8" aria-labelledby="ie-h">
        <div class="flex flex-wrap items-center gap-2">
            <h2 id="ie-h" class="font-display text-[1.6rem]">{{ __('Income and expenditure for the AGM') }}</h2>
            <a href="{{ route('admin.reports.income-expenditure', ['year' => $year]) }}" class="ml-auto btn btn-line btn-sm" data-no-print><x-icon name="file-down" /><span>CSV</span></a>
        </div>
        <p class="text-sm text-ink-3">{{ $fyLabel($year) }} · {{ __('Reversed entries are taken off. Cash put in the bank is not income or expense.') }}</p>

        <div class="mt-4 grid xl:grid-cols-3 gap-5 items-start">
            @foreach ($books as $report)
                @php($surplus = $report['total_income'] - $report['total_expense'])
                <article class="paper-card overflow-hidden book-{{ $report['book']->code->value }}">
                    <h3 class="px-5 pt-4 pb-3 border-b border-rule font-display text-xl flex items-center gap-2"><span class="inline-block w-2.5 h-2.5 rounded-full bg-[color:var(--book)]"></span>{{ $report['book']->code->label() }}</h3>
                    <table class="khata-table">
                        <tbody>
                            <tr><th scope="rowgroup" colspan="2">{{ __('Income') }}</th></tr>
                            @forelse ($report['income'] as $line)
                                <tr><td>{{ $line['head']->label() }}</td><td class="amt">{{ $line['amount'] < 0 ? '−' : '' }}{{ rupees(abs($line['amount'])) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-ink-3">{{ __('None') }}</td></tr>
                            @endforelse
                            <tr class="font-semibold"><td>{{ __('Total income') }}</td><td class="amt">{{ rupees($report['total_income']) }}</td></tr>
                            <tr><th scope="rowgroup" colspan="2">{{ __('Expenditure') }}</th></tr>
                            @forelse ($report['expense'] as $line)
                                <tr><td>{{ $line['head']->label() }}</td><td class="amt">{{ $line['amount'] < 0 ? '−' : '' }}{{ rupees(abs($line['amount'])) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-ink-3">{{ __('None') }}</td></tr>
                            @endforelse
                            <tr class="font-semibold"><td>{{ __('Total expenditure') }}</td><td class="amt">{{ rupees($report['total_expense']) }}</td></tr>
                            <tr class="bg-paper-2/50 font-semibold [&>td]:border-t [&>td]:border-t-ink/20 [&>td]:border-b-[3px] [&>td]:border-double [&>td]:border-b-ink/25">
                                <td>{{ $surplus >= 0 ? __('Surplus') : __('Deficit') }}</td>
                                <td class="amt {{ $surplus >= 0 ? 'text-tulsi' : 'text-sindoor' }}">{{ rupees(abs($surplus)) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.admin>
