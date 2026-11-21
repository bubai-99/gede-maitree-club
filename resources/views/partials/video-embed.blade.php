{{-- A video from App\Support\VideoEmbed: a player for known sites and files, or a link card. --}}
@php($label = $caption ?: __('Video on :site', ['site' => $embed->provider]))
<figure class="news-video not-prose my-7 {{ $embed->tall ? 'max-w-[380px] mx-auto' : '' }}">
    @if ($embed->type === 'iframe')
        <div class="relative w-full overflow-hidden rounded-xl bg-ink {{ $embed->tall ? 'aspect-[9/16]' : 'aspect-video' }}">
            <iframe src="{{ $embed->src }}" title="{{ $label }}" class="absolute inset-0 w-full h-full" loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
                    allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
    @elseif ($embed->type === 'video')
        <video src="{{ $embed->src }}" controls preload="metadata" playsinline class="w-full rounded-xl bg-ink {{ $embed->tall ? 'aspect-[9/16]' : 'aspect-video' }}">
            <a href="{{ $embed->url }}">{{ __('Download the video') }}</a>
        </video>
    @else
        <a href="{{ $embed->url }}" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-xl border border-rule bg-card p-4 hover:border-ink/30">
            <span class="w-12 h-12 rounded-full bg-sindoor text-white grid place-items-center shrink-0"><x-icon name="play" class="w-5 h-5" /></span>
            <span class="min-w-0"><span class="block font-semibold">{{ __('Watch the video') }}</span><span class="block text-[13.5px] text-ink-3 truncate">{{ $embed->url }}</span></span>
        </a>
    @endif
    <figcaption class="mt-2 text-[14px] text-ink-3 flex items-center gap-2">@if ($caption)<span>{{ $caption }}</span> · @endif<span>{{ $embed->provider }}</span> · <a href="{{ $embed->url }}" target="_blank" rel="noopener" class="hover:underline">{{ __('Open on the site') }}</a></figcaption>
</figure>
