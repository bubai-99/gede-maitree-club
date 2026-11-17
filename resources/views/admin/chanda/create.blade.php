<x-layouts.admin :title="__('Record chanda')">
    <div class="max-w-[640px]">
        <a href="{{ route('admin.chanda.show', $event) }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ $event->tr('title') }}</a>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Record chanda') }}</h1>
        <p class="mt-1 text-ink-2">{{ __('The receipt gets the next number in the chanda series and the money goes into the Club book.') }}</p>

        <form method="POST" action="{{ route('admin.chanda.store', $event) }}" class="mt-6 space-y-4" x-data="{ mode: @js(old('mode', 'cash')) }">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="donor_name" :label="__('Donor')" required maxlength="120" autocomplete="off" />
                <x-field name="family_no" :label="__('Family number (if a member)')" placeholder="MC-0104" />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="amount" type="number" min="1" inputmode="numeric" :label="__('Amount')" required class="[&_input]:text-lg" />
                <div>
                    <label for="f-area_id" class="label">{{ __('Area') }}</label>
                    <select id="f-area_id" name="area_id" class="field" @if (! $manager) required @endif>
                        @if ($manager)<option value="">{{ __('At the club room') }}</option>@endif
                        @foreach ($areas as $option)
                            <option value="{{ $option->id }}" @selected((int) old('area_id', $area) === $option->id)>{{ $option->tr('name') }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="area_id" />
                </div>
            </div>
            <fieldset>
                <legend class="label">{{ __('Paid by') }}</legend>
                <div class="flex gap-5">
                    @foreach (\App\Enums\ChandaMode::cases() as $mode)
                        <label class="flex items-center gap-2"><input type="radio" name="mode" value="{{ $mode->value }}" x-model="mode" class="accent-sindoor" @checked(old('mode', 'cash') === $mode->value)> {{ $mode->label() }}</label>
                    @endforeach
                </div>
            </fieldset>
            <div x-show="mode === 'upi'" x-cloak>
                <x-field name="upi_ref" :label="__('UTR (for UPI)')" inputmode="numeric" maxlength="40" />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="donor_phone" type="tel" :label="__('Mobile (for the WhatsApp receipt)')" inputmode="tel" />
                <x-field name="received_on" type="date" :label="__('Date')" :value="now()->toDateString()" :max="now()->toDateString()" />
            </div>
            @if ($manager && $collectors->isNotEmpty())
                <div>
                    <label for="f-collected_by" class="label">{{ __('Collected by') }}</label>
                    <select id="f-collected_by" name="collected_by" class="field">
                        @foreach ($collectors as $collector)
                            <option value="{{ $collector->id }}" @selected((int) old('collected_by', auth()->id()) === $collector->id)>{{ $collector->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[13px] text-ink-3">{{ __('Cash stays with this person until they hand it to the treasurer.') }}</p>
                </div>
            @endif
            <label class="flex items-start gap-2.5 text-[15px]"><input type="checkbox" name="show_name" value="1" class="mt-1 w-4 h-4 accent-sindoor" @checked(old('show_name'))> {{ __('The donor agrees to have their name shown on the website') }}</label>
            <button class="btn btn-primary h-12 px-6"><x-icon name="receipt" /><span>{{ __('Record and print receipt') }}</span></button>
        </form>
    </div>
</x-layouts.admin>
