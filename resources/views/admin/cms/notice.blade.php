<x-admin.form-shell :record="$record" :route-name="$routeName" :parameter="$parameter" :list-title="$listTitle" :title="$record->exists ? $record->title : __('New notice')">
    <x-admin.bilingual name="title" :label="__('Title')" :record="$record" required />
    <div class="grid sm:grid-cols-3 gap-4">
        <x-field name="slug" :label="__('Link name')" :value="$record->slug" required />
        <x-field name="tag" :label="__('Tag (English)')" :value="$record->tag" />
        <x-field name="tag_bn" :label="__('Tag (Bengali)')" :value="$record->tag_bn" lang="bn" />
    </div>
    <x-admin.bilingual name="summary" :label="__('Summary')" :record="$record" textarea rows="3" required />
    <x-admin.bilingual name="body" :label="__('More detail')" :record="$record" textarea rows="6" />
    <div class="flex flex-wrap items-end gap-6">
        <div>
            <label for="f-audience" class="label">{{ __('Who sees it') }}</label>
            <select id="f-audience" name="audience" class="field">
                @foreach (\App\Enums\NoticeAudience::cases() as $audience)
                    <option value="{{ $audience->value }}" @selected(old('audience', $record->audience?->value ?? 'public') === $audience->value)>{{ $audience->label() }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 h-11"><input type="hidden" name="pinned" value="0"><input type="checkbox" name="pinned" value="1" class="w-4 h-4 accent-sindoor" @checked(old('pinned', $record->pinned))> {{ __('Pin to the top') }}</label>
    </div>
</x-admin.form-shell>
