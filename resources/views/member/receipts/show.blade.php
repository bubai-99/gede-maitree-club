<x-layouts.member :title="__('Receipt')">
    <a href="{{ route('member.receipts.index') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Receipts') }}</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-[minmax(0,560px)_1fr] items-start">
        <div>
            <h1 class="sr-only">{{ __('Receipt') }} {{ $payment->receipt_no }}</h1>
            @include('partials.receipt', ['payment' => $payment])
        </div>
        <div class="space-y-2 lg:pt-2" data-no-print>
            <button type="button" onclick="window.print()" class="btn btn-primary w-full h-12"><x-icon name="printer" /><span>{{ __('Print or save as PDF') }}</span></button>
            <p class="text-[13px] text-ink-2 pt-1">{{ __('Choose “Save as PDF” in the print dialog to keep a copy. Something wrong on this receipt? Tell the treasurer.') }}</p>
        </div>
    </div>
</x-layouts.member>
