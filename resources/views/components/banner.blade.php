@props(['photo' => null, 'eyebrow' => null, 'title', 'lead' => null, 'crumbs' => []])
{{-- Inner-page banner: photo behind a shade, eyebrow, title and lead (like the prototype's page banners). --}}
<div {{ $attributes->merge(['class' => 'banner']) }}>
    @if ($photo)
        <div class="banner-media" aria-hidden="true"><x-photo :key="$photo" :width="1800" eager /></div>
    @endif
    <div class="banner-shade"></div>
    <div class="relative mx-auto max-w-[1160px] px-5 pt-24 pb-14 md:pt-28 md:pb-16">
        @if ($crumbs)
            <nav aria-label="{{ __('Breadcrumb') }}" class="crumbs text-[14px] mb-4">
                <ol class="flex flex-wrap items-center gap-1.5">
                    @foreach ($crumbs as $label => $url)
                        <li class="flex items-center gap-1.5"><a href="{{ $url }}">{{ $label }}</a><x-icon name="chevron-right" class="w-3.5 h-3.5 text-white/50" /></li>
                    @endforeach
                </ol>
            </nav>
        @endif
        @if ($eyebrow)
            <p class="eyebrow text-marigold">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-3 font-display leading-[1.04] text-[clamp(2.4rem,6vw,4.2rem)] max-w-[18ch]">{{ $title }}</h1>
        @if ($lead)
            <p class="mt-4 text-[17px] sm:text-[19px] leading-relaxed text-white/85 max-w-[56ch]">{{ $lead }}</p>
        @endif
        {{ $slot }}
    </div>
    @if ($photo)
        <x-photo-credit :key="$photo" class="banner-credit" />
    @endif
</div>
