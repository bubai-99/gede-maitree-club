<x-admin.form-shell :record="$record" :route-name="$routeName" :parameter="$parameter" :list-title="$listTitle" :title="$record->exists ? $record->name : __('New package')">
    <x-admin.bilingual name="name" :label="__('Name')" :record="$record" required />
    <x-admin.bilingual name="for" :label="__('Good for')" :record="$record" />
    <div class="grid sm:grid-cols-4 gap-4">
        <x-field name="slug" :label="__('Link name')" :value="$record->slug" required />
        <x-field name="rate" type="number" :label="__('Rate per guest (₹)')" :value="$record->rate" min="1" required />
        <x-field name="min_guests" type="number" :label="__('Fewest guests')" :value="$record->min_guests ?? 50" min="0" required />
        <x-field name="sort" type="number" :label="__('Order on the page')" :value="$record->sort ?? 0" min="0" />
    </div>
    <label class="flex items-center gap-2"><input type="hidden" name="popular" value="0"><input type="checkbox" name="popular" value="1" class="w-4 h-4 accent-sindoor" @checked(old('popular', $record->popular))> {{ __('Most booked') }}</label>
    <div class="grid sm:grid-cols-2 gap-4">
        <x-field name="menu" :label="__('What is included (English)')" textarea rows="6" :value="implode(PHP_EOL, $record->menu ?? [])" :hint="__('One item per line')" />
        <x-field name="menu_bn" :label="__('What is included (Bengali)')" textarea rows="6" :value="implode(PHP_EOL, $record->menu_bn ?? [])" lang="bn" />
    </div>
</x-admin.form-shell>
