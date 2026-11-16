@props(['record', 'routeName', 'parameter', 'listTitle', 'title', 'files' => false, 'publishable' => true])
@php
    $exists = $record->exists;
    $canEdit = $exists ? auth()->user()?->can('update', $record) : auth()->user()?->can('create', get_class($record));
    $action = $exists ? route($routeName.'.update', [$parameter => $record]) : route($routeName.'.store');
@endphp
<x-layouts.admin :title="$listTitle">
    <div class="max-w-[880px]">
        <a href="{{ route($routeName.'.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ $listTitle }}</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="font-display text-[2rem] leading-tight">{{ $title }}</h1>
            @if ($exists && $publishable)
                <x-admin.status :published="$record->isPublished()" />
            @endif
        </div>
        @unless ($canEdit)
            <p class="mt-3 rounded-lg bg-marigold-wash text-turmeric px-4 py-3 text-[14px]">{{ __('You can look but not change this. Only the Admin and the Website editor edit the website.') }}</p>
        @endunless

        <form method="POST" action="{{ $action }}" @if ($files) enctype="multipart/form-data" @endif class="mt-6">
            @csrf
            @if ($exists) @method('PUT') @endif
            <fieldset @disabled(! $canEdit) class="space-y-6">
                {{ $slot }}
            </fieldset>
            @if ($canEdit)
                <div class="mt-8 flex flex-wrap gap-2 sticky bottom-20 lg:bottom-4 bg-paper/95 py-3">
                    <button type="submit" name="action" value="draft" class="btn btn-line">{{ __('Save as draft') }}</button>
                    <button type="submit" name="action" value="publish" class="btn btn-primary">{{ __('Save and publish') }}</button>
                </div>
            @endif
        </form>

        @if ($exists && $canEdit)
            <div class="mt-6 flex flex-wrap gap-2 border-t border-rule pt-6">
                @if ($publishable && $record->isPublished())
                    <form method="POST" action="{{ route($routeName.'.unpublish', [$parameter => $record]) }}">@csrf @method('DELETE')<button class="btn btn-line btn-sm">{{ __('Take off the website') }}</button></form>
                @endif
                <form method="POST" action="{{ route($routeName.'.destroy', [$parameter => $record]) }}" x-data @submit="if (! confirm({{ Js::from(__('Delete this for good?')) }})) $event.preventDefault()">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm text-sindoor-deep"><x-icon name="trash-2" /><span>{{ __('Delete') }}</span></button>
                </form>
            </div>
        @endif
    </div>
</x-layouts.admin>
