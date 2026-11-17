@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
    $first = \Illuminate\Support\Str::before($user->name, ' ');
    $totalIn = (int) $books->sum('income');
    $totalOut = (int) $books->sum('expense');
    $needs = ($soon ? 1 : 0) + $upiWaiting->count() + ($openEnquiries ? 1 : 0) + ($owing->isNotEmpty() ? 1 : 0) + ($cashOut->isNotEmpty() ? 1 : 0) + ($lowStock->isNotEmpty() ? 1 : 0) + ($drafts ? 1 : 0);
    $wide = $books->isNotEmpty() || $chanda;
@endphp
<x-layouts.admin :title="__('Today')">
    <div class="flex flex-wrap items-end gap-4 mb-6">
        <div class="min-w-0">
            <h1 class="font-display text-[2.1rem] leading-tight">{{ local_date(now(), 'l, j F') }}</h1>
            <p class="text-[15px] text-ink-2 mt-1">{{ $greeting }}, {{ $first }}. {{ $books->isNotEmpty() ? __('Here is where the books stand.') : __('Here is what is waiting for you.') }}</p>
        </div>
    </div>

    <div class="grid xl:grid-cols-12 gap-6">
        @if ($wide)
            {{-- Books sheet --}}
            <div class="xl:col-span-7 paper-card overflow-hidden">
                @if ($books->isNotEmpty())
                    <div class="px-5 pt-4 pb-3 flex items-center border-b border-rule">
                        <h2 class="font-display text-xl">{{ __(':month books', ['month' => local_date(now(), 'F')]) }}</h2>
                        <a href="{{ route('admin.khata.index') }}" class="ml-auto text-sm font-semibold text-sindoor hover:underline">{{ __('Open khata') }}</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="khata-table min-w-[480px]">
                            <thead><tr><th scope="col">{{ __('Book') }}</th><th scope="col" class="!text-right">{{ __('In (joma)') }}</th><th scope="col" class="!text-right">{{ __('Out (khoroch)') }}</th><th scope="col" class="!text-right">{{ __('Surplus') }}</th></tr></thead>
                            <tbody>
                                @foreach ($books as $row)
                                    @php $code = $row['book']->code->value; @endphp
                                    <tr>
                                        <td><a href="{{ route('admin.khata.index', ['book' => $code]) }}" class="flex items-center gap-2.5 font-semibold hover:underline"><span class="book-{{ $code }} inline-block w-2.5 h-2.5 rounded-full bg-[color:var(--book)] shrink-0"></span>{{ __(ucfirst($code)) }}</a></td>
                                        <td class="amt">{{ rupees($row['income']) }}</td>
                                        <td class="amt text-ink-2">{{ rupees($row['expense']) }}</td>
                                        <td class="amt font-semibold {{ $row['income'] - $row['expense'] >= 0 ? 'text-tulsi' : 'text-sindoor' }}">{{ $row['income'] - $row['expense'] < 0 ? '−' : '' }}{{ rupees(abs($row['income'] - $row['expense'])) }}</td>
                                    </tr>
                                @endforeach
                                @if ($books->count() > 1)
                                    <tr class="bg-paper-2/50 font-semibold [&>td]:border-t [&>td]:border-t-ink/20 [&>td]:border-b-[3px] [&>td]:border-double [&>td]:border-b-ink/25">
                                        <td>{{ __('All books') }}</td>
                                        <td class="amt">{{ rupees($totalIn) }}</td>
                                        <td class="amt">{{ rupees($totalOut) }}</td>
                                        <td class="amt {{ $totalIn - $totalOut >= 0 ? 'text-tulsi' : 'text-sindoor' }}">{{ $totalIn - $totalOut < 0 ? '−' : '' }}{{ rupees(abs($totalIn - $totalOut)) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($chanda)
                    @php $goal = $chanda['target'] ?: $chanda['budget']; @endphp
                    <div class="px-5 py-4 {{ $books->isNotEmpty() ? 'border-t border-rule' : '' }}">
                        <div class="flex items-baseline justify-between gap-3 text-[15px]">
                            <a href="{{ route('admin.chanda.show', $chanda['event']) }}" class="font-semibold hover:underline">{{ __(':event chanda', ['event' => $chanda['event']->tr('title')]) }}</a>
                            <span class="num"><span class="font-semibold">{{ rupees($chanda['collected']) }}</span>@if ($goal) <span class="text-ink-3">{{ __('of') }} {{ rupees($goal) }}</span>@endif</span>
                        </div>
                        @if ($goal)
                            <div class="mt-2 h-2.5 rounded-full bg-paper-2 overflow-hidden"><div class="h-full bg-sindoor rounded-full" style="width:{{ min(100, round($chanda['collected'] / $goal * 100, 1)) }}%"></div></div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- Needs you: first on phones, beside the books on wide screens --}}
        <div class="{{ $wide ? 'xl:col-span-5' : 'xl:col-span-12 max-w-[720px]' }} order-first xl:order-none">
            <h2 class="font-display text-xl mb-3 flex items-center gap-2.5">{{ __('Needs you today') }}@if ($needs)<span class="chip h-6 px-2 text-[13px] bg-sindoor text-white num">{{ digits($needs) }}</span>@endif</h2>
            <ul class="space-y-3">
                @if ($soon)
                    @php $days = (int) now()->startOfDay()->diffInDays($soon->starts_at->copy()->startOfDay()); @endphp
                    <li class="paper-card p-4 flex items-start gap-3">
                        <span class="w-9 h-9 rounded-full bg-[#1f1440] text-[#ffe2a8] grid place-items-center shrink-0"><x-icon name="calendar-days" class="w-[18px] h-[18px]" /></span>
                        <div class="flex-1"><p class="font-semibold leading-snug">{{ $days === 0 ? __(':event is today', ['event' => $soon->tr('title')]) : trans_choice(':event tomorrow|:event in :count days', $days, ['event' => $soon->tr('title'), 'count' => digits($days)]) }}</p>
                        <p class="text-sm text-ink-3 mt-0.5">{{ $soon->tr('time_label') }}@if ($soon->tr('place')) · {{ $soon->tr('place') }}@endif · {{ __('Check the details before the notice goes out') }}</p></div>
                        <a href="{{ route('admin.events.edit', $soon) }}" class="btn btn-line btn-sm shrink-0">{{ __('Check') }}</a>
                    </li>
                @endif

                @foreach ($upiWaiting as $payment)
                    <li class="paper-card p-4">
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-full bg-marigold-wash text-turmeric grid place-items-center shrink-0"><x-icon name="smartphone" class="w-[18px] h-[18px]" /></span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold leading-snug">{{ __('UPI payment to confirm') }} · {{ $payment->family->tr('head_name') }}</p>
                                <p class="text-sm text-ink-3 mt-0.5 num">{{ $payment->family->family_no }} · {{ $payment->monthsLabel() }}@if ($payment->upi_ref) · UTR {{ $payment->upi_ref }}@endif @if ($payment->submitted_at)· {{ local_date($payment->submitted_at, 'j M, g:i a') }}@endif</p>
                            </div>
                            <span class="font-semibold num">{{ rupees($payment->amount) }}</span>
                        </div>
                        @can('confirm', $payment)
                            <div class="flex flex-wrap gap-2 mt-3 sm:pl-12">
                                <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}">
                                    @csrf
                                    <button class="btn btn-ink btn-sm"><x-icon name="check" /><span>{{ __('Matches bank, confirm') }}</span></button>
                                </form>
                                <a href="{{ route('admin.payments.index') }}" class="btn btn-ghost btn-sm">{{ __('Not in bank yet') }}</a>
                            </div>
                        @endcan
                    </li>
                @endforeach
                @if ($upiWaitingCount > $upiWaiting->count())
                    <li><a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-sindoor hover:underline">{{ trans_choice(':count more UPI payment waiting|:count more UPI payments waiting', $upiWaitingCount - $upiWaiting->count(), ['count' => digits($upiWaitingCount - $upiWaiting->count())]) }}</a></li>
                @endif

                @if ($openEnquiries)
                    <li class="paper-card p-4 flex items-start gap-3">
                        <span class="w-9 h-9 rounded-full bg-tulsi-wash text-tulsi grid place-items-center shrink-0"><x-icon name="user-check" class="w-[18px] h-[18px]" /></span>
                        <div class="flex-1"><p class="font-semibold leading-snug">{{ trans_choice(':count website enquiry to answer|:count website enquiries to answer', $openEnquiries, ['count' => digits($openEnquiries)]) }}</p>
                        <p class="text-sm text-ink-3 mt-0.5">{{ $latestEnquiries->map(fn ($e) => $e->name.' ('.$e->kind->label().')')->implode(', ') }}</p></div>
                        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-line btn-sm shrink-0">{{ __('Review') }}</a>
                    </li>
                @endif

                @if ($owing->isNotEmpty())
                    <li class="paper-card p-4 flex items-start gap-3">
                        <span class="w-9 h-9 rounded-full bg-sindoor-wash text-sindoor grid place-items-center shrink-0"><x-icon name="bell-ring" class="w-[18px] h-[18px]" /></span>
                        <div class="flex-1"><p class="font-semibold leading-snug">{{ trans_choice(':count family owes 3 months or more|:count families owe 3 months or more', $owing->count(), ['count' => digits($owing->count())]) }}</p>
                        <p class="text-sm text-ink-3 mt-0.5">{{ $owing->take(3)->map(fn ($f) => $f->tr('head_name'))->implode(', ') }}{{ $owing->count() > 3 ? ' …' : '' }}</p></div>
                        <a href="{{ route('admin.dues.index', ['min' => 3]) }}" class="btn btn-line btn-sm shrink-0">{{ __('Remind') }}</a>
                    </li>
                @endif

                @if ($cashOut->isNotEmpty())
                    @php $holder = $cashOut->first(); @endphp
                    <li class="paper-card p-4 flex items-start gap-3">
                        <span class="w-9 h-9 rounded-full bg-paper-2 text-ink grid place-items-center shrink-0"><x-icon name="hand-coins" class="w-[18px] h-[18px]" /></span>
                        <div class="flex-1"><p class="font-semibold leading-snug">{{ __(':amount cash is still with collectors', ['amount' => rupees((int) $cashOut->sum('amount'))]) }}</p>
                        <p class="text-sm text-ink-3 mt-0.5">{{ trans_choice(':name has held chanda cash for :count day|:name has held chanda cash for :count days', max(1, (int) $holder->since->diffInDays(now())), ['name' => $holder->collector->name, 'count' => digits(max(1, (int) $holder->since->diffInDays(now())))]) }}</p></div>
                        <a href="{{ route('admin.chanda.cash') }}" class="btn btn-line btn-sm shrink-0">{{ __('Receive') }}</a>
                    </li>
                @endif

                @if ($drafts)
                    <li class="paper-card p-4 flex items-start gap-3">
                        <span class="w-9 h-9 rounded-full bg-turmeric-wash text-turmeric grid place-items-center shrink-0"><x-icon name="calendar-days" class="w-[18px] h-[18px]" /></span>
                        <div class="flex-1"><p class="font-semibold leading-snug">{{ trans_choice(':count event is still a draft|:count events are still drafts', $drafts, ['count' => digits($drafts)]) }}</p>
                        <p class="text-sm text-ink-3 mt-0.5">{{ __('Members and the website see them once they are published') }}</p></div>
                        <a href="{{ route('admin.events.index') }}" class="btn btn-line btn-sm shrink-0">{{ __('Review') }}</a>
                    </li>
                @endif

                @if ($lowStock->isNotEmpty())
                    <li class="paper-card p-4 flex items-start gap-3">
                        <span class="w-9 h-9 rounded-full bg-tulsi-wash text-tulsi grid place-items-center shrink-0"><x-icon name="sprout" class="w-[18px] h-[18px]" /></span>
                        <div class="flex-1"><p class="font-semibold leading-snug">{{ trans_choice(':count plant almost out of stock|:count plants almost out of stock', $lowStock->count(), ['count' => digits($lowStock->count())]) }}</p>
                        <p class="text-sm text-ink-3 mt-0.5">{{ $lowStock->map(fn ($p) => $p->tr('name'))->implode(', ') }}</p></div>
                        <a href="{{ route('admin.plants.index') }}" class="btn btn-line btn-sm shrink-0">{{ __('Restock') }}</a>
                    </li>
                @endif

                @if (! $needs)
                    <li class="paper-card p-6 text-center"><span class="w-11 h-11 mx-auto rounded-full bg-tulsi-wash text-tulsi grid place-items-center"><x-icon name="check-check" class="w-5 h-5" /></span><p class="font-semibold mt-3">{{ __('Nothing waiting for you') }}</p><p class="text-sm text-ink-3 mt-1">{{ __('New payments and requests will show up here.') }}</p></li>
                @endif
            </ul>
        </div>

        @if ($latest->isNotEmpty())
            {{-- Recent entries --}}
            <div class="xl:col-span-12 paper-card overflow-hidden">
                <div class="px-5 pt-4 pb-3 flex items-center border-b border-rule">
                    <h2 class="font-display text-xl">{{ __('Latest entries') }}</h2>
                    <span class="ml-auto text-sm text-ink-3">{{ __('Every entry shows who wrote it') }}</span>
                </div>
                <div class="relative overflow-x-auto"><div class="khata sm:min-w-[720px]">
                    <table class="khata-table">
                        <thead><tr>
                            <th scope="col" class="w-[56px] !px-2">{{ __('Date') }}</th>
                            <th scope="col" class="!pl-4">{{ __('Particulars') }}</th>
                            <th scope="col" class="hidden md:table-cell">{{ __('Ref.') }}</th>
                            <th scope="col" class="hidden md:table-cell">{{ __('Entered by') }}</th>
                            <th scope="col" class="!text-right">{{ __('Joma (in)') }}</th>
                            <th scope="col" class="!text-right">{{ __('Khoroch (out)') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($latest as $i => $entry)
                                @php
                                    $in = $entry->type === \App\Enums\LedgerType::Income || $entry->type === \App\Enums\LedgerType::Withdrawal;
                                    $sign = $entry->reverses_id ? '−' : '';
                                    $sameDay = $i && $latest[$i - 1]->entry_date->eq($entry->entry_date);
                                @endphp
                                <tr @if ($i && ! $sameDay) class="[&>td]:border-t [&>td]:border-t-ink/20" @endif>
                                    <td class="!px-2 text-[13px] text-ink-2 num whitespace-nowrap">{{ $sameDay ? '' : local_date($entry->entry_date, 'j M') }}</td>
                                    <td class="!pl-4"><span class="flex items-center gap-2.5"><span class="book-{{ $entry->book->code->value }} inline-block w-2.5 h-2.5 rounded-full bg-[color:var(--book)] shrink-0"></span><span class="{{ $entry->reversal ? 'line-through text-ink-3' : '' }}">{{ $entry->head->label() }}@if ($entry->note) — {{ \Illuminate\Support\Str::limit($entry->note, 48) }}@endif</span>
                                        @if ($entry->hasMedia('bill'))
                                            <a href="{{ route('admin.khata.bill', $entry) }}" target="_blank" class="text-ink-3 hover:text-ink" title="{{ __('Bill photo') }}"><x-icon name="paperclip" class="w-4 h-4" /><span class="sr-only">{{ __('Bill photo') }}</span></a>
                                        @endif
                                    </span></td>
                                    <td class="hidden md:table-cell text-[13px] text-ink-3 num whitespace-nowrap">{{ data_get($entry->source, 'receipt_no') ?? ($entry->hasMedia('bill') ? __('Bill') : '') }}</td>
                                    <td class="hidden md:table-cell text-[13px] text-ink-2 whitespace-nowrap">{{ $entry->recorder?->name ?? __('Online') }}</td>
                                    <td class="amt {{ $in ? 'text-ink font-semibold' : '' }}">{{ $in ? $sign.rupees($entry->amount) : '' }}</td>
                                    <td class="amt {{ $in ? '' : 'text-sindoor font-semibold' }}">{{ $in ? '' : $sign.rupees($entry->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div></div>
            </div>
        @endif
    </div>
</x-layouts.admin>
