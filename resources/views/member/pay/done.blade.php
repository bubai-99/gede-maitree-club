@php($status = $payment->status)
<x-layouts.member :title="__('Payment')">
    <a href="{{ route('member.pay') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Pay dues') }}</a>

    @if ($status === \App\Enums\PaymentStatus::Confirmed)
        <div class="mt-3 grid gap-6 lg:grid-cols-[minmax(0,560px)_1fr] items-start">
            <div>
                <div class="mb-4 rounded-lg bg-tulsi-wash text-tulsi p-4 flex items-center gap-3" role="status" data-no-print>
                    <x-icon name="circle-check" class="w-6 h-6 shrink-0" />
                    <p class="font-semibold">{{ __('Thank you! :amount received. Your receipt is below.', ['amount' => rupees($payment->amount)]) }}</p>
                </div>
                @include('partials.receipt', ['payment' => $payment])
            </div>
            <div class="space-y-2 lg:pt-2" data-no-print>
                <button type="button" onclick="window.print()" class="btn btn-primary w-full h-12"><x-icon name="printer" /><span>{{ __('Print or save as PDF') }}</span></button>
                <a href="{{ route('member.receipts.index') }}" class="btn btn-line w-full h-12"><x-icon name="receipt-text" /><span>{{ __('All receipts') }}</span></a>
            </div>
        </div>
    @elseif ($status === \App\Enums\PaymentStatus::AwaitingConfirmation)
        <section class="mt-3 max-w-[560px] rounded-lg border-2 border-marigold bg-marigold-wash/60 p-6" role="status">
            <x-icon name="hourglass" class="w-8 h-8 text-turmeric" />
            <h1 class="mt-2 font-display text-[1.8rem] leading-tight">{{ __('Waiting for the treasurer') }}</h1>
            <p class="mt-2 text-ink-2">{{ __('We sent your UTR to the treasurer. Your stamped receipt appears in the app once it is confirmed, usually the same evening.') }}</p>
            <p class="mt-3 text-[15px]">{{ rupees($payment->amount) }} · {{ $payment->monthsLabel() }} · {{ __('UTR') }} <span class="num">{{ $payment->upi_ref }}</span></p>
        </section>
    @elseif ($status === \App\Enums\PaymentStatus::Pending)
        <section class="mt-3 max-w-[560px] card p-6" role="status" x-data x-init="setTimeout(() => window.location.reload(), 8000)">
            <x-icon name="hourglass" class="w-8 h-8 text-turmeric" />
            <h1 class="mt-2 font-display text-[1.8rem] leading-tight">{{ __('Confirming your payment') }}</h1>
            <p class="mt-2 text-ink-2">{{ __('Razorpay has your payment and is confirming it with the bank. This usually takes a minute. Your receipt appears in the app as soon as it is done.') }}</p>
            <a href="{{ route('member.pay.done', $payment) }}" class="btn btn-line btn-sm mt-4">{{ __('Check again') }}</a>
        </section>
    @else
        <section class="mt-3 max-w-[560px] rounded-lg border border-sindoor/40 bg-sindoor-wash p-6" role="alert">
            <x-icon name="circle-alert" class="w-8 h-8 text-sindoor" />
            <h1 class="mt-2 font-display text-[1.8rem] leading-tight text-sindoor-deep">{{ $status->label() }}</h1>
            <p class="mt-2 text-ink-2">
                @if ($status === \App\Enums\PaymentStatus::Rejected)
                    {{ __('The treasurer could not find this payment in the bank.') }} {{ __('Reason') }}: {{ $payment->note }}
                @else
                    {{ __('This payment was not completed and no receipt was issued. If money left your account, it will be returned by your bank, or show this page to the treasurer.') }}
                @endif
            </p>
            <a href="{{ route('member.pay') }}" class="btn btn-primary btn-sm mt-4">{{ __('Try again') }}</a>
        </section>
    @endif
</x-layouts.member>
