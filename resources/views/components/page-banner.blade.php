@props(['page', 'crumb' => null, 'kickerClass' => 'text-marigold'])
@php
    /** @var \App\Support\PageContent $content */
    $content = app(\App\Support\PageContent::class);
    $photoUrl = $content->photoUrl($page);
    $sample = $content->samplePhotoKey($page);
@endphp
{{-- Inner-page banner: photo, small heading, title and introduction, all editable in the console. --}}
<div class="banner">
    @if ($photoUrl)
        <div class="banner-media" aria-hidden="true">
            @if ($sample)
                <x-photo :key="$sample" :width="1800" eager />
            @else
                <img src="{{ $photoUrl }}" alt="" class="w-full h-full object-cover" fetchpriority="high">
            @endif
        </div>
    @endif
    <div class="banner-shade"></div>
    <div class="relative mx-auto max-w-[1160px] px-5 pt-16 pb-14 sm:pt-24 sm:pb-20 min-h-[360px] sm:min-h-[440px] flex flex-col justify-end">
        <nav class="crumbs text-[13px] mb-5" aria-label="{{ __('Breadcrumb') }}"><a href="{{ route('home') }}">{{ __('Home') }}</a><span class="mx-2 text-white/40" aria-hidden="true">/</span><span class="text-white" aria-current="page">{{ $crumb ?? $content->text($page, 'title') }}</span></nav>
        <div class="max-w-[680px]">
            <p class="eyebrow {{ $kickerClass }}">{{ $content->text($page, 'kicker') }}</p>
            <h1 class="mt-3 font-display leading-[1.05] text-[clamp(2.6rem,6.4vw,4.4rem)] text-white">{{ $content->text($page, 'title') }}</h1>
            <p class="mt-4 text-[18px] text-white/85 max-w-[54ch] leading-relaxed">{{ $content->text($page, 'intro') }}</p>
            {{ $slot }}
        </div>
    </div>
    @if ($sample)
        <p class="banner-credit">{{ __('Photo') }}: <x-photo-credit :key="$sample" /></p>
    @endif
</div>
