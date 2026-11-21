<x-layouts.admin :title="__('Lanes and areas')">
    <div class="max-w-[760px]">
        <a href="{{ route('admin.settings.edit') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Settings') }}</a>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Lanes and areas') }}</h1>
        <p class="text-ink-2">{{ __('The choices for “Where you live” on the join, volunteer and chanda forms, in this order. “Somewhere else” is always added at the end.') }}</p>

        <form method="POST" action="{{ route('admin.areas.update') }}" class="mt-6"
              x-data="{ seq: 0, areas: @js(array_values($areas)).map((a) => ({ ...a, uid: Math.random() })) }">
            @csrf @method('PUT')
            <fieldset @disabled(! $canEdit)>
                <legend class="sr-only">{{ __('Lanes and areas') }}</legend>
                <ol class="paper-card divide-y divide-rule">
                    <template x-for="(area, i) in areas" :key="area.uid">
                        <li class="p-3 sm:p-4 grid grid-cols-[auto_1fr_1fr_auto] gap-2 sm:gap-3 items-end">
                            <span class="w-7 h-11 grid place-items-center text-ink-3 num" x-text="i + 1"></span>
                            <div>
                                <label class="label" :for="'area-' + i">{{ __('Name (English)') }}</label>
                                <input class="field" :id="'area-' + i" :name="'areas[' + i + '][name]'" x-model="area.name" required maxlength="60">
                            </div>
                            <div>
                                <label class="label" :for="'area-bn-' + i">{{ __('Name (Bengali)') }}</label>
                                <input class="field" lang="bn" :id="'area-bn-' + i" :name="'areas[' + i + '][name_bn]'" x-model="area.name_bn" maxlength="60">
                            </div>
                            @if ($canEdit)
                                <div class="flex gap-1 h-11 items-center">
                                    <button type="button" class="btn btn-ghost btn-sm px-2" @click="if (i > 0) areas.splice(i - 1, 0, areas.splice(i, 1)[0])" :disabled="i === 0" aria-label="{{ __('Move up') }}"><x-icon name="arrow-up" class="w-4 h-4" /></button>
                                    <button type="button" class="btn btn-ghost btn-sm px-2" @click="if (i < areas.length - 1) areas.splice(i + 1, 0, areas.splice(i, 1)[0])" :disabled="i === areas.length - 1" aria-label="{{ __('Move down') }}"><x-icon name="arrow-down" class="w-4 h-4" /></button>
                                    <button type="button" class="btn btn-ghost btn-sm px-2 text-sindoor-deep" @click="areas.splice(i, 1)" :disabled="areas.length === 1" aria-label="{{ __('Remove') }}"><x-icon name="trash-2" class="w-4 h-4" /></button>
                                </div>
                            @endif
                        </li>
                    </template>
                    <li class="p-3 sm:p-4 flex items-center gap-3 text-ink-3"><span class="w-7 text-center">·</span>{{ __('Somewhere else') }} <span class="text-[13px]">({{ __('always last') }})</span></li>
                </ol>
                <x-field-error name="areas" />
                @if ($canEdit)
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" class="btn btn-line" @click="areas.push({ name: '', name_bn: '', uid: Math.random() }); $nextTick(() => document.getElementById('area-' + (areas.length - 1))?.focus())" x-show="areas.length < 40"><x-icon name="plus" />{{ __('Add a lane or area') }}</button>
                        <button type="submit" class="btn btn-primary"><x-icon name="save" />{{ __('Save') }}</button>
                    </div>
                @else
                    <p class="mt-3 text-[14px] text-ink-3">{{ __('You can look but not change this. Only the Admin and the Website editor edit the website.') }}</p>
                @endif
            </fieldset>
        </form>
    </div>
</x-layouts.admin>
