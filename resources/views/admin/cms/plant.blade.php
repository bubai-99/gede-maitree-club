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
    <x-admin.photo-field :record="$record" />
</x-admin.form-shell>
