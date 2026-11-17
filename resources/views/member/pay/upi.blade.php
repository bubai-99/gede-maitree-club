<x-layouts.member :title="__('Pay by UPI')">
    <a href="{{ route('member.pay') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Pay dues') }}</a>
    <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Pay by UPI') }}</h1>
    <p class="text-ink-2">{{ __('Subscription') }} · {{ $payment->monthsLabel() }}</p>

    <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_400px] items-start">
        <section class="rounded-lg bg-card border border-rule p-5 text-center" aria-labelledby="upi-h">
            <h2 id="upi-h" class="text-sm text-ink-2">{{ __('To pay') }}</h2>
            <p class="font-display text-[2.6rem] leading-none mt-1 num">{{ rupees($payment->amount) }}</p>
            <a href="{{ $uri }}" class="btn btn-primary w-full h-12 mt-4 lg:hidden"><x-icon name="smartphone" /><span>{{ __('Open UPI app') }}</span></a>
            <p class="text-sm text-ink-2 mt-4 mb-2">{{ __('Or scan with any UPI app on another phone') }}</p>
            <div class="w-[220px] h-[220px] mx-auto [&>svg]:w-full [&>svg]:h-full" role="img" aria-label="{{ __('UPI QR code') }}">{!! $qr !!}</div>
            <div class="mt-3 text-sm text-ink-2" x-data="{ copied: false }">
                <span class="num select-all">{{ $upiId }}</span>
                <button type="button" class="ml-1 inline-flex items-center gap-1 font-semibold hover:text-ink" @click="navigator.clipboard?.writeText(@js($upiId)).then(() => copied = true)"><x-icon name="copy" class="w-3.5 h-3.5" /><span x-text="copied ? @js(__('Copied')) : @js(__('Copy UPI ID'))">{{ __('Copy UPI ID') }}</span></button>
            </div>
            <p class="mt-2 text-[13px] text-ink-3">{{ __('Note on the payment') }}: <span class="num">{{ $note }}</span></p>
            @if ($site->isSample())
                <p class="mt-3 text-[13px] text-sindoor-deep">{{ __('Sample UPI ID. Please don’t send money.') }}</p>
            @endif
        </section>

        <form method="POST" action="{{ route('member.pay.utr', $payment) }}" class="rounded-lg bg-card border border-rule p-5" novalidate
            x-data="{ utr: @js(old('utr', '')) }">
            @csrf
            <div class="flex items-center gap-2 mb-2"><span class="w-6 h-6 rounded-full bg-ink text-white text-[13px] font-bold grid place-items-center num">{{ digits(3) }}</span><label for="utr" class="font-semibold">{{ __('Enter the UTR') }}</label></div>
            <input id="utr" name="utr" x-model="utr" @input="utr = utr.replace(/\D/g, '').slice(0, 12)" class="field num tracking-wider" inputmode="numeric" maxlength="12" placeholder="412356789012" autocomplete="off"
                aria-describedby="utr-hint{{ $errors->has('utr') ? ' utr-error' : '' }}" @error('utr') aria-invalid="true" @enderror required />
            <x-field-error name="utr" />
            <p class="text-sm text-ink-3 mt-2" id="utr-hint">{{ __('The 12-digit number on your UPI app’s receipt (also called UPI Ref. No. or UTR). The treasurer checks it and your receipt arrives here.') }}</p>
            <button class="btn btn-ink w-full h-12 mt-3" :disabled="utr.length !== 12">{{ __('Send for confirmation') }}</button>
        </form>
    </div>
</x-layouts.member>
