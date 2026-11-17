<x-layouts.admin :title="__('Who’s coming')">
    <a href="{{ route('admin.events.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink" data-no-print><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Events') }}</a>
    <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ $event->tr('title') }}</h1>
            <p class="mt-1 text-ink-2">{{ local_date($event->starts_at, 'l, j F Y') }}@if ($event->tr('place')) · {{ $event->tr('place') }}@endif</p>
        </div>
        <div class="flex flex-wrap gap-2" data-no-print>
            <button type="button" onclick="window.print()" class="btn btn-line btn-sm"><x-icon name="printer" /><span>{{ __('Print list') }}</span></button>
            <a href="{{ route('admin.events.registrations.export', $event) }}" class="btn btn-line btn-sm"><x-icon name="file-spreadsheet" /><span>{{ __('Download CSV') }}</span></a>
            @if ($event->pass_required && ! $event->isCancelled())
                @can('scan', $event)
                    <a href="{{ route('admin.scan', ['event' => $event->slug]) }}" class="btn btn-ink btn-sm"><x-icon name="scan-line" /><span>{{ __('Open the gate scanner') }}</span></a>
                @endcan
            @endif
        </div>
    </div>

    @if ($event->isCancelled())
        <section class="mt-5 rounded-xl border-2 border-sindoor bg-sindoor-wash/50 p-5" aria-labelledby="cancel-h">
            <h2 id="cancel-h" class="font-display text-xl text-sindoor-deep">{{ __('This event is cancelled') }}</h2>
            <p class="mt-1 text-[15px]">{{ local_text($event->cancel_reason, $event->cancel_reason_bn) }} · {{ local_date($event->cancelled_at, 'j F, g:i a') }}@if ($event->canceller) · {{ $event->canceller->name }}@endif</p>
            <p class="mt-2 text-[14px] text-ink-2">{{ __('Members see a pinned notice in the app. Tap each family below to send the WhatsApp message (the app sends nothing by itself).') }}</p>
            @if ($whatsapp)
                <ul class="mt-3 grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach ($whatsapp as $link)
                        <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="btn btn-line btn-sm w-full justify-start"><x-icon name="message-circle" /><span class="truncate">{{ $link['name'] }} · <span class="num">{{ $link['family'] }}</span></span></a></li>
                    @endforeach
                </ul>
            @else
                <p class="mt-2 text-[14px] text-ink-3">{{ __('No registered family has a mobile number.') }}</p>
            @endif
        </section>
    @endif

    <div class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-px bg-rule border border-rule rounded-lg overflow-hidden text-center">
        <div class="bg-card p-4"><p class="font-display text-2xl num">{{ digits($families) }}</p><p class="text-[13px] text-ink-3">{{ __('families') }}</p></div>
        <div class="bg-card p-4"><p class="font-display text-2xl num">{{ digits($people) }}@if ($event->capacity)<span class="text-ink-3 text-lg"> / {{ digits($event->capacity) }}</span>@endif</p><p class="text-[13px] text-ink-3">{{ __('people') }}</p></div>
        <div class="bg-card p-4"><p class="font-display text-2xl num">{{ digits($stats['inside']) }}</p><p class="text-[13px] text-ink-3">{{ __('checked in') }} · {{ digits($stats['families']) }} {{ __('families') }}</p></div>
        <div class="bg-card p-4"><p class="font-display text-2xl num {{ $stats['denied'] ? 'text-sindoor' : '' }}">{{ digits($stats['denied']) }}</p><p class="text-[13px] text-ink-3">{{ __('turned away') }}</p></div>
    </div>

    <div class="mt-6 grid xl:grid-cols-12 gap-6 items-start">
        <section class="xl:col-span-8 paper-card overflow-hidden" aria-labelledby="regs-h">
            <h2 id="regs-h" class="px-5 pt-4 pb-3 border-b border-rule font-display text-xl">{{ __('Registrations') }}</h2>
            <div class="overflow-x-auto"><table class="khata-table min-w-[620px]">
                <thead><tr><th scope="col">{{ __('Family') }}</th><th scope="col" class="amt">{{ __('People') }}</th><th scope="col">{{ __('At the gate') }}</th><th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
                <tbody>
                    @forelse ($registrations as $registration)
                        <tr class="{{ $registration->isActive() ? '' : 'text-ink-3' }}">
                            <td>
                                <span class="block font-semibold">{{ $registration->family->tr('head_name') }}</span>
                                <span class="block text-[13px] text-ink-3 num">{{ $registration->family->family_no }}@if ($registration->family->area) · {{ $registration->family->tr('area') }}@endif</span>
                            </td>
                            <td class="amt">{{ digits($registration->people) }}</td>
                            <td class="text-[14px]">
                                @if (! $registration->isActive())
                                    <span class="chip bg-paper-2 text-ink-2">{{ __('Cancelled') }}</span>
                                @elseif ($registration->pass?->used_at)
                                    <span class="chip bg-tulsi-wash text-tulsi"><x-icon name="check" />{{ local_date($registration->pass->used_at, 'g:i a') }}</span>
                                    <span class="block text-[12.5px] text-ink-3">{{ digits((int) $registration->pass->entered_people) }} {{ __('people') }}@if ($registration->pass->gate) · {{ $registration->pass->gate }}@endif @if ($registration->pass->scanner) · {{ $registration->pass->scanner->name }}@endif</span>
                                @elseif ($registration->pass)
                                    <span class="text-ink-3">{{ __('Not yet') }}</span>
                                @else
                                    <span class="text-ink-3">{{ __('No pass needed') }}</span>
                                @endif
                            </td>
                            <td class="text-right" data-no-print>
                                @if ($registration->isActive() && ! $event->isCancelled())
                                    <form method="POST" action="{{ route('admin.events.registrations.cancel', [$event, $registration]) }}" x-data @submit="if (! confirm({{ Js::from(__('Cancel this registration?')) }})) $event.preventDefault()">
                                        @csrf
                                        <button class="btn btn-ghost btn-sm text-sindoor-deep">{{ __('Cancel') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-ink-2">{{ __('No family has registered yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>

        <div class="xl:col-span-4 space-y-6" data-no-print>
            @unless ($event->isCancelled())
                <form method="POST" action="{{ route('admin.events.registrations.store', $event) }}" class="paper-card p-5 space-y-4">
                    @csrf
                    <h2 class="font-display text-xl">{{ __('Register a family') }}</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <x-field name="family_no" :label="__('Family number')" placeholder="MC-0104" required />
                        <x-field name="people" type="number" min="1" max="50" inputmode="numeric" :label="__('People')" value="1" required />
                    </div>
                    <p class="text-[13px] text-ink-3">{{ __('At the desk the committee can register a family even after registration closes. Seat limits still apply.') }}</p>
                    <button class="btn btn-ink w-full"><x-icon name="user-plus" /><span>{{ __('Register a family') }}</span></button>
                </form>
            @endunless

            <section class="paper-card overflow-hidden" aria-labelledby="gates-h">
                <h2 id="gates-h" class="px-5 pt-4 pb-3 border-b border-rule font-display text-xl">{{ __('Attendance by gate') }}</h2>
                <ul class="divide-y divide-rule text-[15px]">
                    @forelse ($byGate as $row)
                        <li class="px-5 py-2.5 flex"><span>{{ $row->gate ?: __('Gate') }}</span><span class="ml-auto num">{{ digits((int) $row->people) }} {{ __('people') }} · {{ digits((int) $row->scans) }} {{ __('scans') }}</span></li>
                    @empty
                        <li class="px-5 py-4 text-ink-3">{{ __('No one has been scanned in yet.') }}</li>
                    @endforelse
                </ul>
                @if ($stats['reentries'])
                    <p class="px-5 py-2.5 border-t border-rule text-[14px] text-ink-2">{{ __('Let back in after stepping out') }}: <span class="num">{{ digits($stats['reentries']) }}</span></p>
                @endif
            </section>

            @if ($conflicts->isNotEmpty())
                <section class="paper-card overflow-hidden" aria-labelledby="dup-h">
                    <h2 id="dup-h" class="px-5 pt-4 pb-3 border-b border-rule font-display text-xl">{{ __('To look at') }}</h2>
                    <p class="px-5 pt-3 text-[14px] text-ink-2">{{ __('Scans from phones that were offline. The first scan of a pass counts; later ones are duplicates.') }}</p>
                    <ul class="divide-y divide-rule text-[14px]">
                        @foreach ($conflicts as $scan)
                            <li class="px-5 py-2.5">
                                <span class="font-semibold num">{{ $scan->family?->family_no ?? '—' }}</span>
                                · {{ $scan->conflict === \App\Models\PassScan::CONFLICT_DUPLICATE ? __('Duplicate entry') : __('Let in although the pass was cancelled') }}
                                <span class="block text-ink-3">{{ local_date($scan->scanned_at, 'g:i a') }} · {{ $scan->gate ?: __('Gate') }} · {{ $scan->scanner?->name }} · {{ digits($scan->people) }} {{ __('people') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @can('cancel', $event)
                <a href="{{ route('admin.events.cancel', $event) }}" class="block text-center text-sm font-semibold text-sindoor hover:underline">{{ __('Cancel this event and tell everyone') }}</a>
            @endcan
        </div>
    </div>
</x-layouts.admin>
