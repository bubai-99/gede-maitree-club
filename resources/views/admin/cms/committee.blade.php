<x-admin.form-shell :record="$record" :route-name="$routeName" :parameter="$parameter" :list-title="$listTitle" :title="$record->exists ? $record->name : __('New committee member')" files>
    <x-admin.bilingual name="name" :label="__('Name')" :record="$record" required />
    <x-admin.bilingual name="role" :label="__('Role')" :record="$record" required />
    <div class="grid sm:grid-cols-3 gap-4">
        <x-field name="phone" type="tel" :label="__('Phone')" :value="$record->phone" />
        <x-field name="since" type="number" :label="__('On the committee since')" :value="$record->since" min="1990" max="2100" />
        <x-field name="sort" type="number" :label="__('Order on the page')" :value="$record->sort ?? 0" min="0" />
    </div>
    <x-admin.photo-field :record="$record" />
</x-admin.form-shell>
