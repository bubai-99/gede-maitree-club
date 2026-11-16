@props(['page', 'crumb' => null])
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
    <div class="relative mx-auto max-w-[1160px] px-5 pt-20 pb-14 md:pt-24 md:pb-16">
        <nav aria-label="{{ __('Breadcrumb') }}" class="crumbs text-[14px] mb-5">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
                <li aria-hidden="true" class="text-white/50">/</li>
                <li aria-current="page" class="text-white/90">{{ $crumb ?? $content->text($page, 'title') }}</li>
            </ol>
        </nav>
        <p class="eyebrow text-marigold">{{ $content->text($page, 'kicker') }}</p>
        <h1 class="mt-3 font-display leading-[1.04] text-[clamp(2.4rem,6vw,4.2rem)] max-w-[18ch]">{{ $content->text($page, 'title') }}</h1>
        <p class="mt-4 text-[17px] sm:text-[19px] leading-relaxed text-white/85 max-w-[56ch]">{{ $content->text($page, 'intro') }}</p>
        {{ $slot }}
    </div>
    @if ($sample)
        <p class="banner-credit">{{ __('Photo') }}: <x-photo-credit :key="$sample" /></p>
    @endif
</div>
