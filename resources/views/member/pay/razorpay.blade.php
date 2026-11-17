@php
    $text = [
        'dismissed' => __('Payment cancelled. No money was taken. You can try again, or pay in cash at the club.'),
        'failed' => __('The payment did not go through. No money was taken, or it will be returned by your bank. Please try again.'),
        'verifyFailed' => __('We could not verify this payment. If money left your account, show this page to the treasurer.'),
        'network' => __('The internet connection dropped while we were checking the payment. Open Receipts in a minute; if it is not there, tell the treasurer.'),
        'unavailable' => __('Razorpay did not load. Check your internet connection and try again.'),
    ];
@endphp
<x-layouts.member :title="__('Pay online')">
    <a href="{{ route('member.pay') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Pay dues') }}</a>

    <section class="mt-3 max-w-[560px] card p-6 sm:p-8"
        x-data="razorpayCheckout({ options: @js($checkout), verifyUrl: @js($verifyUrl), csrf: @js(csrf_token()), autoOpen: @js($autoOpen), text: @js($text) })">
        <h1 class="font-display text-[2rem] leading-tight">{{ __('Pay online') }}</h1>
        <p class="text-ink-2 mt-1">{{ __('Subscription') }} · {{ $payment->monthsLabel() }}</p>
        <p class="mt-4 font-display text-[2.8rem] leading-none num">{{ rupees($payment->amount) }}</p>
        <p class="mt-2 text-[13px] text-ink-3 num">{{ __('Reference') }} {{ $payment->reference }}</p>

        <div class="mt-5 rounded-lg p-4 text-[15px]" x-show="message" x-cloak
            :class="state === 'verifying' ? 'bg-paper-2 text-ink-2' : 'bg-sindoor-wash text-sindoor-deep'" role="alert">
            <p x-text="message"></p>
            <p class="mt-1 text-[13px] text-ink-2" x-show="detail" x-text="detail"></p>
        </div>
        <p class="mt-5 rounded-lg bg-paper-2 p-4 text-[15px] text-ink-2" x-show="state === 'verifying'" x-cloak role="status">{{ __('Checking the payment with Razorpay…') }}</p>

        <button type="button" class="btn btn-primary w-full h-12 mt-5" @click="open()" :disabled="state === 'opening' || state === 'verifying'">
            <x-icon name="credit-card" /><span>{{ __('Pay :amount', ['amount' => rupees($payment->amount)]) }}</span>
        </button>
        <p class="mt-3 text-[13px] text-ink-3">{{ __('Payments are handled by Razorpay. The club never sees your card or bank details.') }}</p>
        <noscript><p class="mt-3 text-sindoor-deep">{{ __('Online payment needs JavaScript. Please turn it on, or pay in cash at the club.') }}</p></noscript>
    </section>

    <script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
</x-layouts.member>
