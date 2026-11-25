@php
    $labels = ['paid' => __('Paid'), 'due' => __('Due'), 'wait' => __('Checking'), 'next' => __('Upcoming'), 'waived' => __('Waived'), 'none' => __('Not billed')];
    $tones = [
        'paid' => 'bg-tulsi-wash text-tulsi border-tulsi/20',
        'due' => 'bg-sindoor-wash text-sindoor-deep border-sindoor/30',
        'wait' => 'bg-marigold-wash text-turmeric border-marigold/40',
        'next' => 'bg-card text-ink-3 border-rule border-dashed',
        'waived' => 'bg-paper-2 text-ink-2 border-rule',
        'none' => 'bg-card text-ink-3 border-rule',
    ];
@endphp
<x-layouts.member :title="__('Statement')">
    {{-- Filters and pages swap in place (resources/js/instant.js) --}}
    <div id="member-dues" data-instant class="instant-region">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Statement') }}</h1>
            <p class="text-ink-2 num">{{ __('Subscription for family :no, month by month.', ['no' => $family->family_no]) }}</p>
        </div>
        <button type="button" onclick="window.print()" class="btn btn-line btn-sm" data-no-print><x-icon name="printer" /><span>{{ __('Print or save as PDF') }}</span></button>
    </div>

    <form method="GET" class="mt-5 flex flex-wrap items-center gap-2" data-no-print>
        <label for="fy" class="text-sm text-ink-2">{{ __('Financial year') }}</label>
        <select id="fy" name="fy" class="field h-10 w-auto" onchange="this.form.requestSubmit()">
            @foreach ($years as $year)
                <option value="{{ $year }}" @selected($fy === $year)>{{ local_date(\Illuminate\Support\Carbon::create($year, 4, 1), 'M Y') }} – {{ local_date(\Illuminate\Support\Carbon::create($year + 1, 3, 1), 'M Y') }}</option>
            @endforeach
        </select>
        <noscript><button class="btn btn-line h-10">{{ __('Show') }}</button></noscript>
    </form>

    <div class="mt-5 grid grid-cols-3 gap-2 sm:gap-3">
        <div class="rounded-lg border border-rule bg-card p-3 sm:p-4"><p class="text-[13px] text-ink-3">{{ __('Paid') }}</p><p class="font-display text-[1.5rem] sm:text-[1.9rem] leading-tight text-tulsi num">{{ rupees($paid) }}</p></div>
        <div class="rounded-lg border border-rule bg-card p-3 sm:p-4"><p class="text-[13px] text-ink-3">{{ __('Due') }}</p><p class="font-display text-[1.5rem] sm:text-[1.9rem] leading-tight num {{ $owed ? 'text-sindoor-deep' : '' }}">{{ rupees($owed) }}</p></div>
        <div class="rounded-lg border border-rule bg-card p-3 sm:p-4"><p class="text-[13px] text-ink-3">{{ __('Still to come') }}</p><p class="font-display text-[1.5rem] sm:text-[1.9rem] leading-tight text-ink-2 num">{{ rupees($upcoming) }}</p></div>
    </div>

    <h2 class="sr-only">{{ __('Months') }}</h2>
    <ol class="mt-4 grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-2">
        @foreach ($rows as $row)
            <li class="rounded-lg border {{ $tones[$row['state']] }} px-3 py-2.5">
                <p class="text-[13px] font-semibold">{{ local_date($row['month'], 'M Y') }}</p>
                <p class="text-[12.5px] mt-0.5 flex items-center gap-1">@if ($row['state'] === 'paid')<x-icon name="check" class="w-3.5 h-3.5" />@endif{{ $labels[$row['state']] }}</p>
            </li>
        @endforeach
    </ol>

    <section class="mt-6 rounded-lg border border-rule bg-card" aria-labelledby="pay-h">
        <h2 id="pay-h" class="px-4 pt-4 font-display text-[1.4rem]">{{ __('Payments this year') }}</h2>
        @if ($payments->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="khata-table mt-2 min-w-[520px]">
                    <thead><tr><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Receipt') }}</th><th scope="col">{{ __('Months') }}</th><th scope="col">{{ __('Mode') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="whitespace-nowrap">{{ local_date($payment->paid_at, 'j M Y') }}</td>
                                <td><a href="{{ route('member.receipts.show', $payment) }}" class="font-semibold num hover:underline">{{ $payment->receipt_no }}</a>@if ($payment->status === \App\Enums\PaymentStatus::Cancelled) <span class="chip bg-ink text-white">{{ __('Cancelled') }}</span>@endif</td>
                                <td>{{ $payment->monthsLabel() ?: '—' }}</td>
                                <td>{{ $payment->modeLabel() }}</td>
                                <td class="amt font-semibold">{{ rupees($payment->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="p-4 text-ink-2">{{ __('No payments yet this year.') }}</p>
        @endif
    </section>

    @if ($outstanding > 0)
        <a href="{{ route('member.pay') }}" class="btn btn-primary w-full sm:w-auto h-12 mt-5" data-no-print><x-icon name="indian-rupee" /><span>{{ __('Pay :amount due', ['amount' => rupees($outstanding)]) }}</span></a>
    @endif
    <a href="{{ route('member.receipts.index') }}" class="mt-4 ml-1 inline-flex items-center gap-1 text-[14px] font-semibold text-sindoor hover:underline" data-no-print>{{ __('Receipts') }}<x-icon name="arrow-right" class="w-4 h-4" /></a>
    </div>
</x-layouts.member>
