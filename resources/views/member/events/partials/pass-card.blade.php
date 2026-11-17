{{-- A family pass card. Expects $pass (with event), $family and $big. The QR holds only a random token. --}}
@php
    $event = $pass->event;
    $active = $pass->isActive() && ! $event->isCancelled();
@endphp
<article class="rounded-lg {{ $active ? 'bg-ink text-paper' : 'bg-card border-2 border-dashed border-ink/30 text-ink' }} p-5">
    <div class="flex items-start gap-4">
        <div class="flex-1 min-w-0">
            <p class="text-[12px] tracking-[0.14em] font-semibold {{ $active ? 'text-marigold' : 'text-turmeric' }}">{{ __('FAMILY PASS') }}</p>
            <p class="font-display text-2xl mt-1 leading-tight">{{ $event->tr('title') }}</p>
            <p class="text-sm {{ $active ? 'text-paper/75' : 'text-ink-2' }} mt-1">{{ local_date($event->starts_at, 'l, j M') }} · {{ __(':count people', ['count' => digits($pass->people)]) }} · <span class="num">{{ $family?->family_no ?? $pass->family->family_no }}</span></p>
        </div>
        <div class="{{ $big ? 'w-32 h-32' : 'w-24 h-24' }} bg-white rounded-lg p-1.5 shrink-0 [&>svg]:w-full [&>svg]:h-full {{ $active ? '' : 'opacity-25' }}" role="img" aria-label="{{ __('Pass QR code') }}">
            {{-- SVG drawn on the server by bacon/bacon-qr-code from the pass token; no user text. --}}
            {!! \App\Support\QrCode::svg($pass->qrText(), 200) !!}
        </div>
    </div>
    <p class="text-sm {{ $active ? 'text-paper/75 border-paper/25' : 'text-ink-2 border-ink/20' }} mt-4 pt-3 border-t border-dashed">
        @if ($event->isCancelled())
            {{ __('This event has been cancelled.') }}
        @elseif (! $pass->isActive())
            {{ __('This pass was cancelled.') }}
        @elseif ($pass->used_at)
            {{ __('Used at the gate at :time.', ['time' => local_date($pass->used_at, 'g:i a')]) }}
        @else
            {{ __('Show this at the gate. One pass for the whole family.') }}
        @endif
    </p>
</article>
