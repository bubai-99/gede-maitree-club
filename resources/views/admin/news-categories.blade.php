@php($canEdit = auth()->user()?->can('create', \App\Models\NewsCategory::class))
<x-layouts.admin :title="__('News categories')">
    <div class="max-w-[760px]">
        <a href="{{ route('admin.news.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('News') }}</a>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('News categories') }}</h1>
        <p class="text-ink-2">{{ __('Shown as filters on the News page. Lower numbers come first.') }}</p>

        <ul class="mt-6 paper-card divide-y divide-rule">
            @forelse ($categories as $category)
                <li class="p-4">
                    <form method="POST" action="{{ route('admin.news-categories.update', $category) }}" class="grid sm:grid-cols-[1fr_1fr_90px_auto] gap-3 items-end">
                        @csrf @method('PUT')
                        <fieldset @disabled(! $canEdit) class="contents">
                            <x-field name="name" id="c-name-{{ $category->id }}" :label="__('Name (English)')" :value="$category->name" required maxlength="80" />
                            <x-field name="name_bn" id="c-bn-{{ $category->id }}" :label="__('Name (Bengali)')" :value="$category->name_bn" maxlength="80" lang="bn" />
                            <x-field name="sort" id="c-sort-{{ $category->id }}" type="number" min="0" :label="__('Order')" :value="$category->sort" />
                            @if ($canEdit)<button class="btn btn-line btn-sm h-11">{{ __('Save') }}</button>@endif
                        </fieldset>
                    </form>
                    <div class="mt-2 flex items-center gap-3 text-[13.5px] text-ink-3">
                        <span>{{ trans_choice(':count post|:count posts', $category->posts_count, ['count' => digits($category->posts_count)]) }}</span>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('admin.news-categories.destroy', $category) }}" x-data @submit="if (! confirm({{ Js::from(__('Remove this category? Its posts stay, without a category.')) }})) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <button class="text-sindoor-deep hover:underline">{{ __('Remove') }}</button>
                            </form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="p-6 text-ink-3">{{ __('No categories yet.') }}</li>
            @endforelse
        </ul>

        @if ($canEdit)
            <form method="POST" action="{{ route('admin.news-categories.store') }}" class="mt-6 paper-card p-4 grid sm:grid-cols-[1fr_1fr_90px_auto] gap-3 items-end">
                @csrf
                <x-field name="name" id="new-name" :label="__('New category (English)')" required maxlength="80" placeholder="Pujas" />
                <x-field name="name_bn" id="new-bn" :label="__('In Bengali')" maxlength="80" lang="bn" placeholder="পূজা" />
                <x-field name="sort" id="new-sort" type="number" min="0" :label="__('Order')" :value="($categories->max('sort') ?? 0) + 1" />
                <button class="btn btn-primary btn-sm h-11"><x-icon name="plus" />{{ __('Add') }}</button>
            </form>
        @endif
    </div>
</x-layouts.admin>
