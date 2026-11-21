@php
    use App\Enums\LedgerAccount;
    use App\Http\Controllers\Admin\NurseryController;
@endphp
<x-layouts.admin :title="__('Nursery stock')">
    @include('admin.nursery.partials.tabs', ['tab' => 'stock'])

    <div class="mt-5 paper-card overflow-x-auto">
        <table class="khata-table min-w-[760px]">
            <thead><tr><th>{{ __('Plant') }}</th><th>{{ __('Pot') }}</th><th class="!text-right">{{ __('Price') }}</th><th class="!text-right">{{ __('In stock') }}</th><th class="!text-right">{{ __('Sold this month') }}</th><th><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
            <tbody>
                @forelse ($plants as $plant)
                    <tr>
                        <td><span class="font-semibold">{{ $plant->tr('name') }}</span>@unless ($plant->active) <span class="chip h-5 px-1.5 text-[11px] bg-paper-2 text-ink-3">{{ __('Not sold') }}</span>@endunless</td>
                        <td class="text-ink-2">{{ $plant->tr('pot') ?: '—' }}</td>
                        <td class="amt">{{ rupees($plant->price) }}</td>
                        <td class="amt font-semibold {{ $plant->stock === 0 ? 'text-ink-3' : ($plant->stock < 10 ? 'text-sindoor' : '') }}">{{ $plant->stock === 0 ? __('Out') : digits($plant->stock) }}</td>
                        <td class="amt text-ink-2">{{ digits($sold[$plant->id] ?? 0) }}</td>
                        <td class="text-right">
                            <details class="inline-block text-left relative">
                                <summary class="btn btn-ghost btn-sm cursor-pointer">{{ __('Stock in') }}</summary>
                                <form method="POST" action="{{ route('admin.nursery.stock.in', $plant) }}" class="absolute right-0 z-10 mt-1 w-72 paper-card p-4 space-y-3 shadow-lg">
                                    @csrf
                                    <div class="grid grid-cols-2 gap-3">
                                        <x-field name="qty" id="in-qty-{{ $plant->id }}" type="number" min="1" inputmode="numeric" :label="__('Quantity')" required />
                                        <x-field name="cost" id="in-cost-{{ $plant->id }}" type="number" min="0" inputmode="numeric" :label="__('Cost per plant')" />
                                    </div>
                                    <x-field name="from" id="in-from-{{ $plant->id }}" :label="__('From')" :placeholder="__('e.g. Galiff Street wholesale')" maxlength="120" />
                                    <div>
                                        <label for="in-acc-{{ $plant->id }}" class="label">{{ __('Paid from') }}</label>
                                        <select id="in-acc-{{ $plant->id }}" name="account" class="field">
                                            @foreach (LedgerAccount::cases() as $account)<option value="{{ $account->value }}">{{ $account->label() }}</option>@endforeach
                                        </select>
                                    </div>
                                    <p class="text-[13px] text-ink-3">{{ __('A cost goes into the Nursery book as Plants and pots.') }}</p>
                                    <button class="btn btn-primary btn-sm w-full">{{ __('Add to stock') }}</button>
                                </form>
                            </details>
                            @if ($plant->stock > 0)
                                <details class="inline-block text-left relative">
                                    <summary class="btn btn-ghost btn-sm text-ink-2 cursor-pointer">{{ __('Loss') }}</summary>
                                    <form method="POST" action="{{ route('admin.nursery.stock.loss', $plant) }}" class="absolute right-0 z-10 mt-1 w-64 paper-card p-4 space-y-3 shadow-lg">
                                        @csrf
                                        <x-field name="qty" id="loss-qty-{{ $plant->id }}" type="number" min="1" :max="$plant->stock" inputmode="numeric" :label="__('Quantity')" :value="1" required />
                                        <div>
                                            <label for="loss-why-{{ $plant->id }}" class="label">{{ __('Reason') }}</label>
                                            <select id="loss-why-{{ $plant->id }}" name="reason" class="field">
                                                @foreach (NurseryController::lossReasons() as $key => $reason)<option value="{{ $key }}">{{ __($reason['label']) }}</option>@endforeach
                                            </select>
                                        </div>
                                        <button class="btn btn-line btn-sm w-full text-sindoor-deep">{{ __('Record loss') }}</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-ink-3">{{ __('No plants in the catalogue yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($moves->isNotEmpty())
        <section class="mt-8" aria-labelledby="moves-h">
            <h2 id="moves-h" class="font-display text-xl">{{ __('Recent stock changes') }}</h2>
            <ul class="mt-2 paper-card divide-y divide-rule">
                @foreach ($moves as $move)
                    <li class="px-4 py-2.5 flex flex-wrap items-center gap-x-3 text-[15px]">
                        <span class="font-semibold">{{ $move->plant->tr('name') }}</span>
                        <span class="text-ink-2">{{ $move->kind->label() }}@if ($move->ref) · {{ __($move->ref) }}@endif</span>
                        <span class="ml-auto num {{ $move->qty < 0 ? 'text-sindoor' : 'text-tulsi' }}">{{ $move->qty > 0 ? '+' : '−' }}{{ digits(abs($move->qty)) }}</span>
                        <span class="w-full text-[13px] text-ink-3">{{ local_date($move->created_at, 'j M, g:i a') }}@if ($move->recorder) · {{ $move->recorder->name }}@endif @if ($move->cost) · {{ __(':cost each', ['cost' => rupees($move->cost)]) }}@endif</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.admin>
