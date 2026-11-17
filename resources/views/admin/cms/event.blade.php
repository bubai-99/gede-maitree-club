@php
    $schedule = collect($record->schedule ?? [])->map(fn ($s) => implode(' | ', [$s['time'] ?? '', $s['time_bn'] ?? '', $s['what'] ?? '', $s['what_bn'] ?? '']))->implode("\n");
    $bring = collect($record->bring ?? [])->map(fn ($b) => implode(' | ', [$b['text'] ?? '', $b['text_bn'] ?? '']))->implode("\n");
@endphp
<x-admin.form-shell :record="$record" :route-name="$routeName" :parameter="$parameter" :list-title="$listTitle" :title="$record->exists ? $record->title : __('New event')" files>
    <x-admin.bilingual name="title" :label="__('Title')" :record="$record" required />
    <div class="grid sm:grid-cols-3 gap-4">
        <x-field name="slug" :label="__('Link name')" :value="$record->slug" :hint="__('Used in the address, e.g. /events/sports')" required />
        <div>
            <label for="f-kind" class="label">{{ __('Kind') }}</label>
            <select id="f-kind" name="kind" class="field">
                @foreach (\App\Enums\EventKind::cases() as $kind)
                    <option value="{{ $kind->value }}" @selected(old('kind', $record->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <x-field name="contact_role" :label="__('Who to ask (committee role)')" :value="$record->contact_role ?? 'Secretary'" />
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <x-field name="date" type="date" :label="__('Date')" :value="$record->starts_at?->toDateString()" required />
        <x-field name="time" type="time" :label="__('Start time')" :value="$record->starts_at?->format('H:i')" />
    </div>
    <x-admin.bilingual name="time_label" :label="__('Time as shown')" :record="$record" />
    <x-admin.bilingual name="place" :label="__('Place')" :record="$record" />
    <fieldset class="rounded-xl border border-rule bg-card p-5 space-y-4">
        <legend class="px-2 font-display text-xl">{{ __('Registration and passes') }}</legend>
        <div class="flex flex-wrap gap-x-6 gap-y-3">
            <label class="flex items-center gap-2"><input type="hidden" name="registration_open" value="0"><input type="checkbox" name="registration_open" value="1" class="w-4 h-4 accent-sindoor" @checked(old('registration_open', $record->registration_open))> {{ __('Families can register in the app') }}</label>
            <label class="flex items-center gap-2"><input type="hidden" name="pass_required" value="0"><input type="checkbox" name="pass_required" value="1" class="w-4 h-4 accent-sindoor" @checked(old('pass_required', $record->pass_required))> {{ __('Members need a pass (QR at the gate)') }}</label>
            <label class="flex items-center gap-2"><input type="hidden" name="members_only" value="0"><input type="checkbox" name="members_only" value="1" class="w-4 h-4 accent-sindoor" @checked(old('members_only', $record->exists ? $record->members_only : true))> {{ __('Members only (active families)') }}</label>
        </div>
        <div class="grid sm:grid-cols-3 gap-4">
            <x-field name="capacity" type="number" min="1" inputmode="numeric" :label="__('Seats (blank = no limit)')" :value="$record->capacity" />
            <x-field name="family_limit" type="number" min="1" max="50" inputmode="numeric" :label="__('Most people per family')" :value="$record->family_limit" :hint="__('Blank: up to :count', ['count' => digits(\App\Models\Event::DEFAULT_FAMILY_LIMIT)])" />
            <x-field name="registration_closes" type="date" :label="__('Registration closes')" :value="$record->registration_closes_at?->toDateString()" :hint="__('Blank: when the event starts')" />
        </div>
        <p class="text-[13px] text-ink-3">{{ __('Families can change or cancel their registration until it closes. Each registration gets one family pass when a pass is needed.') }}</p>
    </fieldset>
    <label class="flex items-center gap-2"><input type="hidden" name="chanda_box" value="0"><input type="checkbox" name="chanda_box" value="1" class="w-4 h-4 accent-sindoor" @checked(old('chanda_box', $record->chanda_box))> {{ __('Collects chanda') }}</label>
    <x-admin.bilingual name="about" :label="__('About')" :record="$record" textarea rows="5" />
    <x-field name="schedule" :label="__('Programme')" textarea rows="6" :value="$schedule" :hint="__('One line each: time | time in Bengali | what happens | in Bengali')" />
    <x-field name="bring" :label="__('What to bring')" textarea rows="3" :value="$bring" :hint="__('One line each: English | Bengali')" />
    <x-admin.photo-field :record="$record" />
</x-admin.form-shell>
