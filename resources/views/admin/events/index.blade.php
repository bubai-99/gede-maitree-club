<x-layouts.admin :title="__('Events')">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Events') }}</h1>
            <p class="mt-1 text-ink-2">{{ __('Create events, open passes, and see who is coming.') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('passes.scan')
                <a href="{{ route('admin.scan') }}" class="btn btn-line btn-sm"><x-icon name="scan-line" /><span>{{ __('Gate scanner') }}</span></a>
            @endcan
            @can('create', \App\Models\Event::class)
                <a href="{{ route('admin.events.create') }}" class="btn btn-ink btn-sm"><x-icon name="plus" /><span>{{ __('New event') }}</span></a>
            @endcan
        </div>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-rule bg-card">
        <table class="khata-table min-w-[760px]">
            <thead><tr><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Event') }}</th><th scope="col">{{ __('Passes') }}</th><th scope="col">{{ __('Registered') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td class="num whitespace-nowrap font-semibold">{{ local_date($event->starts_at, 'j M Y') }}</td>
                        <td>
                            <span class="font-semibold">{{ $event->tr('title') }}</span>
                            <span class="block text-[13px] text-ink-3">{{ $event->tr('place') }}@if ($event->tr('time_label')) · {{ $event->tr('time_label') }}@endif</span>
                        </td>
                        <td>
                            @if ($event->pass_required)
                                <span class="chip border border-ink/40 whitespace-nowrap"><x-icon name="ticket" />{{ __('Pass needed') }}</span>
                            @else
                                <span class="text-sm text-ink-3">{{ $event->members_only ? __('Members only') : __('Open to all') }}</span>
                            @endif
                        </td>
                        <td class="num">
                            @if ($event->registration_open || $event->families)
                                {{ digits((int) $event->families) }} {{ __('families') }} · {{ digits((int) $event->people) }} {{ __('people') }}
                                @if ($event->capacity)<span class="text-ink-3">/ {{ digits($event->capacity) }} {{ __('seats') }}</span>@endif
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($event->isCancelled())
                                <span class="chip bg-ink text-paper">{{ __('Cancelled') }}</span>
                            @else
                                <x-admin.status :published="$event->isPublished()" />
                                @if ($event->registration_open)
                                    <span class="chip bg-tulsi-wash text-tulsi mt-1">{{ __('Registration open') }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            @can('manageRegistrations', $event)
                                <a href="{{ route('admin.events.registrations', $event) }}" class="btn btn-ghost btn-sm">{{ __('Who’s coming') }}</a>
                            @endcan
                            @if ($event->chanda_box)
                                @can('viewChanda', $event)
                                    <a href="{{ route('admin.chanda.show', $event) }}" class="btn btn-ghost btn-sm">{{ __('Chanda') }}</a>
                                @endcan
                            @endif
                            @if ($event->pass_required && ! $event->isCancelled())
                                @can('scan', $event)
                                    <a href="{{ route('admin.scan', ['event' => $event->slug]) }}" class="btn btn-ghost btn-sm">{{ __('Gate') }}</a>
                                @endcan
                            @endif
                            <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-line btn-sm">@can('update', $event){{ __('Edit') }}@else{{ __('View') }}@endcan</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-ink-2">{{ __('Nothing here yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $events->links() }}</div>
</x-layouts.admin>
