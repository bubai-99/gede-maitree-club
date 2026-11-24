@props(['dark' => false, 'long' => false, 'variant' => null])
@php
    $current = app()->getLocale();
    if ($variant === 'app') {
        // Member app top bar: grey pill, white pressed key
        $wrap = 'flex items-center rounded-md bg-paper-2 p-0.5';
        $base = 'px-2.5 h-8 rounded text-sm font-semibold text-ink-2 grid place-items-center aria-[current=true]:bg-white aria-[current=true]:text-ink';
        // Phones: "বাং", like the website header; wider screens: "বাংলা"
        $labels = ['EN', '<span class="sm:hidden">বাং</span><span class="hidden sm:inline">বাংলা</span>'];
    } else {
        $wrap = 'flex items-center rounded-md border p-0.5 text-[13px] '.($dark ? 'border-white/15' : 'border-rule');
        $base = $dark
            ? 'px-3 h-8 rounded font-semibold text-white/70 grid place-items-center aria-[current=true]:bg-white aria-[current=true]:text-ink'
            : 'px-2 h-7 rounded font-semibold text-ink-2 grid place-items-center aria-[current=true]:bg-ink aria-[current=true]:text-paper';
        $labels = $long ? ['English', 'বাংলা'] : ['EN', 'বাং'];
        $labels = array_map('e', $labels);
    }
@endphp
<div {{ $attributes->merge(['class' => $wrap]) }} role="group" aria-label="{{ __('Language') }}">
    <a href="{{ route('locale.switch', 'en') }}" rel="nofollow" lang="en" hreflang="en" class="{{ $base }}" aria-current="{{ $current === 'en' ? 'true' : 'false' }}">{!! $labels[0] !!}</a>
    <a href="{{ route('locale.switch', 'bn') }}" rel="nofollow" lang="bn" hreflang="bn" class="{{ $base }}" aria-current="{{ $current === 'bn' ? 'true' : 'false' }}">{!! $labels[1] !!}</a>
</div>
