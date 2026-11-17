@php
    $club = $site->club();
    $amounts = $dues->mapWithKeys(fn ($due) => [(string) $due->id => $due->amount]);
@endphp
<x-layouts.member :title="__('Pay dues')">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Pay subscription') }}</h1>
            <p class="text-ink-2 num">{{ __('Family') }} {{ $family->family_no }} · {{ __(':amount a month covers your whole household.', ['amount' => rupees((int) $club['monthly_fee'])]) }}</p>
        </div>
        <a href="{{ route('member.dues') }}" class="btn btn-line btn-sm"><x-icon name="scroll-text" /><span>{{ __('Statement') }}</span></a>
    </div>

    @foreach ($waiting as $pending)
        <div class="mt-5 rounded-lg border-2 border-marigold bg-marigold-wash/60 p-5" role="status">
            <div class="flex items-start gap-3">
                <span class="w-11 h-11 rounded-full bg-white grid place-items-center shrink-0"><x-icon name="hourglass" class="w-5 h-5 text-turmeric" /></span>
                <div class="min-w-0">
                    <p class="font-semibold">{{ __('Payment sent, waiting for the treasurer') }}</p>
                    <p class="text-[15px] text-ink-2 mt-0.5">{{ rupees($pending->amount) }} · {{ $pending->monthsLabel() }} · {{ __('UTR') }} <span class="num">{{ $pending->upi_ref }}</span></p>
                    <p class="text-sm text-ink-2 mt-1">{{ __('Usually confirmed the same evening. Your receipt will appear in the app.') }}</p>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($rejected as $payment)
        <div class="mt-5 rounded-lg border border-sindoor/40 bg-sindoor-wash p-4 text-[15px]" role="alert">
            <p class="font-semibold text-sindoor-deep">{{ __('The treasurer could not find your UPI payment of :amount (UTR :utr).', ['amount' => rupees($payment->amount), 'utr' => $payment->upi_ref]) }}</p>
            <p class="text-ink-2 mt-0.5">{{ __('Reason') }}: {{ $payment->note }}</p>
        </div>
    @endforeach

    @if ($dues->isEmpty())
        <section class="mt-6 rounded-lg bg-tulsi text-white p-6 relative overflow-hidden">
            <p class="text-[15px] flex items-center gap-1.5"><x-icon name="badge-check" class="w-5 h-5" />{{ __('All paid up') }}</p>
            <p class="font-display text-[1.8rem] leading-tight mt-1">{{ $waiting->isNotEmpty() ? __('Nothing else to pay right now.') : __('Thank you. Your next month is due on the 1st.') }}</p>
            <a href="{{ route('member.receipts.index') }}" class="btn btn-light btn-sm mt-4"><x-icon name="receipt-text" /><span>{{ __('Receipts') }}</span></a>
        </section>
    @else
        <form method="POST" action="{{ route('member.pay.store') }}" class="mt-5 grid gap-5 lg:gap-6 lg:grid-cols-[minmax(0,1fr)_380px] items-start"
            x-data="{ amounts: @js($amounts), picked: @js($dues->pluck('id')->map(fn ($id) => (string) $id)->values()), get total() { return this.picked.reduce((sum, id) => sum + (this.amounts[id] || 0), 0); } }">
            @csrf
            <div class="min-w-0">
                <div class="flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-ink text-white text-[13px] font-bold grid place-items-center num">{{ digits(1) }}</span><h2 class="font-semibold">{{ __('Choose months') }}</h2></div>
                <fieldset class="mt-3">
                    <legend class="sr-only">{{ __('Months') }}</legend>
                    <ul class="rounded-lg border border-rule bg-card divide-y divide-rule">
                        @foreach ($dues as $due)
                            <li><label class="flex items-center gap-3 px-4 h-14 cursor-pointer hover:bg-paper-2/50">
                                <input type="checkbox" name="dues[]" value="{{ $due->id }}" x-model="picked" @checked(in_array((string) $due->id, array_map('strval', old('dues', $dues->pluck('id')->all())), true)) class="w-5 h-5 accent-[#d21f1b]" />
                                <span class="flex-1">{{ local_date($due->period, 'F Y') }}</span>
                                <span class="chip bg-sindoor-wash text-sindoor-deep">{{ __('Due') }}</span>
                                <span class="font-semibold w-16 text-right num">{{ rupees($due->amount) }}</span>
                            </label></li>
                        @endforeach
                    </ul>
                </fieldset>
                <x-field-error name="dues" />
                <x-field-error name="dues.0" />
                <p class="mt-3 text-sm text-ink-3">{{ __('Want to pay months in advance? Pay at the club and the treasurer records them.') }}</p>
            </div>

            <div class="min-w-0 lg:sticky lg:top-24">
                <div class="flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-ink text-white text-[13px] font-bold grid place-items-center num">{{ digits(2) }}</span><h2 class="font-semibold">{{ __('Pay') }}</h2></div>
                <div class="mt-3 rounded-lg bg-card border border-rule p-5 text-center">
                    <p class="text-sm text-ink-2">{{ __('To pay') }}</p>
                    <p class="font-display text-[2.6rem] leading-none mt-1 num">₹<span x-text="total.toLocaleString('{{ app()->getLocale() === 'bn' ? 'bn-IN' : 'en-IN' }}')">{{ number_format((int) $dues->sum('amount')) }}</span></p>
                    @if ($online)
                        <button class="btn btn-primary w-full h-12 mt-4" :disabled="picked.length === 0"><x-icon name="credit-card" /><span>{{ __('Pay online') }}</span></button>
                        <p class="mt-3 text-[13px] text-ink-3">{{ __('UPI, cards and netbanking through Razorpay. Your receipt appears here as soon as the payment goes through.') }}</p>
                    @else
                        <button class="btn btn-primary w-full h-12 mt-4" :disabled="picked.length === 0"><x-icon name="smartphone" /><span>{{ __('Pay by UPI') }}</span></button>
                        <p class="mt-3 text-[13px] text-ink-3">{{ __('Next you get the club’s UPI QR code. After paying, type the 12-digit UTR and the treasurer confirms it.') }}</p>
                    @endif
                </div>

                <div class="mt-4 rounded-lg bg-card border border-rule p-5">
                    <p class="font-semibold flex items-center gap-2"><x-icon name="banknote" class="w-5 h-5 text-sindoor" />{{ __('Cash at the club') }}</p>
                    <p class="text-[15px] text-ink-2 mt-1 leading-relaxed">{{ __('Give it to the treasurer at the club room, 6–9 pm on any evening. The receipt appears in this app; no paper slip needed.') }}</p>
                    <p class="mt-2 text-[14px] text-ink-2">{{ local_text($club['address'], $club['address_bn']) }}</p>
                </div>
            </div>
        </form>
    @endif
</x-layouts.member>
