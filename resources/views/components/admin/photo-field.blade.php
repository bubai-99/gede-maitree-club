@props(['record'])
<div class="card p-5">
    <p class="label">{{ __('Photo') }}</p>
    <div class="flex flex-wrap items-center gap-4">
        @if ($record->exists && $record->photoUrl())
            <img src="{{ $record->photoUrl(400) }}" alt="" class="w-28 h-20 object-cover rounded-md bg-paper-2">
        @endif
        <div class="min-w-0 flex-1">
            <label for="f-photo_upload" class="sr-only">{{ __('Upload a photo') }}</label>
            <input id="f-photo_upload" type="file" name="photo_upload" accept="image/jpeg,image/png,image/webp" class="block text-[14px]" @error('photo_upload') aria-invalid="true" aria-describedby="f-photo_upload-error" @enderror>
            <p class="mt-1 text-[13px] text-ink-3">{{ __('JPG, PNG or WebP, up to 4 MB.') }}</p>
            <x-field-error name="photo_upload" />
            @if ($record->exists && $record->hasUploadedPhoto())
                <label class="mt-2 flex items-center gap-2 text-[14px]"><input type="checkbox" name="remove_photo" value="1" class="accent-sindoor"> {{ __('Remove the uploaded photo') }}</label>
            @endif
        </div>
    </div>
</div>
