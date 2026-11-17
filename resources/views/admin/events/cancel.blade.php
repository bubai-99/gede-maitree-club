<x-layouts.admin :title="__('Cancel event')">
    <div class="max-w-[640px]">
        <a href="{{ route('admin.events.registrations', $event) }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Who’s coming') }}</a>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Cancel · :event', ['event' => $event->tr('title')]) }}</h1>
        <p class="mt-2 text-[15px] text-ink-2">{{ __('Everyone who registered gets a notice in the app. Passes stop working at the gate.') }}</p>
        <p class="mt-1 text-[15px] text-ink-2">{{ __(':count families have registered.', ['count' => digits($families)]) }}</p>

        <form method="POST" action="{{ route('admin.events.cancel.store', $event) }}" class="mt-6 space-y-4">
            @csrf
            <x-field name="reason" :label="__('Reason shown to members')" textarea rows="3" required minlength="5" maxlength="250" :placeholder="__('e.g. Ground not available because of the by-election')" />
            <x-field name="reason_bn" :label="__('Reason in Bengali')" textarea rows="3" maxlength="250" lang="bn" />
            <div class="flex flex-wrap gap-2">
                <button class="btn btn-primary"><x-icon name="ban" /><span>{{ __('Cancel event') }}</span></button>
                <a href="{{ route('admin.events.registrations', $event) }}" class="btn btn-line">{{ __('Keep the event') }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
