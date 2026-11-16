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
    <div class="flex flex-wrap gap-6">
        <label class="flex items-center gap-2"><input type="hidden" name="pass_required" value="0"><input type="checkbox" name="pass_required" value="1" class="w-4 h-4 accent-sindoor" @checked(old('pass_required', $record->pass_required))> {{ __('Member pass needed') }}</label>
        <label class="flex items-center gap-2"><input type="hidden" name="chanda_box" value="0"><input type="checkbox" name="chanda_box" value="1" class="w-4 h-4 accent-sindoor" @checked(old('chanda_box', $record->chanda_box))> {{ __('Collects chanda') }}</label>
    </div>
    <x-admin.bilingual name="about" :label="__('About')" :record="$record" textarea rows="5" />
    <x-field name="schedule" :label="__('Programme')" textarea rows="6" :value="$schedule" :hint="__('One line each: time | time in Bengali | what happens | in Bengali')" />
    <x-field name="bring" :label="__('What to bring')" textarea rows="3" :value="$bring" :hint="__('One line each: English | Bengali')" />
    <x-admin.photo-field :record="$record" />
</x-admin.form-shell>
