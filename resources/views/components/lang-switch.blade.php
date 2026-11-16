@props(['dark' => false, 'long' => false])
@php
    $base = $dark
        ? 'px-3 h-8 rounded font-semibold text-white/70 grid place-items-center aria-[current=true]:bg-white aria-[current=true]:text-ink'
        : 'px-2 h-7 rounded font-semibold text-ink-2 grid place-items-center aria-[current=true]:bg-ink aria-[current=true]:text-paper';
    $current = app()->getLocale();
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center rounded-md border p-0.5 text-[13px] '.($dark ? 'border-white/15' : 'border-rule')]) }} role="group" aria-label="{{ __('Language') }}">
    <a href="{{ route('locale.switch', 'en') }}" rel="nofollow" lang="en" hreflang="en" class="{{ $base }}" aria-current="{{ $current === 'en' ? 'true' : 'false' }}">{{ $long ? 'English' : 'EN' }}</a>
    <a href="{{ route('locale.switch', 'bn') }}" rel="nofollow" lang="bn" hreflang="bn" class="{{ $base }}" aria-current="{{ $current === 'bn' ? 'true' : 'false' }}">{{ $long ? 'বাংলা' : 'বাং' }}</a>
</div>
