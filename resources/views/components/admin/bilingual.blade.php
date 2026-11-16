@props(['name', 'label', 'record' => null, 'textarea' => false, 'rows' => 3, 'required' => false, 'hint' => null])
{{-- One field in English and Bengali side by side. --}}
<div class="grid sm:grid-cols-2 gap-4">
    <x-field :name="$name" :label="$label.' (English)'" :value="$record?->getAttribute($name)" :textarea="$textarea" :rows="$rows" :required="$required" :hint="$hint" />
    <x-field :name="$name.'_bn'" :label="$label.' (বাংলা)'" :value="$record?->getAttribute($name.'_bn')" :textarea="$textarea" :rows="$rows" lang="bn" />
</div>
