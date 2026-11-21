@php($club = $site->club())
<x-layouts.admin :title="__('Receipt') . ' ' . $payment->receipt_no">
    <a href="{{ route('admin.catering.index', ['order' => $order->id]) }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ $order->customer_name }}</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-[minmax(0,560px)_1fr] items-start">
        <div>
            <h1 class="sr-only">{{ __('Receipt') }} {{ $payment->receipt_no }}</h1>
            <article class="receipt-paper relative rounded-lg bg-card border border-rule p-5 sm:p-7 overflow-hidden book-catering" aria-labelledby="rc-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-2xl text-sindoor" id="rc-title">{{ local_text($club['name'], $club['name_bn']) }}</p>
                        <p class="text-[11px] tracking-[0.16em] font-semibold text-ink-3 uppercase">{{ __('Catering receipt') }} · {{ __('Regd.') }} <span class="num">{{ $club['reg_no'] }}</span></p>
                    </div>
                    <span class="chip bg-tulsi-wash text-tulsi shrink-0"><x-icon name="check" />{{ __('Received') }}</span>
                </div>
                <dl class="mt-5 text-[15px] divide-y divide-dashed divide-ink/20 border-y border-dashed border-ink/20">
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Receipt no.') }}</dt><dd class="font-semibold num text-right">{{ $payment->receipt_no }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Date') }}</dt><dd class="text-right">{{ local_date($payment->paid_on ?? $payment->created_at) }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Received from') }}</dt><dd class="text-right">{{ $order->customer_name }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('For') }}</dt><dd class="text-right">{{ $payment->kind->label() }}: {{ __('serving on :date', ['date' => local_date($order->event_date)]) }} · <span class="num">{{ $order->number() }}</span></dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Mode') }}</dt><dd class="text-right">{{ $payment->mode->label() }}@if ($payment->upi_ref) · {{ __('UTR') }} <span class="num">{{ $payment->upi_ref }}</span>@endif</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Book') }}</dt><dd class="text-right">{{ \App\Enums\BookCode::Catering->label() }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Received by') }}</dt><dd class="text-right">{{ $payment->receiver?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Balance') }}</dt><dd class="text-right num">{{ rupees($summary['balance']) }}</dd></div>
                </dl>
                <p class="font-display text-[2.8rem] mt-4 num">{{ rupees($payment->amount) }}</p>
                <div class="absolute right-4 bottom-3 ink-stamp pointer-events-none" aria-hidden="true"><x-seal :size="116" :rotate="-8" /></div>
            </article>
        </div>
        <div class="space-y-2 lg:pt-2" data-no-print>
            <button type="button" onclick="window.print()" class="btn btn-primary w-full h-12"><x-icon name="printer" /><span>{{ __('Print') }}</span></button>
            @if ($whatsapp)
                <a target="_blank" rel="noopener" href="{{ $whatsapp }}" class="btn btn-line w-full h-12"><x-icon name="message-circle" /><span>{{ __('Send on WhatsApp') }}</span></a>
            @endif
            <a href="{{ route('admin.catering.index', ['order' => $order->id]) }}" class="btn btn-line w-full h-12"><x-icon name="chef-hat" /><span>{{ __('Back to the order') }}</span></a>
        </div>
    </div>
</x-layouts.admin>
