@props(['name'])
@error($name)
    <p id="{{ $name }}-error" class="field-error text-sindoor-deep"><x-icon name="circle-alert" />{{ $message }}</p>
@enderror
