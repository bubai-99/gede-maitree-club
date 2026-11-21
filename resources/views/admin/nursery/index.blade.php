@php
    $catalogue = $plants->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->tr('name'),
        'pot' => $p->tr('pot'),
        'price' => $p->price,
        'member' => min($p->member_price, $p->price),
        'stock' => $p->stock,
    ])->values();
    $old = collect(old('lines', []))->map(fn ($q) => (int) $q)->filter()->all();
@endphp
<x-layouts.admin :title="__('Nursery')">
    @include('admin.nursery.partials.tabs', ['tab' => 'counter'])

    <div class="mt-5 grid lg:grid-cols-12 gap-6 items-start"
         x-data="{
            plants: @js($catalogue),
            cart: @js((object) $old),
            family: @js(old('family_no', '')),
            mode: @js(old('mode', 'cash')),
            get member() { return this.family.trim() !== '' },
            plant(id) { return this.plants.find((p) => p.id === Number(id)) },
            qty(id) { return this.cart[id] || 0 },
            add(id) { const p = this.plant(id); if (p && this.qty(id) < p.stock) this.cart[id] = this.qty(id) + 1 },
            less(id) { const q = this.qty(id) - 1; if (q <= 0) delete this.cart[id]; else this.cart[id] = q },
            get lines() { return Object.entries(this.cart).filter(([, q]) => q > 0).map(([id, q]) => ({ p: this.plant(id), q })).filter((l) => l.p) },
            get subtotal() { return this.lines.reduce((a, l) => a + l.p.price * l.q, 0) },
            get total() { return this.lines.reduce((a, l) => a + (this.member ? l.p.member : l.p.price) * l.q, 0) },
            rupees(n) { return '₹' + Number(n).toLocaleString(@js(app()->getLocale() === 'bn' ? 'bn-IN' : 'en-IN')) },
         }">
        <div class="lg:col-span-7 xl:col-span-8">
            <h2 class="sr-only">{{ __('Plants') }}</h2>
            @if ($plants->isEmpty())
                <p class="paper-card p-6 text-ink-2">{{ __('No plants in the catalogue yet.') }}</p>
            @endif
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5">
                @foreach ($plants as $plant)
                    <button type="button" @click="add({{ $plant->id }})" @disabled($plant->stock === 0)
                            :class="qty({{ $plant->id }}) ? 'border-tulsi ring-2 ring-tulsi/25' : 'border-rule'"
                            class="text-left bg-card rounded-lg border p-3 hover:border-tulsi/60 disabled:opacity-45 disabled:cursor-not-allowed relative"
                            aria-label="{{ __('Add :plant', ['plant' => $plant->tr('name')]) }}">
                        <span class="w-9 h-9 rounded-lg bg-tulsi-wash text-tulsi grid place-items-center"><x-icon name="sprout" class="w-5 h-5" /></span>
                        <span x-show="qty({{ $plant->id }})" x-cloak class="absolute top-2.5 right-2.5 chip h-6 px-2 bg-tulsi text-white num" x-text="'×' + qty({{ $plant->id }})"></span>
                        <span class="block font-semibold leading-tight mt-2 text-[15px]">{{ $plant->tr('name') }}</span>
                        <span class="block text-sm text-ink-3 mt-0.5">@if ($plant->tr('pot')){{ $plant->tr('pot') }} · @endif{{ rupees($plant->price) }}</span>
                        <span class="block text-[12.5px] mt-1 {{ $plant->stock === 0 ? 'text-ink-3' : ($plant->stock < 10 ? 'text-sindoor font-semibold' : 'text-ink-3') }}">{{ $plant->stock === 0 ? __('Out of stock') : __(':count in stock', ['count' => digits($plant->stock)]) }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('admin.nursery.store') }}" class="lg:col-span-5 xl:col-span-4 paper-card lg:sticky lg:top-24">
            @csrf
            <div class="p-5 border-b border-rule"><h2 class="font-display text-xl">{{ __('Bill') }}</h2></div>
            <div class="p-5">
                <template x-for="line in lines" :key="line.p.id">
                    <input type="hidden" :name="'lines[' + line.p.id + ']'" :value="line.q">
                </template>
                <p x-show="lines.length === 0" class="text-[15px] text-ink-3 py-6 text-center">{{ __('Tap plants on the left to add them.') }}</p>
                <ul x-show="lines.length" x-cloak class="text-[15px]">
                    <template x-for="line in lines" :key="line.p.id">
                        <li class="flex items-center gap-2 py-2 border-b border-rule">
                            <span class="flex-1" x-text="line.p.name"></span>
                            <span class="flex items-center rounded-md border border-rule">
                                <button type="button" @click="less(line.p.id)" class="w-7 h-7 grid place-items-center" aria-label="{{ __('Less') }}"><x-icon name="minus" class="w-3.5 h-3.5" /></button>
                                <span class="w-6 text-center num" x-text="line.q"></span>
                                <button type="button" @click="add(line.p.id)" class="w-7 h-7 grid place-items-center" aria-label="{{ __('More') }}"><x-icon name="plus" class="w-3.5 h-3.5" /></button>
                            </span>
                            <span class="w-16 text-right num" x-text="rupees((member ? line.p.member : line.p.price) * line.q)"></span>
                        </li>
                    </template>
                </ul>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <x-field name="family_no" :label="__('Family number (if a member)')" placeholder="MC-0104" x-model="family" />
                    <x-field name="buyer_name" :label="__('Buyer (optional)')" maxlength="120" />
                </div>
                <p class="mt-1.5 text-[13px] text-ink-3">{{ __('Members pay the member price (:percent% off).', ['percent' => digits($discount)]) }}</p>

                <dl class="mt-4 text-[15px] space-y-1">
                    <div class="flex"><dt class="text-ink-2">{{ __('Subtotal') }}</dt><dd class="ml-auto num" x-text="rupees(subtotal)"></dd></div>
                    <div class="flex"><dt class="text-ink-2">{{ __('Member discount') }}</dt><dd class="ml-auto num text-tulsi" x-text="'−' + rupees(subtotal - total)"></dd></div>
                    <div class="flex font-semibold text-lg border-t border-ink/20 pt-3 mt-3"><dt>{{ __('To collect') }}</dt><dd class="ml-auto font-display text-2xl num" x-text="rupees(total)"></dd></div>
                </dl>

                <fieldset class="mt-4">
                    <legend class="label">{{ __('Paid by') }}</legend>
                    <div class="flex gap-5">
                        <label class="flex items-center gap-2"><input type="radio" name="mode" value="cash" x-model="mode" class="accent-tulsi"> {{ \App\Enums\PaymentMode::Cash->label() }}</label>
                        <label class="flex items-center gap-2"><input type="radio" name="mode" value="upi" x-model="mode" class="accent-tulsi"> {{ \App\Enums\PaymentMode::Upi->label() }}</label>
                    </div>
                </fieldset>
                <div x-show="mode === 'upi'" x-cloak class="mt-3">
                    <x-field name="upi_ref" :label="__('UTR (for UPI)')" inputmode="numeric" maxlength="40" />
                </div>
                <x-field-error name="lines" />
                <button class="btn w-full h-12 mt-4 bg-tulsi text-white hover:bg-[#1f5029]" :disabled="lines.length === 0"><x-icon name="receipt" />{{ __('Save sale') }}</button>
            </div>
        </form>
    </div>

    <section class="mt-8" aria-labelledby="today-h">
        <h2 id="today-h" class="font-display text-xl">{{ __('Sales today') }} · <span class="num">{{ rupees((int) $sales->whereNull('cancelled_at')->sum('total')) }}</span></h2>
        @if ($sales->isEmpty())
            <p class="mt-2 text-ink-3">{{ __('No sales yet today.') }}</p>
        @else
            <div class="mt-3 paper-card overflow-x-auto">
                <table class="khata-table min-w-[560px]">
                    <thead><tr><th>{{ __('Receipt no.') }}</th><th>{{ __('Time') }}</th><th>{{ __('Buyer') }}</th><th>{{ __('Mode') }}</th><th class="!text-right">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr class="{{ $sale->isCancelled() ? 'text-ink-3 line-through' : '' }}">
                                <td><a href="{{ route('admin.nursery.sale', $sale) }}" class="num font-semibold hover:underline">{{ $sale->receipt_no }}</a></td>
                                <td class="num">{{ local_date($sale->created_at, 'g:i a') }}</td>
                                <td>{{ $sale->buyer_name ?: '—' }}@if ($sale->family) · <span class="num">{{ $sale->family->family_no }}</span>@endif</td>
                                <td>{{ $sale->mode->label() }}</td>
                                <td class="amt">{{ rupees($sale->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.admin>
