@php
    $target = max($summary['target'], 1);
    $doorPct = min(100, round($summary['door'] * 100 / $target, 1));
    $sponsorPct = min(100 - $doorPct, round($summary['sponsors'] * 100 / $target, 1));
    $toGo = max(0, $summary['target'] - $summary['collected']);
    $canWrite = auth()->user()?->can('recordIn', [\App\Models\ChandaEntry::class, null]) || $areas->isNotEmpty();
@endphp
<x-layouts.admin :title="__('Puja chanda')">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ $event->tr('title') }}</h1>
            <p class="mt-1 text-ink-2">{{ local_date($event->starts_at, 'j F Y') }} · {{ __('Receipts in the chanda series, money in the Club book') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($events->count() > 1)
                <form method="GET" action="{{ route('admin.chanda.index') }}" x-data class="flex">
                    <label for="pick-event" class="sr-only">{{ __('Event') }}</label>
                    <select id="pick-event" class="field h-9 py-0 text-sm" @change="window.location = $event.target.value">
                        @foreach ($events as $option)
                            <option value="{{ route('admin.chanda.show', $option) }}" @selected($option->is($event))>{{ $option->tr('title') }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            <a href="{{ route('admin.chanda.receipts', $event) }}" class="btn btn-line btn-sm"><x-icon name="receipt" /><span>{{ __('Receipts') }}</span></a>
            @if ($canWrite)
                <a href="{{ route('admin.chanda.create', $event) }}" class="btn btn-ink btn-sm"><x-icon name="plus" /><span>{{ __('Record chanda') }}</span></a>
            @endif
        </div>
    </div>

    @if ($manager)
        <section class="mt-5 paper-card p-5 sm:p-6" aria-labelledby="total-h">
            <div class="flex flex-wrap items-end gap-x-8 gap-y-2">
                <div><p id="total-h" class="text-sm text-ink-3">{{ __('Collected so far') }}</p><p class="font-display text-[2.6rem] leading-none text-sindoor mt-1 num">{{ rupees($summary['collected']) }}</p></div>
                <p class="text-[15px] text-ink-2 pb-1">{{ __('Target') }} {{ rupees($summary['target']) }} · {{ __('Budget') }} {{ rupees($summary['budget']) }} · <span class="font-semibold text-ink">{{ rupees($toGo) }} {{ __('to go') }}</span></p>
            </div>
            <div class="mt-4 h-4 rounded-full bg-paper-2 overflow-hidden flex" role="img" aria-label="{{ __('Door to door') }} {{ rupees($summary['door']) }}, {{ __('Sponsors') }} {{ rupees($summary['sponsors']) }}">
                <div class="h-full bg-sindoor" style="width: {{ $doorPct }}%"></div>
                <div class="h-full bg-marigold" style="width: {{ $sponsorPct }}%"></div>
            </div>
            <div class="mt-2 flex flex-wrap gap-5 text-sm text-ink-2">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-sindoor"></span>{{ __('Door to door') }} {{ rupees($summary['door']) }}</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-marigold"></span>{{ __('Sponsors') }} {{ rupees($summary['sponsors']) }}</span>
                @if ($summary['promised'])<span>{{ __('Promised, not yet received') }} {{ rupees($summary['promised']) }}</span>@endif
            </div>
            <dl class="mt-5 grid grid-cols-3 gap-px bg-rule border border-rule rounded-lg overflow-hidden text-center">
                <div class="bg-card p-3"><dt class="text-[13px] text-ink-3">{{ __('Budget') }}</dt><dd class="font-display text-xl num">{{ rupees($summary['budget']) }}</dd></div>
                <div class="bg-card p-3"><dt class="text-[13px] text-ink-3">{{ __('Collected') }}</dt><dd class="font-display text-xl num">{{ rupees($summary['collected']) }}</dd></div>
                <div class="bg-card p-3"><dt class="text-[13px] text-ink-3">{{ __('Spent') }}</dt><dd class="font-display text-xl num {{ $summary['spent'] > $summary['budget'] && $summary['budget'] ? 'text-sindoor' : '' }}">{{ rupees($summary['spent']) }}</dd></div>
            </dl>
            <p class="mt-2 text-[13px] text-ink-3">{{ __('Spent: khoroch in the khata marked for this event.') }} · {{ $event->chanda_public ? __('The website shows these totals (no donor names unless the donor agreed).') : __('The website does not show these totals yet.') }}</p>
        </section>
    @elseif ($myCash)
        <p class="mt-5 rounded-lg bg-marigold-wash text-turmeric px-4 py-3 text-[15px]">{{ __('You hold :amount chanda cash from :count receipts. Hand it to the treasurer within 7 days.', ['amount' => rupees($myCash->amount), 'count' => digits($myCash->receipts)]) }}</p>
    @endif

    <div class="grid xl:grid-cols-12 gap-6 mt-6 items-start">
        <section class="xl:col-span-8 paper-card overflow-hidden" aria-labelledby="areas-h">
            <div class="px-5 pt-4 pb-3 border-b border-rule"><h2 id="areas-h" class="font-display text-xl">{{ $manager ? __('By area') : __('My areas') }}</h2></div>
            <div class="overflow-x-auto"><table class="khata-table min-w-[640px]">
                <thead><tr><th scope="col">{{ __('Area') }}</th><th scope="col">{{ __('Volunteer') }}</th><th scope="col">{{ __('Houses visited') }}</th><th scope="col" class="w-[32%]">{{ __('Collected / target') }}</th><th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
                <tbody>
                    @forelse ($areas as $area)
                        @php
                            $got = $summary['by_area'][$area->id] ?? 0;
                            $pct = $area->target > 0 ? min(100, (int) round($got * 100 / $area->target)) : 0;
                        @endphp
                        <tr>
                            <td class="font-semibold">{{ $area->tr('name') }}</td>
                            <td class="text-ink-2">{{ $area->collector?->name ?? '—' }}</td>
                            <td class="num">@if ($area->houses){{ digits((int) $area->visited) }} / {{ digits($area->houses) }}@else — @endif</td>
                            <td><div class="flex items-center gap-3"><div class="flex-1 h-2 rounded-full bg-paper-2 overflow-hidden"><div class="h-full rounded-full {{ $pct >= 80 ? 'bg-tulsi' : ($pct >= 50 ? 'bg-marigold' : 'bg-sindoor') }}" style="width: {{ $pct }}%"></div></div><span class="text-sm num whitespace-nowrap">{{ rupees($got) }} <span class="text-ink-3">/ {{ rupees($area->target) }}</span></span></div></td>
                            <td class="text-right whitespace-nowrap">
                                @can('recordIn', [\App\Models\ChandaEntry::class, $area])
                                    <a href="{{ route('admin.chanda.create', [$event, 'area' => $area->id]) }}" class="btn btn-ghost btn-sm"><x-icon name="plus" /><span class="sr-only sm:not-sr-only">{{ __('Record') }}</span></a>
                                @endcan
                            </td>
                        </tr>
                        @if ($manager)
                            <tr class="bg-paper-2/40" x-data="{ open: false }">
                                <td colspan="5" class="!py-1">
                                    <button type="button" class="text-[13px] font-semibold text-ink-2 hover:text-ink" @click="open = ! open" :aria-expanded="open.toString()">{{ __('Edit area') }}</button>
                                    <form x-show="open" x-cloak method="POST" action="{{ route('admin.chanda.areas.update', $area) }}" class="mt-2 mb-2 grid sm:grid-cols-6 gap-2 items-end">
                                        @csrf @method('PUT')
                                        @include('admin.chanda.partials.area-fields', ['area' => $area, 'prefix' => 'a'.$area->id])
                                        <button class="btn btn-ink btn-sm">{{ __('Save') }}</button>
                                    </form>
                                    @if (! ($summary['by_area'][$area->id] ?? 0))
                                        <form x-show="open" x-cloak method="POST" action="{{ route('admin.chanda.areas.destroy', $area) }}" class="mb-2">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-sindoor-deep"><x-icon name="trash-2" /><span>{{ __('Remove area') }}</span></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-ink-2">{{ $manager ? __('No areas yet. Add the lanes and who collects in each.') : __('No area is assigned to you. Ask the Puja in-charge.') }}</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            @if ($manager)
                <form method="POST" action="{{ route('admin.chanda.areas.store', $event) }}" class="border-t border-rule px-5 py-4 grid sm:grid-cols-6 gap-2 items-end">
                    @csrf
                    <p class="sm:col-span-6 font-semibold">{{ __('Add an area') }}</p>
                    @include('admin.chanda.partials.area-fields', ['area' => null, 'prefix' => 'new'])
                    <button class="btn btn-ink btn-sm"><x-icon name="plus" /><span>{{ __('Add') }}</span></button>
                </form>
            @endif
        </section>

        <div class="xl:col-span-4 space-y-6">
            <section class="paper-card" aria-labelledby="sp-h">
                <div class="px-5 pt-4 pb-3 border-b border-rule flex items-center"><h2 id="sp-h" class="font-display text-xl">{{ __('Sponsors') }}</h2></div>
                <ul class="px-5">
                    @forelse ($sponsors as $sponsor)
                        <li class="py-3 border-b border-rule last:border-0 flex items-start gap-3" x-data="{ open: false }">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold">{{ $sponsor->tr('name') }}</p>
                                <p class="text-sm text-ink-3">{{ $sponsor->tr('deliverable') }}</p>
                                @if ($manager && ! $sponsor->isReceived())
                                    <form x-show="open" x-cloak method="POST" action="{{ route('admin.chanda.sponsors.receive', $sponsor) }}" class="mt-2 grid grid-cols-2 gap-2">
                                        @csrf
                                        <label class="sr-only" for="sp-amt-{{ $sponsor->id }}">{{ __('Amount') }}</label>
                                        <input id="sp-amt-{{ $sponsor->id }}" name="amount" type="number" min="1" class="field h-9 num" value="{{ $sponsor->amount }}" required>
                                        <label class="sr-only" for="sp-mode-{{ $sponsor->id }}">{{ __('Paid by') }}</label>
                                        <select id="sp-mode-{{ $sponsor->id }}" name="mode" class="field h-9 py-0">@foreach (\App\Enums\ChandaMode::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                                        <label class="sr-only" for="sp-utr-{{ $sponsor->id }}">{{ __('UTR (for UPI)') }}</label>
                                        <input id="sp-utr-{{ $sponsor->id }}" name="upi_ref" class="field h-9 num col-span-2" placeholder="{{ __('UTR (for UPI)') }}">
                                        <button class="btn btn-ink btn-sm col-span-2">{{ __('Record and send receipt') }}</button>
                                    </form>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-semibold num">{{ rupees($sponsor->amount) }}</p>
                                @if ($sponsor->isReceived())
                                    <p class="text-[12.5px] text-tulsi font-semibold">{{ __('Received') }}</p>
                                @elseif ($manager)
                                    <button type="button" class="text-[12.5px] text-turmeric font-semibold hover:underline" @click="open = ! open">{{ __('Promised · mark received') }}</button>
                                @else
                                    <p class="text-[12.5px] text-turmeric font-semibold">{{ __('Promised') }}</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="py-4 text-ink-3">{{ __('No sponsors yet.') }}</li>
                    @endforelse
                </ul>
                @if ($manager)
                    <form method="POST" action="{{ route('admin.chanda.sponsors.store', $event) }}" class="border-t border-rule px-5 py-4 space-y-2" x-data="{ got: false }">
                        @csrf
                        <p class="font-semibold">{{ __('Add a sponsor') }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label for="sp-name" class="label">{{ __('Business or name') }}</label><input id="sp-name" name="name" class="field" required maxlength="150" value="{{ old('name') }}"></div>
                            <div><label for="sp-name-bn" class="label">{{ __('In Bengali') }}</label><input id="sp-name-bn" name="name_bn" class="field" lang="bn" maxlength="150" value="{{ old('name_bn') }}"></div>
                            <div><label for="sp-amount" class="label">{{ __('Amount') }}</label><input id="sp-amount" name="amount" type="number" min="1" class="field num" required value="{{ old('amount') }}"></div>
                            <div><label for="sp-gives" class="label">{{ __('What they get') }}</label><input id="sp-gives" name="deliverable" class="field" maxlength="150" placeholder="{{ __('Gate banner') }}" value="{{ old('deliverable') }}"></div>
                        </div>
                        <label class="flex items-center gap-2 text-[14px]"><input type="checkbox" name="received" value="1" class="w-4 h-4 accent-sindoor" x-model="got"> {{ __('Money already received') }}</label>
                        <div x-show="got" x-cloak class="grid grid-cols-2 gap-2">
                            <select name="mode" class="field" aria-label="{{ __('Paid by') }}">@foreach (\App\Enums\ChandaMode::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                            <input name="upi_ref" class="field num" placeholder="{{ __('UTR (for UPI)') }}" aria-label="{{ __('UTR (for UPI)') }}">
                        </div>
                        <button class="btn btn-ink btn-sm w-full"><x-icon name="plus" /><span>{{ __('Add sponsor') }}</span></button>
                    </form>
                @endif
            </section>

            <section class="paper-card" aria-labelledby="budget-h">
                <div class="px-5 pt-4 pb-3 border-b border-rule flex items-center"><h2 id="budget-h" class="font-display text-xl">{{ __('Budget') }}</h2>
                    @if ($manager)
                        <a href="{{ route('admin.chanda.budget', $event) }}" class="ml-auto btn btn-ghost btn-sm text-ink-2"><x-icon name="pencil" /><span>{{ __('Edit') }}</span></a>
                    @endif
                </div>
                <ul class="px-5 text-[15px]">
                    @forelse ($event->budget ?? [] as $line)
                        <li class="py-2.5 border-b border-rule last:border-0 flex gap-3"><span>{{ local_text($line['head'] ?? '', $line['head_bn'] ?? null) }}</span><span class="ml-auto num">{{ rupees((int) ($line['amount'] ?? 0)) }}</span></li>
                    @empty
                        <li class="py-4 text-ink-3">{{ __('No budget yet.') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>

    <section class="mt-6 paper-card overflow-hidden" aria-labelledby="recent-h">
        <div class="px-5 pt-4 pb-3 border-b border-rule flex items-center"><h2 id="recent-h" class="font-display text-xl">{{ __('Latest receipts') }}</h2>
            <a href="{{ route('admin.chanda.receipts', $event) }}" class="ml-auto text-sm font-semibold text-sindoor hover:underline">{{ __('All receipts') }} →</a></div>
        @include('admin.chanda.partials.receipt-rows', ['receipts' => $recent])
    </section>
</x-layouts.admin>
