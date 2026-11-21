<x-admin.form-shell :record="$record" :route-name="$routeName" :parameter="$parameter" :list-title="$listTitle" :title="$record->exists ? __('Edit review') : __('New review')">
    <p class="rounded-lg bg-paper-2 px-4 py-3 text-[14px] text-ink-2">{{ __('Add a review only with the customer’s permission. Use a description, not their full name, if they prefer (“A family on School Road”).') }}</p>
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label for="f-kind" class="label">{{ __('About') }}</label>
            <select id="f-kind" name="kind" class="field" required>
                @foreach (\App\Enums\ReviewKind::cases() as $kind)
                    <option value="{{ $kind->value }}" @selected(old('kind', $record->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
            <x-field-error name="kind" />
        </div>
        <div>
            <label for="f-rating" class="label">{{ __('Stars') }}</label>
            <select id="f-rating" name="rating" class="field" required>
                @foreach ([5, 4, 3, 2, 1] as $n)
                    <option value="{{ $n }}" @selected((int) old('rating', $record->rating ?? 5) === $n)>{{ str_repeat('★', $n) }} · {{ digits($n) }}</option>
                @endforeach
            </select>
            <x-field-error name="rating" />
        </div>
        <x-field name="reviewed_on" type="date" :label="__('Date')" :value="$record->reviewed_on?->toDateString()" :max="now()->toDateString()" />
    </div>
    <x-admin.bilingual name="body" :label="__('Their words')" :record="$record" textarea :rows="4" required />
    <x-admin.bilingual name="who" :label="__('Who')" :record="$record" required :hint="__('e.g. A family on School Road')" />
    <x-admin.bilingual name="what" :label="__('What they had')" :record="$record" :hint="__('e.g. Full setup · wedding, 220 guests')" />
    <x-field name="sort" type="number" min="0" :label="__('Order')" :value="$record->sort ?? 0" class="max-w-[160px]" />
</x-admin.form-shell>
