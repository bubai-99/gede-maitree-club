<x-layouts.admin :title="__('Pages')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Pages') }}</h1>
    <p class="mt-1 text-ink-2">{{ __('Headings, introductions and banner photos of each page, in English and Bengali.') }}</p>
    <ul class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($definitions as $key => $definition)
            @php($page = $pages->get($key))
            <li class="card p-5">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-display text-[1.3rem]">{{ __($definition['label']) }}</h2>
                    @if ($page?->hasUnpublishedChanges())
                        <span class="chip bg-marigold-wash text-turmeric">{{ __('Unpublished changes') }}</span>
                    @elseif ($page?->blocks)
                        <x-admin.status :published="true" />
                    @else
                        <span class="chip bg-paper-2 text-ink-2">{{ __('Default text') }}</span>
                    @endif
                </div>
                <p class="mt-1 text-[14px] text-ink-3">{{ trans_choice(':count field|:count fields', count($definition['fields']), ['count' => digits(count($definition['fields']))]) }}</p>
                <a href="{{ route('admin.pages.edit', $key) }}" class="mt-4 btn btn-line btn-sm">@can('create', \App\Models\Page::class){{ __('Edit') }}@else{{ __('View') }}@endcan</a>
            </li>
        @endforeach
    </ul>
</x-layouts.admin>
