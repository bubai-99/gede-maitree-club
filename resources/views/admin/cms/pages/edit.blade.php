@php($canEdit = auth()->user()?->can('create', \App\Models\Page::class))
<x-layouts.admin :title="__('Pages')">
    <div class="max-w-[960px]">
        <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Pages') }}</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="font-display text-[2rem] leading-tight">{{ __($definition['label']) }}</h1>
            @if ($page->hasUnpublishedChanges())
                <span class="chip bg-marigold-wash text-turmeric">{{ __('Unpublished changes') }}</span>
            @endif
        </div>
        @unless ($canEdit)
            <p class="mt-3 rounded-lg bg-marigold-wash text-turmeric px-4 py-3 text-[14px]">{{ __('You can look but not change this. Only the Admin and the Website editor edit the website.') }}</p>
        @endunless

        <form method="POST" action="{{ route('admin.pages.update', $key) }}" enctype="multipart/form-data" class="mt-6">
            @csrf
            @method('PUT')
            <fieldset @disabled(! $canEdit) class="space-y-5">
                @foreach ($fields as $name => $field)
                    <div class="card p-5">
                        <p class="font-semibold">{{ __($field['label']) }}</p>
                        <div class="mt-3 grid sm:grid-cols-2 gap-4">
                            <x-field :name="'fields['.$name.']'" :id="'f-'.$name" label="English" :value="$field['value']" :textarea="! empty($field['long'])" rows="3" />
                            <x-field :name="'fields['.$name.'_bn]'" :id="'f-'.$name.'-bn'" label="বাংলা" :value="$field['value_bn']" :textarea="! empty($field['long'])" rows="3" lang="bn" />
                        </div>
                    </div>
                @endforeach
                @if ($definition['photo'] !== '' || $page->exists)
                    <x-admin.photo-field :record="$page" />
                @endif
            </fieldset>
            @if ($canEdit)
                <div class="mt-8 flex flex-wrap gap-2 sticky bottom-20 lg:bottom-4 bg-paper/95 py-3">
                    <button type="submit" name="action" value="draft" class="btn btn-line">{{ __('Save as draft') }}</button>
                    <button type="submit" name="action" value="publish" class="btn btn-primary">{{ __('Save and publish') }}</button>
                </div>
            @endif
        </form>
    </div>
</x-layouts.admin>
