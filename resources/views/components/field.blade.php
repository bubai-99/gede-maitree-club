@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'textarea' => false,
    'rows' => 4,
])
@php
    $id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name));
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $invalid = $errors->has($errorKey);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($invalid ? $id.'-error' : ''));
    $current = old($errorKey, $value);
@endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $id }}" class="label">{{ $label }}</label>
    @if ($textarea)
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="field" @if ($invalid) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->except(['class', 'id']) }}>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : $current }}" class="field"
            @if ($invalid) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except(['class', 'id']) }} />
    @endif
    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-[13px] text-ink-3">{{ $hint }}</p>
    @endif
    @error($errorKey)
        <p id="{{ $id }}-error" class="field-error text-sindoor-deep"><x-icon name="circle-alert" />{{ $message }}</p>
    @enderror
</div>
