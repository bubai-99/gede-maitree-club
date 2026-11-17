@php
    $club = $site->club();
    $cancelled = $receipt->isCancelled();
@endphp
<x-layouts.admin :title="__('Receipt') . ' ' . $receipt->receipt_no">
    <a href="{{ route('admin.chanda.receipts', $receipt->event) }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Chanda receipts') }}</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-[minmax(0,560px)_1fr] items-start">
        <div>
            <h1 class="sr-only">{{ __('Receipt') }} {{ $receipt->receipt_no }}</h1>
            <article class="receipt-paper relative rounded-lg bg-card border border-rule p-5 sm:p-7 overflow-hidden book-club" aria-labelledby="rc-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-2xl text-sindoor" id="rc-title">{{ local_text($club['name'], $club['name_bn']) }}</p>
                        <p class="text-[11px] tracking-[0.16em] font-semibold text-ink-3 uppercase">{{ __('Chanda receipt') }} · {{ __('Regd.') }} <span class="num">{{ $club['reg_no'] }}</span></p>
                    </div>
                    @if ($cancelled)
                        <span class="chip bg-ink text-white shrink-0">{{ __('Cancelled') }}</span>
                    @else
                        <span class="chip bg-tulsi-wash text-tulsi shrink-0"><x-icon name="check" />{{ __('Received') }}</span>
                    @endif
                </div>
                <dl class="mt-5 text-[15px] divide-y divide-dashed divide-ink/20 border-y border-dashed border-ink/20">
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Receipt no.') }}</dt><dd class="font-semibold num text-right">{{ $receipt->receipt_no }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Date') }}</dt><dd class="text-right">{{ local_date($receipt->received_at) }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Received from') }}</dt><dd class="text-right">{{ $receipt->donor_name }}@if ($receipt->area) · {{ $receipt->area->tr('name') }}@endif</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('For') }}</dt><dd class="text-right">{{ $receipt->sponsor_id ? __('Sponsorship') : __('Puja chanda') }}: {{ $receipt->event->tr('title') }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Mode') }}</dt><dd class="text-right">{{ $receipt->mode->label() }}@if ($receipt->upi_ref) · {{ __('UTR') }} <span class="num">{{ $receipt->upi_ref }}</span>@endif</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Book') }}</dt><dd class="text-right">{{ \App\Enums\BookCode::Club->label() }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Received by') }}</dt><dd class="text-right">{{ $receipt->collector?->name ?? '—' }}</dd></div>
                    @if ($cancelled)
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-ink-3 shrink-0">{{ __('Cancelled') }}</dt><dd class="text-right">{{ local_date($receipt->cancelled_at) }} · {{ $receipt->cancel_reason }}</dd></div>
                    @endif
                </dl>
                <p class="font-display text-[2.8rem] mt-4 num {{ $cancelled ? 'line-through text-ink-3' : '' }}">{{ rupees($receipt->amount) }}</p>
                @unless ($cancelled)
                    <div class="absolute right-4 bottom-3 ink-stamp pointer-events-none" aria-hidden="true"><x-seal :size="116" :rotate="-8" /></div>
                @endunless
            </article>
        </div>
        <div class="space-y-2 lg:pt-2" data-no-print>
            <button type="button" onclick="window.print()" class="btn btn-primary w-full h-12"><x-icon name="printer" /><span>{{ __('Print') }}</span></button>
            @if ($whatsapp)
                <a target="_blank" rel="noopener" href="{{ $whatsapp }}" class="btn btn-line w-full h-12"><x-icon name="message-circle" /><span>{{ __('Send on WhatsApp') }}</span></a>
            @endif
            @can('viewChanda', $receipt->event)
                <a href="{{ route('admin.chanda.create', $receipt->event) }}" class="btn btn-line w-full h-12"><x-icon name="plus" /><span>{{ __('Record another') }}</span></a>
            @endcan

            @can('cancel', $receipt)
                <details class="pt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-sindoor">{{ __('Cancel this receipt…') }}</summary>
                    <form method="POST" action="{{ route('admin.chanda.receipt.cancel', $receipt) }}" class="mt-2 space-y-2">
                        @csrf
                        <label for="cancel-reason" class="label">{{ __('Reason') }}</label>
                        <input id="cancel-reason" name="reason" class="field" required minlength="3" maxlength="200" value="{{ old('reason') }}" @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror>
                        <x-field-error name="reason" />
                        <button class="btn btn-line btn-sm">{{ __('Cancel receipt') }}</button>
                    </form>
                    <p class="mt-1 text-[13px] text-ink-3">{{ __('The number stays in the register marked cancelled, and a reversing entry goes into the khata.') }}</p>
                </details>
            @endcan
        </div>
    </div>
</x-layouts.admin>
