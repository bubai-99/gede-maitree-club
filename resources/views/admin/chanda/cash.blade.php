<x-layouts.admin :title="__('Chanda cash')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Chanda cash') }}</h1>
    <p class="mt-1 text-ink-2 max-w-2xl">{{ __('Club rule: collectors hand cash to the treasurer within 7 days. The money is already in the Club book as cash; this records who holds it.') }}</p>

    <div class="mt-6 grid xl:grid-cols-12 gap-6 items-start">
        <section class="xl:col-span-7 paper-card overflow-hidden" aria-labelledby="hold-h">
            <div class="px-5 pt-4 pb-3 border-b border-rule flex items-center"><h2 id="hold-h" class="font-display text-xl">{{ __('With collectors') }}</h2>
                <span class="ml-auto text-sm text-ink-3">{{ __('Counted at handover') }}</span></div>
            <ul class="divide-y divide-rule">
                @forelse ($holders as $holder)
                    <li class="px-5 py-4" x-data="{ open: false }">
                        <div class="flex items-center gap-3">
                            <span class="flex-1 min-w-0">
                                <span class="block font-semibold">{{ $holder->collector->name }}</span>
                                <span class="block text-[13px] text-ink-3">{{ __(':count receipts', ['count' => digits($holder->receipts)]) }} · {{ __('since') }} {{ local_date($holder->since, 'j M') }}
                                    @if ($holder->since->lt(now()->subDays(7)))<span class="chip h-5 px-1.5 text-[11.5px] bg-sindoor-wash text-sindoor-deep">{{ __('Over 7 days') }}</span>@endif</span>
                            </span>
                            <span class="font-display text-xl num">{{ rupees($holder->amount) }}</span>
                            <button type="button" class="btn btn-ink btn-sm" @click="open = ! open" :aria-expanded="open.toString()"><x-icon name="hand-coins" /><span>{{ __('Receive') }}</span></button>
                        </div>
                        <form x-show="open" x-cloak method="POST" action="{{ route('admin.chanda.cash.store') }}" class="mt-3 grid sm:grid-cols-3 gap-2 items-end">
                            @csrf
                            <input type="hidden" name="collector_id" value="{{ $holder->collector->id }}">
                            <div><label for="ho-c-{{ $holder->collector->id }}" class="label">{{ __('Counted') }}</label><input id="ho-c-{{ $holder->collector->id }}" name="counted" type="number" min="0" class="field num" value="{{ $holder->amount }}" required></div>
                            <div class="sm:col-span-2"><label for="ho-n-{{ $holder->collector->id }}" class="label">{{ __('Why is it different? (only if it is)') }}</label><input id="ho-n-{{ $holder->collector->id }}" name="note" class="field" maxlength="250"></div>
                            <label class="sm:col-span-3 flex items-start gap-2 text-[14px]"><input type="checkbox" required class="mt-1 w-4 h-4 accent-sindoor"> {{ __(':count receipt counterfoils checked against the khata', ['count' => digits($holder->receipts)]) }}</label>
                            <button class="btn btn-primary sm:col-span-3">{{ __('Received') }}</button>
                        </form>
                    </li>
                @empty
                    <li class="px-5 py-6 text-ink-3">{{ __('No chanda cash is with collectors.') }}</li>
                @endforelse
            </ul>
        </section>

        <section class="xl:col-span-5 paper-card overflow-hidden" aria-labelledby="ho-h">
            <h2 id="ho-h" class="px-5 pt-4 pb-3 border-b border-rule font-display text-xl">{{ __('Handed over') }}</h2>
            <ul class="divide-y divide-rule text-[15px]">
                @forelse ($handovers as $handover)
                    <li class="px-5 py-3">
                        <div class="flex gap-3"><span class="flex-1">{{ $handover->collector->name }} → {{ $handover->receiver->name }}</span><span class="font-semibold num">{{ rupees($handover->counted) }}</span></div>
                        <p class="text-[13px] text-ink-3">{{ local_date($handover->created_at, 'j M, g:i a') }} · {{ __(':count receipts', ['count' => digits($handover->receipts)]) }}@if ($handover->counted !== $handover->expected) · <span class="text-sindoor-deep">{{ __('Receipts say :amount', ['amount' => rupees($handover->expected)]) }}: {{ $handover->note }}</span>@endif</p>
                    </li>
                @empty
                    <li class="px-5 py-6 text-ink-3">{{ __('Nothing handed over yet.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.admin>
