<x-layouts.admin :title="__('Puja chanda')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Puja chanda') }}</h1>
    <div class="mt-6 paper-card p-8 text-center max-w-xl">
        <x-icon name="hand-heart" class="w-10 h-10 text-ink-3 mx-auto" />
        <p class="mt-3 font-semibold">{{ __('No event collects chanda yet') }}</p>
        <p class="mt-1 text-ink-2">{{ __('Tick “Collects chanda” on a puja in Events to open its chanda book.') }}</p>
        @can('create', \App\Models\Event::class)
            <a href="{{ route('admin.events.index') }}" class="mt-4 btn btn-line btn-sm"><x-icon name="calendar-days" /><span>{{ __('Events') }}</span></a>
        @endcan
    </div>
</x-layouts.admin>
