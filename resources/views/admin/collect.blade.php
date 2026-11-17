@php
    $initialMonths = (int) old('months', $payable->count() ?: 1);
    $initialMode = old('mode', 'cash');
@endphp
<x-layouts.admin :title="__('Collect payment')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Collect payment') }}</h1>
    <p class="mt-1 text-ink-2">{{ __('Cash in hand or UPI. The receipt goes to the member’s app at once.') }}</p>

    <div class="mt-6 grid lg:grid-cols-12 gap-6 items-start">
        <div class="lg:col-span-7 space-y-6">
            {{-- 1 · Family --}}
            <section class="paper-card p-5 sm:p-6" aria-labelledby="fam-h">
                <h2 id="fam-h" class="label">{{ digits(1) }} · {{ __('Family') }}</h2>
                <form method="GET" action="{{ route('admin.collect') }}" role="search" class="flex gap-2">
                    <label for="fam-q" class="sr-only">{{ __('Type a name, family no. or phone') }}</label>
                    <input id="fam-q" name="q" value="{{ $query }}" class="field" placeholder="{{ __('Type a name, family no. or phone') }}" autocomplete="off" @if (! $family) autofocus @endif />
                    <button class="btn btn-ink shrink-0"><x-icon name="search" /><span class="sr-only sm:not-sr-only">{{ __('Find') }}</span></button>
                </form>
                @if ($query !== '')
                    <ul class="mt-3 rounded-lg border border-rule divide-y divide-rule bg-card overflow-hidden">
                        @forelse ($matches as $match)
                            <li><a href="{{ route('admin.collect', ['family' => $match->id, 'q' => $query]) }}" @if ($family?->id === $match->id) aria-current="true" @endif class="px-3 py-2.5 flex items-center gap-2 hover:bg-paper-2 aria-[current=true]:bg-paper-2">
                                <span class="font-semibold">{{ $match->tr('head_name') }}</span><span class="text-sm text-ink-3 num">{{ $match->family_no }}</span>
                                <span class="ml-auto text-sm {{ $match->due_total ? 'font-semibold' : 'text-ink-3' }}">{{ $match->due_total ? __(':amount due', ['amount' => rupees((int) $match->due_total)]) : __('paid up') }}</span>
                            </a></li>
                        @empty
                            <li class="px-3 py-3 text-ink-3">{{ __('No family matches') }}</li>
                        @endforelse
                    </ul>
                @endif
                @if ($family)
                    <p class="mt-4 font-semibold text-lg">{{ $family->tr('head_name') }} <span class="num text-ink-3 font-normal">· {{ $family->family_no }} · {{ $family->tr('area') }}</span></p>
                    <p class="text-sm mt-1 {{ $payable->isNotEmpty() ? 'text-sindoor' : 'text-tulsi' }}">
                        @if ($payable->isNotEmpty())
                            {{ __(':count months due (:months)', ['count' => digits($payable->count()), 'months' => $payable->map(fn ($due) => local_date($due->period, 'M y'))->implode(', ')]) }}
                        @else
                            {{ __('Paid up. Anything now is advance.') }}
                        @endif
                    </p>
                @endif
            </section>

            @if ($family && ! $done)
                <form method="POST" action="{{ route('admin.collect.store') }}" class="paper-card p-5 sm:p-6 space-y-6"
                    x-data="{ months: {{ $initialMonths }}, mode: @js($initialMode), dues: @js($dueAmounts), fee: {{ $fee }},
                        get amount() { let total = 0; for (let i = 0; i < this.months; i++) { total += i < this.dues.length ? this.dues[i] : this.fee; } return total; } }">
                    @csrf
                    <input type="hidden" name="family_id" value="{{ $family->id }}">
                    <fieldset>
                        <legend class="label">{{ digits(2) }} · {{ __('How many months?') }}</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($options as $m)
                                <label class="cursor-pointer">
                                    <input type="radio" name="months" value="{{ $m }}" x-model.number="months" class="peer sr-only" @checked($initialMonths === $m)>
                                    <span class="h-12 min-w-16 px-4 rounded-lg border border-rule bg-card font-semibold inline-flex flex-col items-center justify-center peer-checked:bg-ink peer-checked:text-paper peer-checked:border-ink peer-focus-visible:ring-2 peer-focus-visible:ring-sindoor">
                                        <span>{{ digits($m) }} <span class="font-normal text-sm opacity-75">{{ $m === 1 ? __('month') : __('months') }}</span></span>
                                        @if ($m === $payable->count())<span class="block text-[11px] font-semibold opacity-80 -mt-0.5">{{ __('clears dues') }}</span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <x-field-error name="months" />
                    </fieldset>
                    <fieldset>
                        <legend class="label">{{ digits(3) }} · {{ __('Paid by') }}</legend>
                        <div class="grid grid-cols-2 gap-2 max-w-sm">
                            @foreach (['cash' => ['banknote', __('Cash')], 'upi' => ['smartphone', __('UPI')]] as $value => [$icon, $label])
                                <label class="cursor-pointer">
                                    <input type="radio" name="mode" value="{{ $value }}" x-model="mode" class="peer sr-only" @checked($initialMode === $value)>
                                    <span class="h-12 rounded-lg border border-rule bg-card font-semibold flex items-center justify-center gap-2 peer-checked:bg-ink peer-checked:text-paper peer-checked:border-ink peer-focus-visible:ring-2 peer-focus-visible:ring-sindoor"><x-icon :name="$icon" class="w-5 h-5" />{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-3 max-w-sm" x-show="mode === 'upi'" x-cloak>
                            <label for="c-utr" class="label">{{ __('UTR (12 digits)') }}</label>
                            <input id="c-utr" name="utr" value="{{ old('utr') }}" class="field num" inputmode="numeric" maxlength="14" placeholder="412356789012" autocomplete="off" @error('utr') aria-invalid="true" aria-describedby="utr-error" @enderror />
                            <x-field-error name="utr" />
                        </div>
                    </fieldset>
                    <button class="btn btn-primary h-14 w-full text-[17px]">{{ __('Record') }} <span class="num" x-text="'₹' + amount.toLocaleString('{{ app()->getLocale() === 'bn' ? 'bn-IN' : 'en-IN' }}')"></span> {{ __('and issue receipt') }}</button>
                </form>
            @endif
        </div>

        <div class="lg:col-span-5">
            @if ($done)
                <p class="text-sm font-semibold text-ink-3 mb-2">{{ __('Receipt issued') }}</p>
                @include('partials.receipt', ['payment' => $done])
                @php($whatsapp = \App\Support\DueReminder::link($done->family, \App\Support\DueReminder::receiptMessage((string) $done->receipt_no, $done->amount)))
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @if ($whatsapp)
                        <a target="_blank" rel="noopener" href="{{ $whatsapp }}" class="btn btn-line btn-sm"><x-icon name="message-circle" /><span>{{ __('Also send on WhatsApp') }}</span></a>
                    @endif
                    <a href="{{ route('admin.receipts.show', $done) }}" class="btn btn-line btn-sm"><x-icon name="printer" /><span>{{ __('Print') }}</span></a>
                </div>
                <a href="{{ route('admin.collect') }}" class="btn btn-ink w-full h-12 mt-3">{{ __('Collect from another family') }}</a>
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-sindoor">{{ __('Undo: cancel this receipt…') }}</summary>
                    <form method="POST" action="{{ route('admin.payments.cancel', $done) }}" class="mt-2 space-y-2">
                        @csrf
                        <label for="undo-reason" class="label">{{ __('Reason') }}</label>
                        <input id="undo-reason" name="reason" class="field" value="{{ __('Entered by mistake') }}" required maxlength="200">
                        <button class="btn btn-line btn-sm">{{ __('Cancel receipt') }}</button>
                        <p class="text-[13px] text-ink-3">{{ __('The number stays in the register marked cancelled, and a reversing entry goes into the khata.') }}</p>
                    </form>
                </details>
            @elseif ($family)
                <p class="text-sm font-semibold text-ink-3 mb-2">{{ __('Receipt preview') }}</p>
                <div class="receipt-paper relative bg-card border border-rule rounded-lg p-6">
                    <div class="flex items-center gap-3 border-b border-ink/20 pb-3">
                        <x-seal :size="48" :rotate="-4" />
                        <div class="leading-tight"><p class="font-display text-xl text-sindoor">{{ __('Gede Maitree Club') }}</p><p class="text-[11px] tracking-[0.14em] font-semibold text-ink-3">{{ __('MONEY RECEIPT') }}</p></div>
                        <p class="ml-auto text-right text-sm"><span class="block text-ink-3">{{ __('Receipt') }}</span><span class="font-semibold">{{ __('next no.') }}</span></p>
                    </div>
                    <p class="mt-4 text-[15px] leading-relaxed">{{ __('Received with thanks from') }} <strong>{{ $family->tr('head_name') }}</strong> (<span class="num">{{ $family->family_no }}</span>), {{ __('towards subscription.') }}</p>
                    <p class="mt-4 text-sm text-ink-3">{{ auth()->user()?->name }} · {{ local_date(now()) }}</p>
                </div>
            @else
                <div class="paper-card p-6 text-ink-2">
                    <x-icon name="hand-coins" class="w-8 h-8 text-sindoor" />
                    <p class="mt-2">{{ __('Find the family by name, family number or phone, pick the months and record. The receipt number comes from the Club book series and cannot be reused.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
