<x-layouts.admin :title="__('New khata entry')">
    <a href="{{ route('admin.khata.index') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Khata') }}</a>
    <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('New khata entry') }}</h1>

    <form method="POST" action="{{ route('admin.khata.store') }}" enctype="multipart/form-data" class="mt-5 max-w-xl paper-card p-5 sm:p-6 space-y-5"
        x-data="{ type: @js(old('type', $type->value)), heads: @js($heads), head: @js(old('head', '')), photo: '' }">
        @csrf
        <fieldset>
            <legend class="label">{{ __('Book') }}</legend>
            <div class="grid grid-cols-{{ $books->count() }} gap-2">
                @foreach ($books as $option)
                    <label class="book-{{ $option->code->value }} cursor-pointer">
                        <input type="radio" name="book" value="{{ $option->code->value }}" class="peer sr-only" @checked(old('book', $bookCode) === $option->code->value)>
                        <span class="flex items-center justify-center gap-2 h-11 rounded-lg border border-rule bg-card font-semibold peer-checked:border-[color:var(--book)] peer-checked:bg-[color:var(--book-wash)] peer-focus-visible:ring-2 peer-focus-visible:ring-sindoor"><span class="inline-block w-2.5 h-2.5 rounded-full bg-[color:var(--book)]"></span>{{ $option->code->label() }}</span>
                    </label>
                @endforeach
            </div>
            <x-field-error name="book" />
        </fieldset>

        <fieldset>
            <legend class="label">{{ __('Type') }}</legend>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Enums\LedgerType::cases() as $option)
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="{{ $option->value }}" x-model="type" class="peer sr-only" @checked(old('type', $type->value) === $option->value)>
                        <span class="flex items-center justify-center text-center px-2 h-11 rounded-lg border border-rule bg-card font-semibold text-sm peer-checked:bg-ink peer-checked:text-paper peer-checked:border-ink peer-focus-visible:ring-2 peer-focus-visible:ring-sindoor">{{ $option->label() }}</span>
                    </label>
                @endforeach
            </div>
            <x-field-error name="type" />
        </fieldset>

        <x-field name="amount" :label="__('Amount (₹, whole rupees)')" type="number" min="1" step="1" inputmode="numeric" class="max-w-xs" required />

        <div x-show="type === 'income' || type === 'expense'">
            <label for="f-head" class="label">{{ __('Head') }}</label>
            <select id="f-head" name="head" class="field" x-model="head" @error('head') aria-invalid="true" aria-describedby="head-error" @enderror>
                <option value="">{{ __('Choose…') }}</option>
                <template x-for="option in (heads[type] || [])" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === head"></option>
                </template>
            </select>
            <x-field-error name="head" />
        </div>

        <fieldset x-show="type === 'income' || type === 'expense'">
            <legend class="label">{{ __('Paid or received in') }}</legend>
            <div class="grid grid-cols-2 gap-2 max-w-sm">
                @foreach (\App\Enums\LedgerAccount::cases() as $option)
                    <label class="cursor-pointer">
                        <input type="radio" name="account" value="{{ $option->value }}" class="peer sr-only" @checked(old('account', 'cash') === $option->value)>
                        <span class="flex items-center justify-center h-11 rounded-lg border border-rule bg-card font-semibold peer-checked:bg-ink peer-checked:text-paper peer-checked:border-ink peer-focus-visible:ring-2 peer-focus-visible:ring-sindoor">{{ $option->label() }}</span>
                    </label>
                @endforeach
            </div>
            <x-field-error name="account" />
        </fieldset>

        <x-field name="entry_date" :label="__('Date')" type="date" :value="now()->toDateString()" max="{{ now()->toDateString() }}" class="max-w-xs" required />
        <x-field name="note" :label="__('Note')" :hint="__('For example: CESC bill, October. Or the bank slip number for a deposit.')" maxlength="255" />
        @if ($events->isNotEmpty())
            <div class="max-w-md">
                <label for="f-event_id" class="label">{{ __('For event (optional)') }}</label>
                <select id="f-event_id" name="event_id" class="field">
                    <option value="">—</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" @selected((int) old('event_id', request('event')) === $event->id)>{{ $event->tr('title') }} · {{ local_date($event->starts_at, 'j M Y') }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-[13px] text-ink-3">{{ __('Puja expenses marked for an event show as “spent” against its budget.') }}</p>
                <x-field-error name="event_id" />
            </div>
        @endif

        <div>
            <p class="label">{{ __('Bill photo') }} <span class="text-ink-3 font-normal">({{ __('optional') }})</span></p>
            <label class="w-full h-28 rounded-lg border-2 border-dashed border-rule bg-card text-ink-2 flex flex-col items-center justify-center gap-1.5 hover:border-ink/30 cursor-pointer">
                <x-icon name="camera" class="w-6 h-6" /><span class="text-sm" x-text="photo || @js(__('Take a photo of the bill'))">{{ __('Take a photo of the bill') }}</span>
                <input type="file" name="bill" accept="image/jpeg,image/png,image/webp" capture="environment" class="sr-only" @change="photo = $event.target.files[0]?.name || ''">
            </label>
            <x-field-error name="bill" />
        </div>

        <button class="btn btn-ink w-full h-12">{{ __('Save entry') }}</button>
        <p class="text-sm text-ink-3">{{ __('Entries cannot be deleted, only reversed with a note. Every change is logged for the auditor.') }}</p>
    </form>
</x-layouts.admin>
