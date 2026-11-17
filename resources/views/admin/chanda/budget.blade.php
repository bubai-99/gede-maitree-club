@php
    $lines = collect($event->budget ?? [])->map(fn ($b) => implode(' | ', [$b['head'] ?? '', $b['head_bn'] ?? '', $b['amount'] ?? 0]))->implode("\n");
@endphp
<x-layouts.admin :title="__('Budget')">
    <div class="max-w-[720px]">
        <a href="{{ route('admin.chanda.show', $event) }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ $event->tr('title') }}</a>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Budget · :event', ['event' => $event->tr('title')]) }}</h1>

        <form method="POST" action="{{ route('admin.chanda.budget.update', $event) }}" class="mt-6 space-y-5">
            @csrf @method('PUT')
            <x-field name="budget" :label="__('Budget heads')" textarea rows="8" :value="$lines" :hint="__('One line each: head | in Bengali | amount in rupees')" />
            <x-field name="chanda_target" type="number" min="0" inputmode="numeric" :label="__('Chanda target')" :value="$event->chanda_target" />
            <label class="flex items-start gap-2.5 text-[15px]"><input type="hidden" name="chanda_public" value="0"><input type="checkbox" name="chanda_public" value="1" class="mt-1 w-4 h-4 accent-sindoor" @checked(old('chanda_public', $event->chanda_public))>
                <span>{{ __('Show the target, totals by area and sponsors on the website') }}<span class="block text-[13px] text-ink-3">{{ __('Donor names are shown only for donors who agreed.') }}</span></span></label>
            <p class="text-sm text-ink-2">{{ __('Budget changes after the meeting approval are logged.') }}</p>
            <button class="btn btn-primary"><span>{{ __('Save budget') }}</span></button>
        </form>
    </div>
</x-layouts.admin>
