@php
    $videoText = old('videos', collect($record->videos ?? [])->map(fn ($v) => trim(($v['url'] ?? '').(! empty($v['caption']) ? ' | '.$v['caption'] : '')))->join("\n"));
    $images = $record->exists ? $record->getMedia('images') : collect();
@endphp
<x-admin.form-shell :record="$record" :route-name="$routeName" :parameter="$parameter" :list-title="$listTitle" :title="$record->exists ? $record->title : __('New news post')" files>
    <x-admin.bilingual name="title" :label="__('Title')" :record="$record" required />
    <div class="grid sm:grid-cols-2 gap-4">
        <x-field name="slug" :label="__('Link name')" :value="$record->slug" required :hint="__('Letters, numbers and dashes, e.g. durga-puja-2026')" />
        <div>
            <label for="f-category_id" class="label">{{ __('Category') }}</label>
            <select id="f-category_id" name="category_id" class="field">
                <option value="">{{ __('No category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $record->category_id) === $category->id)>{{ $category->tr('name') }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-[13px] text-ink-3"><a href="{{ route('admin.news-categories.index') }}" class="underline">{{ __('Add or rename categories') }}</a></p>
        </div>
    </div>
    <x-admin.bilingual name="summary" :label="__('Summary')" :record="$record" textarea :rows="2" required :hint="__('One or two sentences, shown in the list and at the top of the story.')" />

    <div class="card p-5 space-y-3">
        <p class="font-semibold">{{ __('The story') }}</p>
        <p class="text-[13.5px] text-ink-3">{{ __('Write as usual. ## makes a heading, **bold**, - starts a list, > a quote. Paste a YouTube, Facebook, Instagram, Vimeo or Google Drive video link on a line of its own and the video shows right there.') }}</p>
        <x-admin.bilingual name="body" :label="__('Text')" :record="$record" textarea :rows="14" />
    </div>

    <div class="card p-5">
        <x-field name="videos" :label="__('Videos')" textarea :rows="4" :value="$videoText" placeholder="https://youtu.be/… | Dhunuchi naach on Navami&#10;https://www.facebook.com/…/videos/… | Bijoya" :hint="__('One link per line: YouTube, Facebook, Instagram, Vimeo, Google Drive, Dailymotion, or a .mp4 file. Add a caption after a |. They show below the story.')" />
    </div>

    <x-admin.photo-field :record="$record" />

    <div class="card p-5">
        <p class="label">{{ __('More photos') }}</p>
        @if ($images->isNotEmpty())
            <ul class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-3">
                @foreach ($images as $image)
                    <li class="relative">
                        <img src="{{ $image->getUrl() }}" alt="" class="w-full aspect-square object-cover rounded-md bg-paper-2">
                        <label class="mt-1 flex items-center gap-1.5 text-[12.5px]"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="accent-sindoor"> {{ __('Remove') }}</label>
                    </li>
                @endforeach
            </ul>
        @endif
        <label for="f-images_upload" class="sr-only">{{ __('Add photos') }}</label>
        <input id="f-images_upload" type="file" name="images_upload[]" multiple accept="image/jpeg,image/png,image/webp" class="block text-[14px]">
        <p class="mt-1 text-[13px] text-ink-3">{{ __('Pick several at once. JPG, PNG or WebP, up to 6 MB each.') }}</p>
        <x-field-error name="images_upload" />
        <x-field-error name="images_upload.0" />
    </div>

    <label class="flex items-center gap-2"><input type="hidden" name="featured" value="0"><input type="checkbox" name="featured" value="1" class="w-4 h-4 accent-sindoor" @checked(old('featured', $record->featured))> {{ __('Feature at the top of the News page') }}</label>
</x-admin.form-shell>
