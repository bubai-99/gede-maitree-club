@php
    $event = $pass->event;
    $active = $pass->isActive() && ! $event->isCancelled();
@endphp
<x-layouts.member :title="__('Pass') . ' · ' . $event->tr('title')">
    <a href="{{ route('member.events.passes') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('My passes') }}</a>
    <div class="mt-4 max-w-md mx-auto">
        <h1 class="font-display text-[1.9rem] leading-tight text-center">{{ $event->tr('title') }}</h1>
        <p class="text-center text-ink-2 mt-1">{{ local_date($event->starts_at, 'l, j F Y') }}@if ($event->tr('time_label')) · {{ $event->tr('time_label') }}@endif</p>
        <div class="mt-4 rounded-lg border-2 border-ink bg-white p-5 text-center text-ink">
            <div class="w-64 h-64 max-w-full mx-auto [&>svg]:w-full [&>svg]:h-full {{ $active ? '' : 'opacity-25' }}" role="img" aria-label="{{ __('Pass QR code') }}">
                {{-- SVG drawn on the server by bacon/bacon-qr-code from the pass token; no user text. --}}
                {!! \App\Support\QrCode::svg($pass->qrText(), 320) !!}
            </div>
            <p class="mt-3 font-display text-[1.5rem]">{{ __('FAMILY PASS') }}</p>
            <p class="text-[15px] text-ink-2 num mt-1">{{ $pass->family->family_no }} · {{ __(':count people', ['count' => digits($pass->people)]) }}</p>
            <p class="text-sm text-ink-2 mt-1">{{ $pass->family->tr('head_name') }}</p>
            @unless ($active)
                <p class="mt-3 chip bg-ink text-paper">{{ $event->isCancelled() ? __('This event has been cancelled.') : __('This pass was cancelled.') }}</p>
            @endunless
        </div>
        <p class="mt-4 text-[15px] text-ink-2 flex gap-2"><x-icon name="sun" class="w-5 h-5 shrink-0" />{{ __('Turn your screen brightness up at the gate.') }}</p>
        <p class="mt-2 text-[15px] text-ink-2 flex gap-2"><x-icon name="wifi-off" class="w-5 h-5 shrink-0" />{{ __('Passes open without internet once loaded. Show the QR at the gate.') }}</p>
    </div>
</x-layouts.member>
