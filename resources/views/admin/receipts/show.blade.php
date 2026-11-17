<x-layouts.admin :title="__('Receipt') . ' ' . $payment->receipt_no">
    <a href="{{ route('admin.receipts.index') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Receipts register') }}</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-[minmax(0,560px)_1fr] items-start">
        <div>
            <h1 class="sr-only">{{ __('Receipt') }} {{ $payment->receipt_no }}</h1>
            @include('partials.receipt', ['payment' => $payment])
        </div>
        <div class="space-y-2 lg:pt-2" data-no-print>
            <button type="button" onclick="window.print()" class="btn btn-primary w-full h-12"><x-icon name="printer" /><span>{{ __('Print') }}</span></button>
            @if ($whatsapp && $payment->isPaid())
                <a target="_blank" rel="noopener" href="{{ $whatsapp }}" class="btn btn-line w-full h-12"><x-icon name="message-circle" /><span>{{ __('Resend on WhatsApp') }}</span></a>
            @endif
            <a href="{{ route('admin.dues.family', $payment->family) }}" class="btn btn-line w-full h-12"><x-icon name="users" /><span>{{ __('Family dues') }}</span></a>

            @can('cancel', $payment)
                <details class="pt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-sindoor">{{ __('Cancel this receipt…') }}</summary>
                    <form method="POST" action="{{ route('admin.payments.cancel', $payment) }}" class="mt-2 space-y-2">
                        @csrf
                        <label for="cancel-reason" class="label">{{ __('Reason') }}</label>
                        <input id="cancel-reason" name="reason" class="field" required minlength="3" maxlength="200" value="{{ old('reason') }}" @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror>
                        <x-field-error name="reason" />
                        @if ($payment->mode === \App\Enums\PaymentMode::Online)
                            <p class="text-[13px] text-sindoor-deep">{{ __('This was paid online. Refund the money in the Razorpay Dashboard first; cancelling here only corrects the club’s books.') }}</p>
                        @endif
                        <button class="btn btn-line btn-sm">{{ __('Cancel receipt') }}</button>
                    </form>
                    <p class="mt-1 text-[13px] text-ink-3">{{ __('The number stays in the register marked cancelled, and a reversing entry goes into the khata.') }}</p>
                </details>
            @endcan
        </div>
    </div>
</x-layouts.admin>
