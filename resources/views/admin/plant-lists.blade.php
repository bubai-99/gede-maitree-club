@php
    $sections = [
        'category' => [__('Plant types'), __('The chips on the nursery page, in this order. A type with no plants is not shown there.'), __('Add a type'), 'sprout'],
        'light' => [__('Sun'), __('How much sun a plant needs, shown in its details.'), __('Add a choice'), 'sun'],
        'water' => [__('Water'), __('How often to water, shown in its details.'), __('Add a choice'), 'droplets'],
    ];
@endphp
<x-layouts.admin :title="__('Plant lists')">
    <div class="max-w-[760px]">
        <a href="{{ route('admin.settings.edit') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Settings') }}</a>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Plant lists') }}</h1>
        <p class="text-ink-2">{{ __('The choices on the plant form and the nursery page. Renaming a choice changes it on every plant that has it.') }}</p>

        <form method="POST" action="{{ route('admin.plant-lists.update') }}" class="mt-6 space-y-8">
            @csrf @method('PUT')
            <fieldset @disabled(! $canEdit) class="space-y-8">
                @foreach ($sections as $kind => [$title, $help, $add, $icon])
                    <section aria-labelledby="list-{{ $kind }}" x-data="{ rows: @js(array_values($lists[$kind])).map((r) => ({ key: r.key ?? '', name: r.name ?? '', name_bn: r.name_bn ?? '', uid: Math.random() })) }">
                        <h2 id="list-{{ $kind }}" class="flex items-center gap-2 font-display text-[1.45rem]"><x-icon :name="$icon" class="w-5 h-5 text-tulsi" />{{ $title }}</h2>
                        <p class="text-[14px] text-ink-2">{{ $help }}</p>
                        <x-field-error :name="$kind" />
                        <ol class="mt-3 paper-card divide-y divide-rule">
                            <template x-for="(row, i) in rows" :key="row.uid">
                                <li class="p-3 sm:p-4 grid grid-cols-[auto_1fr_1fr_auto] gap-2 sm:gap-3 items-end">
                                    <span class="w-7 h-11 grid place-items-center text-ink-3 num" x-text="i + 1"></span>
                                    <input type="hidden" :name="'{{ $kind }}[' + i + '][key]'" :value="row.key">
                                    <div>
                                        <label class="label" :for="'{{ $kind }}-' + i">{{ __('Name (English)') }}</label>
                                        <input class="field" :id="'{{ $kind }}-' + i" :name="'{{ $kind }}[' + i + '][name]'" x-model="row.name" required maxlength="40">
                                    </div>
                                    <div>
                                        <label class="label" :for="'{{ $kind }}-bn-' + i">{{ __('Name (Bengali)') }}</label>
                                        <input class="field" lang="bn" :id="'{{ $kind }}-bn-' + i" :name="'{{ $kind }}[' + i + '][name_bn]'" x-model="row.name_bn" maxlength="40">
                                    </div>
                                    @if ($canEdit)
                                        <div class="flex gap-1 h-11 items-center">
                                            <button type="button" class="btn btn-ghost btn-sm px-2" @click="if (i > 0) rows.splice(i - 1, 0, rows.splice(i, 1)[0])" :disabled="i === 0" aria-label="{{ __('Move up') }}"><x-icon name="arrow-up" class="w-4 h-4" /></button>
                                            <button type="button" class="btn btn-ghost btn-sm px-2" @click="if (i < rows.length - 1) rows.splice(i + 1, 0, rows.splice(i, 1)[0])" :disabled="i === rows.length - 1" aria-label="{{ __('Move down') }}"><x-icon name="arrow-down" class="w-4 h-4" /></button>
                                            <button type="button" class="btn btn-ghost btn-sm px-2 text-sindoor-deep" @click="rows.splice(i, 1)" @if ($kind === 'category') :disabled="rows.length === 1" @endif aria-label="{{ __('Remove') }}"><x-icon name="trash-2" class="w-4 h-4" /></button>
                                        </div>
                                    @endif
                                </li>
                            </template>
                            <li x-show="! rows.length" class="p-4 text-ink-3 text-[14px]">{{ __('No choices: the plant form will not ask for this.') }}</li>
                        </ol>
                        @if ($canEdit)
                            <button type="button" class="mt-3 btn btn-line btn-sm" x-show="rows.length < 30" @click="rows.push({ key: '', name: '', name_bn: '', uid: Math.random() }); $nextTick(() => document.getElementById('{{ $kind }}-' + (rows.length - 1))?.focus())"><x-icon name="plus" />{{ $add }}</button>
                        @endif
                    </section>
                @endforeach
            </fieldset>
            @if ($canEdit)
                <div class="sticky bottom-20 lg:bottom-4 bg-paper/95 py-3">
                    <button type="submit" class="btn btn-primary"><x-icon name="save" />{{ __('Save') }}</button>
                </div>
            @else
                <p class="text-[14px] text-ink-3">{{ __('You can look but not change this. Only the Admin and the Website editor edit the website.') }}</p>
            @endif
        </form>
    </div>
</x-layouts.admin>
