<x-layouts.admin :title="__('Website')">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Website') }}</h1>
            <p class="text-ink-2">{{ __('Change the public website without code. Save as a draft, then publish when it is ready.') }}</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-line"><x-icon name="external-link" /><span>{{ __('View website') }}</span></a>
    </div>

    <ul class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($sections as $section)
            <li>
                <a href="{{ route($section['route']) }}" class="card card-hover p-5 flex items-start gap-4">
                    <span class="w-11 h-11 rounded-xl bg-sindoor-wash text-sindoor-deep grid place-items-center shrink-0"><x-icon :name="$section['icon']" class="w-5 h-5" /></span>
                    <span>
                        <span class="block font-display text-[1.3rem]">{{ $section['label'] }}</span>
                        <span class="block text-[14px] text-ink-2 num">{{ trans_choice(':count item|:count items', $section['total'], ['count' => digits($section['total'])]) }}@if ($section['drafts']) · {{ trans_choice(':count draft|:count drafts', $section['drafts'], ['count' => digits($section['drafts'])]) }}@endif</span>
                    </span>
                </a>
            </li>
        @endforeach
        <li><a href="{{ route('admin.pages.index') }}" class="card card-hover p-5 flex items-start gap-4"><span class="w-11 h-11 rounded-xl bg-sindoor-wash text-sindoor-deep grid place-items-center shrink-0"><x-icon name="file-text" class="w-5 h-5" /></span><span><span class="block font-display text-[1.3rem]">{{ __('Pages') }}</span><span class="block text-[14px] text-ink-2">{{ __('Banners and page text') }}</span></span></a></li>
        <li><a href="{{ route('admin.settings.edit') }}" class="card card-hover p-5 flex items-start gap-4"><span class="w-11 h-11 rounded-xl bg-sindoor-wash text-sindoor-deep grid place-items-center shrink-0"><x-icon name="settings" class="w-5 h-5" /></span><span><span class="block font-display text-[1.3rem]">{{ __('Club settings') }}</span><span class="block text-[14px] text-ink-2">{{ __('Contact, menu and search engines') }}</span></span></a></li>
    </ul>
</x-layouts.admin>
