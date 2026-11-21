<x-admin.form-shell :record="$record" :route-name="$routeName" :parameter="$parameter" :list-title="$listTitle" :title="$record->exists ? $record->name : __('New plant')" files>
    <x-admin.bilingual name="name" :label="__('Name')" :record="$record" required />
    <div class="grid sm:grid-cols-3 gap-4">
        <x-field name="slug" :label="__('Link name')" :value="$record->slug" required />
        <div>
            <label for="f-category" class="label">{{ __('Type') }}</label>
            <select id="f-category" name="category" class="field">
                @foreach (\App\Enums\PlantCategory::cases() as $category)
                    <option value="{{ $category->value }}" @selected(old('category', $record->category?->value) === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
        </div>
        <x-field name="sort" type="number" :label="__('Order on the page')" :value="$record->sort ?? 0" min="0" />
    </div>
    <x-admin.bilingual name="pot" :label="__('Pot size')" :record="$record" />
    <div class="grid sm:grid-cols-3 gap-4">
        <x-field name="price" type="number" :label="__('Price (₹)')" :value="$record->price" min="0" required />
        <x-field name="member_price" type="number" :label="__('Member price (₹)')" :value="$record->member_price" min="0" required />
        <x-field name="stock" type="number" :label="__('In stock')" :value="$record->stock ?? 0" min="0" required />
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="f-light" class="label">{{ __('Light') }}</label>
            <select id="f-light" name="light" class="field"><option value="">—</option>
                @foreach (\App\Enums\PlantLight::cases() as $light)
                    <option value="{{ $light->value }}" @selected(old('light', $record->light?->value) === $light->value)>{{ $light->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-water" class="label">{{ __('Water') }}</label>
            <select id="f-water" name="water" class="field"><option value="">—</option>
                @foreach (\App\Enums\PlantWater::cases() as $water)
                    <option value="{{ $water->value }}" @selected(old('water', $record->water?->value) === $water->value)>{{ $water->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="flex flex-wrap gap-6">
        <label class="flex items-center gap-2"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" class="w-4 h-4 accent-sindoor" @checked(old('active', $record->exists ? $record->active : true))> {{ __('On sale') }}</label>
        <label class="flex items-center gap-2"><input type="hidden" name="is_new" value="0"><input type="checkbox" name="is_new" value="1" class="w-4 h-4 accent-sindoor" @checked(old('is_new', $record->is_new))> {{ __('Mark as new') }}</label>
    </div>
    <x-admin.bilingual name="about" :label="__('About')" :record="$record" textarea />
    <x-admin.bilingual name="tip" :label="__('Care tip')" :record="$record" textarea />

    <div class="card p-5 space-y-3">
        <p class="font-semibold">{{ __('Care guide') }}</p>
        <p class="text-[13.5px] text-ink-3">{{ __('Shown in the plant’s details on the website. ## makes a heading, **bold**, - starts a list. A YouTube, Facebook or other video link on a line of its own shows as the video.') }}</p>
        <x-admin.bilingual name="details" :label="__('Text')" :record="$record" textarea :rows="8" />
    </div>

    <div class="card p-5">
        <x-field name="videos" :label="__('Videos')" textarea :rows="3" :value="old('videos', \App\Support\VideoEmbed::text($record->videos))" placeholder="https://youtu.be/… | How to repot a tulsi" :hint="__('One link per line: YouTube, Facebook, Instagram, Vimeo, Google Drive, Dailymotion, or a .mp4 file. Add a caption after a |.')" />
    </div>

    <x-admin.photo-field :record="$record" />

    @php
        $images = $record->exists ? $record->getMedia('images') : collect();
    @endphp
    <div class="card p-5">
        <p class="label">{{ __('More photos') }}</p>
        @if ($images->isNotEmpty())
            <ul class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-3">
                @foreach ($images as $image)
                    <li>
                        <img src="{{ $image->getUrl() }}" alt="" class="w-full aspect-square object-cover rounded-md bg-paper-2">
                        <label class="mt-1 flex items-center gap-1.5 text-[12.5px]"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="accent-sindoor"> {{ __('Remove') }}</label>
                    </li>
                @endforeach
            </ul>
        @endif
        <label for="f-images_upload" class="sr-only">{{ __('Add photos') }}</label>
        <input id="f-images_upload" type="file" name="images_upload[]" multiple accept="image/jpeg,image/png,image/webp" class="block text-[14px]">
        <p class="mt-1 text-[13px] text-ink-3">{{ __('Shown with the main photo in the plant’s details. Pick several at once. JPG, PNG or WebP, up to 6 MB each.') }}</p>
        <x-field-error name="images_upload" />
    </div>
</x-admin.form-shell>
