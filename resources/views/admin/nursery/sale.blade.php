@php
    $club = $site->club();
    $cancelled = $sale->isCancelled();
@endphp
<x-layouts.admin :title="__('Receipt') . ' ' . $sale->receipt_no">
    <a href="{{ route('admin.nursery.index') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Nursery') }}</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-[minmax(0,560px)_1fr] items-start">
        <div>
            <h1 class="sr-only">{{ __('Receipt') }} {{ $sale->receipt_no }}</h1>
            <article class="receipt-paper relative rounded-lg bg-card border border-rule p-5 sm:p-7 overflow-hidden book-nursery" aria-labelledby="rc-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-2xl text-sindoor" id="rc-title">{{ local_text($club['name'], $club['name_bn']) }}</p>
                        <p class="text-[11px] tracking-[0.16em] font-semibold text-ink-3 uppercase">{{ __('Nursery receipt') }} · {{ __('Regd.') }} <span class="num">{{ $club['reg_no'] }}</span></p>
                    </div>
                    @if ($cancelled)
                        <span class="chip bg-ink text-white shrink-0">{{ __('Cancelled') }}</span>
                    @else
                        <span class="chip bg-tulsi-wash text-tulsi shrink-0"><x-icon name="check" />{{ __('Received') }}</span>
                    @endif
                </div>
                <dl class="mt-5 text-[15px] divide-y divide-dashed divide-ink/20 border-y border-dashed border-ink/20">
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Receipt no.') }}</dt><dd class="font-semibold num text-right">{{ $sale->receipt_no }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Date') }}</dt><dd class="text-right">{{ local_date($sale->created_at, 'j M Y, g:i a') }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Buyer') }}</dt><dd class="text-right">{{ $sale->buyer_name ?: '—' }}@if ($sale->family) · <span class="num">{{ $sale->family->family_no }}</span>@endif</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Mode') }}</dt><dd class="text-right">{{ $sale->mode->label() }}@if ($sale->upi_ref) · {{ __('UTR') }} <span class="num">{{ $sale->upi_ref }}</span>@endif</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Received by') }}</dt><dd class="text-right">{{ $sale->recorder?->name ?? '—' }}</dd></div>
                    @if ($cancelled)
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Cancelled') }}</dt><dd class="text-right">{{ local_date($sale->cancelled_at) }} · {{ $sale->cancel_reason }}</dd></div>
                    @endif
                </dl>
                <table class="w-full text-[15px] mt-4">
                    @foreach ($sale->items as $item)
                        <tr><td class="py-1">{{ $item->plant->tr('name') }}</td><td class="text-right num text-ink-2">{{ digits($item->qty) }} × {{ rupees($item->rate) }}</td><td class="text-right num w-24">{{ rupees($item->qty * $item->rate) }}</td></tr>
                    @endforeach
                    @if ($sale->discount)
                        <tr class="text-ink-2"><td class="py-1" colspan="2">{{ __('Member discount') }}</td><td class="text-right num">−{{ rupees($sale->discount) }}</td></tr>
                    @endif
                </table>
                <p class="font-display text-[2.8rem] mt-3 num {{ $cancelled ? 'line-through text-ink-3' : '' }}">{{ rupees($sale->total) }}</p>
                @unless ($cancelled)
                    <div class="absolute right-4 bottom-3 ink-stamp pointer-events-none" aria-hidden="true"><x-seal :size="116" :rotate="-8" /></div>
                @endunless
            </article>
        </div>
        <div class="space-y-2 lg:pt-2" data-no-print>
            <button type="button" onclick="window.print()" class="btn btn-primary w-full h-12"><x-icon name="printer" /><span>{{ __('Print') }}</span></button>
            <a href="{{ route('admin.nursery.index') }}" class="btn btn-line w-full h-12"><x-icon name="plus" /><span>{{ __('New sale') }}</span></a>
            @unless ($cancelled)
                <details class="pt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-sindoor">{{ __('Cancel this sale…') }}</summary>
                    <form method="POST" action="{{ route('admin.nursery.cancel', $sale) }}" class="mt-2 space-y-2">
                        @csrf
                        <x-field name="reason" :label="__('Reason')" required minlength="3" maxlength="200" />
                        <button class="btn btn-line btn-sm">{{ __('Cancel sale') }}</button>
                    </form>
                    <p class="mt-1 text-[13px] text-ink-3">{{ __('The number stays, the plants go back into stock, and a reversing entry goes into the khata.') }}</p>
                </details>
            @endunless
        </div>
    </div>
</x-layouts.admin>
