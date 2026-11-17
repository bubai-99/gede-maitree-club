@props(['name' => 'password', 'label', 'autocomplete' => 'current-password', 'hint' => null])
@php
    $id = 'f-'.$name;
    $invalid = $errors->has($name);
@endphp
{{-- Password input with a show/hide button (prototype passField). --}}
<div x-data="{ show: false }" {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}</label>
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" :type="show ? 'text' : 'password'" type="password" class="field pr-12" autocomplete="{{ $autocomplete }}"
            @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif {{ $attributes->except(['class', 'id']) }} />
        <button type="button" @click="show = ! show" class="absolute right-1 top-1 w-9 h-9 grid place-items-center rounded text-ink-2 hover:bg-paper-2" :aria-pressed="show.toString()" aria-label="{{ __('Show password') }}"><x-icon name="eye" class="w-[18px] h-[18px]" /></button>
    </div>
    @if ($hint)
        <p class="mt-1.5 text-[13px] text-ink-3">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="field-error text-sindoor-deep"><x-icon name="circle-alert" />{{ $message }}</p>
    @enderror
</div>
