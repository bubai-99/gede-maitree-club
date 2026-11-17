{{-- A money receipt (member app and console). Expects $payment with family, dues and receiver loaded. --}}
@php
    $club = $site->club();
    $cancelled = $payment->status === \App\Enums\PaymentStatus::Cancelled;
@endphp
<article class="receipt-paper relative rounded-lg bg-card border border-rule p-5 sm:p-7 overflow-hidden book-club" aria-labelledby="rc-title-{{ $payment->id }}">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="font-display text-2xl text-sindoor" id="rc-title-{{ $payment->id }}">{{ local_text($club['name'], $club['name_bn']) }}</p>
            <p class="text-[11px] tracking-[0.16em] font-semibold text-ink-3 uppercase">{{ __('Money receipt') }} · {{ __('Regd.') }} <span class="num">{{ $club['reg_no'] }}</span></p>
        </div>
        @if ($cancelled)
            <span class="chip bg-ink text-white shrink-0">{{ __('Cancelled') }}</span>
        @else
            <span class="chip bg-tulsi-wash text-tulsi shrink-0"><x-icon name="check" />{{ __('Paid') }}</span>
        @endif
    </div>

    <dl class="mt-5 text-[15px] divide-y divide-dashed divide-ink/20 border-y border-dashed border-ink/20">
        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Receipt no.') }}</dt><dd class="font-semibold num text-right">{{ $payment->receipt_no }}</dd></div>
        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Date') }}</dt><dd class="text-right">{{ local_date($payment->paid_at) }}</dd></div>
        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Received from') }}</dt><dd class="text-right">{{ $payment->family->tr('head_name') }} · <span class="num">{{ $payment->family->family_no }}</span></dd></div>
        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('For') }}</dt><dd class="text-right">{{ __('Subscription') }}@if ($payment->dues->isNotEmpty()): {{ $payment->monthsLabel() }}@endif</dd></div>
        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Mode') }}</dt><dd class="text-right">{{ $payment->modeLabel() }}@if ($payment->upi_ref) · {{ __('UTR') }} <span class="num">{{ $payment->upi_ref }}</span>@endif</dd></div>
        @if ($payment->gateway_payment_id)
            <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Payment ID') }}</dt><dd class="text-right num text-[13px] break-all">{{ $payment->gateway_payment_id }}</dd></div>
        @endif
        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Book') }}</dt><dd class="text-right">{{ \App\Enums\BookCode::Club->label() }}</dd></div>
        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Received by') }}</dt><dd class="text-right">{{ $payment->receiver?->name ?? ($payment->mode === \App\Enums\PaymentMode::Online ? __('Online, through Razorpay') : '—') }}</dd></div>
        @if ($cancelled)
            <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Cancelled') }}</dt><dd class="text-right">{{ local_date($payment->cancelled_at) }} · {{ $payment->note }}</dd></div>
        @endif
    </dl>
    <p class="font-display text-[2.8rem] mt-4 num {{ $cancelled ? 'line-through text-ink-3' : '' }}">{{ rupees($payment->amount) }}</p>
    @unless ($cancelled)
        <div class="absolute right-4 bottom-3 ink-stamp pointer-events-none" aria-hidden="true"><x-seal :size="116" :rotate="-8" /></div>
    @endunless
</article>
