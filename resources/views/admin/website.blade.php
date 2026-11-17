<x-layouts.admin :title="__('Website')">
    <div class="flex flex-wrap items-end gap-4 mb-6">
        <div class="min-w-0">
            <h1 class="font-display text-[2.1rem] leading-tight">{{ __('Website') }}</h1>
            <p class="text-[15px] text-ink-2 mt-1">{{ __('Change the public website without code. Save as a draft, then publish when it is ready.') }}</p>
        </div>
        <div class="ml-auto flex flex-wrap gap-2">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-icon name="globe" /><span>{{ __('Open website') }}</span></a>
        </div>
    </div>

    <div class="grid lg:grid-cols-[220px_minmax(0,1fr)] gap-6 items-start">
        {{-- Content: every part of the site the committee can edit --}}
        <nav class="paper-card p-2 lg:sticky lg:top-24" aria-label="{{ __('Content') }}">
            <p class="px-3 pt-2 pb-1.5 text-[11.5px] font-bold uppercase tracking-[0.12em] text-ink-3">{{ __('Content') }}</p>
            <a href="{{ route('admin.website') }}" aria-current="page" class="flex items-center gap-2.5 h-10 px-3 rounded-lg text-[15px] text-ink-2 hover:bg-paper-2 aria-[current=page]:bg-ink aria-[current=page]:text-paper aria-[current=page]:font-semibold">
                <x-icon name="file-text" class="w-[18px] h-[18px]" />{{ __('Pages') }}<span class="ml-auto text-[12.5px] opacity-75 num">{{ digits(count($definitions)) }}</span>
            </a>
            @foreach ($sections as $section)
                <a href="{{ route($section['route']) }}" class="flex items-center gap-2.5 h-10 px-3 rounded-lg text-[15px] text-ink-2 hover:bg-paper-2">
                    <x-icon :name="$section['icon']" class="w-[18px] h-[18px]" />{{ $section['label'] }}<span class="ml-auto text-[12.5px] text-ink-3 num">{{ digits($section['total']) }}</span>
                </a>
            @endforeach
            <a href="{{ route('admin.settings.edit') }}" class="flex items-center gap-2.5 h-10 px-3 rounded-lg text-[15px] text-ink-2 hover:bg-paper-2">
                <x-icon name="phone" class="w-[18px] h-[18px]" />{{ __('Contact, menu & SEO') }}
            </a>
            <div class="mx-3 mt-2 pt-3 pb-2 border-t border-rule text-[12.5px] text-ink-3">
                <p class="flex items-center gap-1.5 font-semibold text-ink-2"><x-icon name="circle-help" class="w-3.5 h-3.5" />{{ __('How it works') }}</p>
                <p class="mt-1">{{ __('Save draft keeps your work private. Publish puts it on the website.') }}</p>
            </div>
        </nav>

        <div class="min-w-0 space-y-6">
            <section class="paper-card overflow-hidden" aria-labelledby="pages-h">
                <div class="px-5 pt-4 pb-3">
                    <h2 id="pages-h" class="font-display text-xl">{{ __('Pages') }}</h2>
                    <p class="text-[13.5px] text-ink-2">{{ __('The fixed pages of the website. Open one to edit its sections.') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="khata-table min-w-[560px]">
                        <thead><tr><th scope="col">{{ __('Page') }}</th><th scope="col">{{ __('Sections') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Last published') }}</th></tr></thead>
                        <tbody>
                            @foreach ($definitions as $key => $definition)
                                @php $page = $pages->get($key); @endphp
                                <tr>
                                    <td><a href="{{ route('admin.pages.edit', $key) }}" class="flex items-center gap-2.5 font-semibold hover:underline"><x-icon name="file-text" class="w-4 h-4 text-ink-3" />{{ __($definition['label']) }}</a></td>
                                    <td class="text-[14px] text-ink-2">{{ trans_choice(':count field|:count fields', count($definition['fields']), ['count' => digits(count($definition['fields']))]) }}</td>
                                    <td>
                                        @if ($page?->hasUnpublishedChanges())
                                            <span class="chip bg-marigold-wash text-turmeric">{{ __('Unpublished changes') }}</span>
                                        @elseif ($page?->blocks)
                                            <x-admin.status :published="true" />
                                        @else
                                            <span class="chip bg-paper-2 text-ink-2">{{ __('Default text') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-[13px] text-ink-3 num">{{ $page?->published_at ? local_date($page->published_at, 'j M Y') : __('Original text') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @php $drafts = collect($sections)->where('drafts', '>', 0); @endphp
            @if ($drafts->isNotEmpty())
                <section class="paper-card p-5" aria-labelledby="drafts-h">
                    <h2 id="drafts-h" class="font-display text-xl">{{ __('Waiting to be published') }}</h2>
                    <ul class="mt-3 divide-y divide-rule">
                        @foreach ($drafts as $section)
                            <li class="py-2.5 flex items-center justify-between gap-3"><span class="flex items-center gap-2.5"><x-icon :name="$section['icon']" class="w-4 h-4 text-ink-3" />{{ $section['label'] }}</span><a href="{{ route($section['route']) }}" class="text-sm font-semibold text-sindoor hover:underline">{{ trans_choice(':count draft|:count drafts', $section['drafts'], ['count' => digits($section['drafts'])]) }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</x-layouts.admin>
