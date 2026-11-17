{{-- Fields of a chanda area (add and edit forms). Expects $area (or null), $prefix and $collectors. --}}
<div class="sm:col-span-2"><label for="{{ $prefix }}-name" class="label">{{ __('Area') }}</label><input id="{{ $prefix }}-name" name="name" class="field h-9" required maxlength="100" value="{{ $area?->name }}"></div>
<div><label for="{{ $prefix }}-name-bn" class="label">{{ __('In Bengali') }}</label><input id="{{ $prefix }}-name-bn" name="name_bn" class="field h-9" lang="bn" maxlength="100" value="{{ $area?->name_bn }}"></div>
<div class="sm:col-span-2"><label for="{{ $prefix }}-collector" class="label">{{ __('Volunteer') }}</label>
    <select id="{{ $prefix }}-collector" name="collector_id" class="field h-9 py-0">
        <option value="">—</option>
        @foreach ($collectors as $collector)
            <option value="{{ $collector->id }}" @selected($area?->collector_id === $collector->id)>{{ $collector->name }}</option>
        @endforeach
    </select>
</div>
<div><label for="{{ $prefix }}-target" class="label">{{ __('Target') }}</label><input id="{{ $prefix }}-target" name="target" type="number" min="0" class="field h-9 num" value="{{ $area?->target }}"></div>
<div><label for="{{ $prefix }}-houses" class="label">{{ __('Houses') }}</label><input id="{{ $prefix }}-houses" name="houses" type="number" min="0" class="field h-9 num" value="{{ $area?->houses }}"></div>
<div><label for="{{ $prefix }}-visited" class="label">{{ __('Visited') }}</label><input id="{{ $prefix }}-visited" name="visited" type="number" min="0" class="field h-9 num" value="{{ $area?->visited }}"></div>
