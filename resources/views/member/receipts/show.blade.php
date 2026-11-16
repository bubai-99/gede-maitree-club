@php($club = $site->club())
<x-layouts.member :title="__('Receipt')">
    <a href="{{ route('member.receipts.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Receipts') }}</a>

    <article class="mt-4 max-w-[560px] card p-6 sm:p-8 relative overflow-hidden" aria-labelledby="receipt-h">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="font-display text-[1.4rem] text-sindoor">{{ local_text($club['name'], $club['name_bn']) }}</p>
                <p class="text-[12.5px] text-ink-3 num">{{ __('Reg. No.') }} {{ $club['reg_no'] }}</p>
            </div>
            <x-seal :size="64" aria-hidden="true" class="ink-stamp shrink-0" />
        </div>
        <h1 id="receipt-h" class="mt-6 text-[13px] font-semibold uppercase tracking-wide text-ink-3">{{ __('Receipt') }}</h1>
        <p class="font-display text-[1.8rem] num">{{ $payment->receipt_no }}</p>

        <dl class="mt-5 grid grid-cols-[9rem_1fr] gap-y-2 text-[15px]">
            <dt class="text-ink-2">{{ __('Date') }}</dt><dd class="num">{{ local_date($payment->paid_at) }}</dd>
            <dt class="text-ink-2">{{ __('Family') }}</dt><dd class="num">{{ $payment->family->family_no }} · {{ $payment->family->tr('head_name') }}</dd>
            <dt class="text-ink-2">{{ __('Months') }}</dt><dd>{{ $payment->dues->map(fn ($due) => local_date($due->period, 'M Y'))->implode(', ') ?: '—' }}</dd>
            <dt class="text-ink-2">{{ __('Mode') }}</dt><dd>{{ $payment->mode->label() }}@if ($payment->upi_ref) · <span class="num">{{ $payment->upi_ref }}</span>@endif</dd>
            @if ($payment->receiver)
                <dt class="text-ink-2">{{ __('Received by') }}</dt><dd>{{ $payment->receiver->name }}</dd>
            @endif
            <dt class="text-ink-2">{{ __('Status') }}</dt><dd>{{ $payment->status->label() }}</dd>
        </dl>
        <p class="mt-6 pt-4 border-t border-rule leader font-display text-[1.6rem]"><span>{{ __('Amount') }}</span><span class="num">{{ rupees($payment->amount) }}</span></p>
        <button type="button" onclick="window.print()" class="mt-6 btn btn-line btn-sm" data-no-print><x-icon name="printer" /><span>{{ __('Print') }}</span></button>
    </article>
</x-layouts.member>
