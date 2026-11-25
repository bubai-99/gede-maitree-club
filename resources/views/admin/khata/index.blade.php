@php
    $query = fn (array $change) => route('admin.khata.index', array_filter([
        'book' => $book?->code->value ?? 'all',
        'month' => $month->format('Y-m'),
        'type' => $type?->value,
        'head' => $head?->value,
        ...$change,
    ], fn ($v) => $v !== null && $v !== ''));
@endphp
<x-layouts.admin :title="__('Khata')">
    {{-- Filters and pages swap in place (resources/js/instant.js) --}}
    <div id="admin-khata" data-instant class="instant-region">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Khata') }}</h1>
            <p class="mt-1 text-ink-2">{{ local_date($month, 'F Y') }} · {{ $book ? $book->code->label() : __('three books, one AGM report') }}</p>
        </div>
        <div class="flex flex-wrap gap-2" data-no-print>
            <button type="button" onclick="window.print()" class="btn btn-line btn-sm"><x-icon name="printer" /><span>{{ __('Print month') }}</span></button>
            @can('reports.open')
                <a href="{{ route('admin.reports.index') }}" class="btn btn-line btn-sm"><x-icon name="file-spreadsheet" /><span>{{ __('AGM report') }}</span></a>
            @endcan
            @if ($canWrite)
                <a href="{{ route('admin.khata.create', ['book' => $book?->code->value]) }}" class="btn btn-ink btn-sm"><x-icon name="plus" /><span>{{ __('New entry') }}</span></a>
            @endif
        </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-2" data-no-print>
        @if ($seeAll)
            <a href="{{ $query(['book' => 'all']) }}" @if ($book === null) aria-current="page" @endif class="chip h-9 px-3.5 text-sm border border-rule bg-card aria-[current=page]:bg-ink aria-[current=page]:text-paper aria-[current=page]:border-ink">{{ __('All books') }}</a>
        @endif
        @foreach ($books as $option)
            <a href="{{ $query(['book' => $option->code->value]) }}" @if ($book?->id === $option->id) aria-current="page" @endif class="book-{{ $option->code->value }} chip h-9 px-3.5 text-sm border border-rule bg-card aria-[current=page]:bg-[color:var(--book)] aria-[current=page]:text-white aria-[current=page]:border-transparent"><span class="inline-block w-2.5 h-2.5 rounded-full bg-[color:var(--book)]"></span>{{ $option->code->label() }}</a>
        @endforeach
        <form method="GET" class="flex flex-wrap items-center gap-2 sm:ml-auto">
            <input type="hidden" name="book" value="{{ $book?->code->value ?? 'all' }}">
            <label for="k-month" class="sr-only">{{ __('Month') }}</label>
            <select id="k-month" name="month" class="field h-9 text-sm w-auto">
                @foreach ($months as $option)
                    <option value="{{ $option->format('Y-m') }}" @selected($option->format('Y-m') === $month->format('Y-m'))>{{ local_date($option, 'F Y') }}</option>
                @endforeach
            </select>
            <label for="k-type" class="sr-only">{{ __('Type') }}</label>
            <select id="k-type" name="type" class="field h-9 text-sm w-auto">
                <option value="">{{ __('Joma and khoroch') }}</option>
                @foreach (\App\Enums\LedgerType::cases() as $option)
                    <option value="{{ $option->value }}" @selected($type === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
            <label for="k-head" class="sr-only">{{ __('Head') }}</label>
            <select id="k-head" name="head" class="field h-9 text-sm w-auto">
                <option value="">{{ __('All heads') }}</option>
                @foreach (\App\Enums\LedgerHead::cases() as $option)
                    <option value="{{ $option->value }}" @selected($head === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-line btn-sm">{{ __('Filter') }}</button>
        </form>
    </div>

    <div class="mt-4 grid grid-cols-2 lg:grid-cols-5 gap-px bg-rule rounded-lg overflow-hidden border border-rule {{ $book ? 'book-'.$book->code->value : '' }}">
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __('Joma this month') }}</p><p class="font-display text-2xl num">{{ rupees($totals['income']) }}</p></div>
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __('Khoroch this month') }}</p><p class="font-display text-2xl text-sindoor num">{{ rupees($totals['expense']) }}</p></div>
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __('Surplus') }}</p><p class="font-display text-2xl num {{ $totals['income'] - $totals['expense'] >= 0 ? 'text-tulsi' : 'text-sindoor' }}">{{ $totals['income'] - $totals['expense'] < 0 ? '−' : '' }}{{ rupees(abs($totals['income'] - $totals['expense'])) }}</p></div>
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __('Cash in hand today') }}</p><p class="font-display text-2xl num">{{ $position['cash'] < 0 ? '−' : '' }}{{ rupees(abs($position['cash'])) }}</p></div>
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __('In the bank today') }}</p><p class="font-display text-2xl num">{{ $position['bank'] < 0 ? '−' : '' }}{{ rupees(abs($position['bank'])) }}</p></div>
    </div>

    <div class="mt-4 paper-card overflow-hidden">
        <div class="relative overflow-x-auto"><div class="khata sm:min-w-[820px]">
            <table class="khata-table">
                <thead><tr>
                    <th scope="col" class="w-[64px] !px-2">{{ __('Date') }}</th>
                    <th scope="col" class="!pl-4">{{ __('Particulars') }}</th>
                    <th scope="col" class="hidden md:table-cell">{{ __('Entered by') }}</th>
                    <th scope="col" class="amt">{{ __('Joma (in)') }}</th>
                    <th scope="col" class="amt">{{ __('Khoroch (out)') }}</th>
                    @if ($running)<th scope="col" class="amt">{{ __('Balance') }}</th>@endif
                    <th scope="col" data-no-print><span class="sr-only">{{ __('Action') }}</span></th>
                </tr></thead>
                <tbody>
                    @if ($running)
                        <tr class="bg-paper-2/40"><td></td><td class="!pl-4 text-ink-2">{{ __('Brought forward') }}</td><td class="hidden md:table-cell"></td><td></td><td></td><td class="amt font-semibold">{{ $opening < 0 ? '−' : '' }}{{ rupees(abs($opening)) }}</td><td data-no-print></td></tr>
                    @endif
                    @forelse ($entries as $i => $entry)
                        @php
                            $in = $entry->type === \App\Enums\LedgerType::Income || $entry->type === \App\Enums\LedgerType::Withdrawal;
                            $sign = $entry->reverses_id ? '−' : '';
                        @endphp
                        <tr @if ($i && $entries[$i - 1]->entry_date->ne($entry->entry_date)) class="[&>td]:border-t [&>td]:border-t-ink/20" @endif>
                            <td class="!px-2 text-[13px] text-ink-2 num whitespace-nowrap">{{ $i && $entries[$i - 1]->entry_date->eq($entry->entry_date) ? '' : local_date($entry->entry_date, 'j M') }}</td>
                            <td class="!pl-4">
                                <span class="flex items-center gap-2.5">
                                    <span class="book-{{ $entry->book->code->value }} inline-block w-2.5 h-2.5 rounded-full bg-[color:var(--book)] shrink-0" title="{{ $entry->book->code->label() }}"></span>
                                    <span class="{{ $entry->reversal ? 'line-through text-ink-3' : '' }}">{{ $entry->head->label() }}@if ($entry->type->isTransfer()) · {{ $entry->type->label() }}@endif</span>
                                    @if ($entry->hasMedia('bill'))
                                        <a href="{{ route('admin.khata.bill', $entry) }}" target="_blank" class="text-ink-3 hover:text-ink" title="{{ __('Bill photo') }}"><x-icon name="paperclip" class="w-4 h-4" /><span class="sr-only">{{ __('Bill photo') }}</span></a>
                                    @endif
                                </span>
                                <span class="block text-[12.5px] text-ink-3 ml-5">
                                    {{ $entry->account->label() }}@if ($entry->note) · {{ $entry->note }}@endif
                                    @if ($entry->reverses_id) · {{ __('Reverses the entry of :date', ['date' => local_date($entry->reverses?->entry_date, 'j M Y')]) }}@endif
                                </span>
                            </td>
                            <td class="hidden md:table-cell text-[13px] text-ink-2 whitespace-nowrap">{{ $entry->recorder?->name ?? __('Online') }}</td>
                            <td class="amt {{ $in ? 'text-ink font-semibold' : '' }}">{{ $in ? $sign.rupees($entry->amount) : '' }}</td>
                            <td class="amt {{ $in ? '' : 'text-sindoor font-semibold' }}">{{ $in ? '' : $sign.rupees($entry->amount) }}</td>
                            @if ($running)
                                @php($bal = $running[$entry->id])
                                <td class="amt text-ink-2">{{ $bal < 0 ? '−' : '' }}{{ rupees(abs($bal)) }}</td>
                            @endif
                            <td class="text-right" data-no-print>
                                @can('reverse', $entry)
                                    @if (! $entry->reversal)
                                        <details class="text-left inline-block">
                                            <summary class="cursor-pointer btn btn-ghost btn-sm text-ink-2">{{ __('Reverse') }}</summary>
                                            <form method="POST" action="{{ route('admin.khata.reverse', $entry) }}" class="mt-2 w-56 space-y-2">
                                                @csrf
                                                <label for="rev-{{ $entry->id }}" class="label">{{ __('Why?') }}</label>
                                                <input id="rev-{{ $entry->id }}" name="note" class="field h-10" required minlength="3" maxlength="200">
                                                <button class="btn btn-line btn-sm">{{ __('Reverse entry') }}</button>
                                            </form>
                                        </details>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="!py-12 text-center text-ink-3">{{ __('No entries this month.') }}</td></tr>
                    @endforelse
                    <tr class="bg-paper-2/50 font-semibold [&>td]:border-t [&>td]:border-t-ink/20 [&>td]:border-b-[3px] [&>td]:border-double [&>td]:border-b-ink/25">
                        <td></td><td class="!pl-4">{{ __('Month total') }}</td><td class="hidden md:table-cell"></td>
                        <td class="amt num">{{ rupees($totals['income']) }}</td><td class="amt text-sindoor num">{{ rupees($totals['expense']) }}</td>
                        @if ($running)<td></td>@endif
                        <td data-no-print></td>
                    </tr>
                </tbody>
            </table>
        </div></div>
    </div>
    @error('note')<p class="mt-3 field-error">{{ $message }}</p>@enderror
    <p class="text-sm text-ink-3 mt-3">{{ __('Entries cannot be deleted, only reversed with a note. Every change is logged for the auditor.') }}</p>
    </div>
</x-layouts.admin>
