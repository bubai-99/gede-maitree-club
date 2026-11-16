<x-layouts.member :title="__('Receipts')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Receipts') }}</h1>
    <ul class="mt-6 space-y-3">
        @forelse ($payments as $payment)
            <li>
                <a href="{{ route('member.receipts.show', $payment) }}" class="card card-hover p-5 flex items-center gap-4">
                    <x-icon name="receipt-text" class="w-6 h-6 text-sindoor" />
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold num">{{ $payment->receipt_no }}</span>
                        <span class="block text-[14px] text-ink-2">{{ local_date($payment->paid_at) }} · {{ $payment->mode->label() }}</span>
                    </span>
                    <span class="font-display text-[1.4rem] num">{{ rupees($payment->amount) }}</span>
                </a>
            </li>
        @empty
            <li class="text-ink-2">{{ __('Nothing here yet.') }}</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $payments->links() }}</div>
</x-layouts.member>
