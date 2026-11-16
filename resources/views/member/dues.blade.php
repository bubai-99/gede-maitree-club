@php($club = $site->club())
<x-layouts.member :title="__('Dues')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Your dues') }}</h1>
    <p class="text-ink-2 num">{{ __('Family') }} {{ $family->family_no }} · {{ rupees((int) $club['monthly_fee']) }} / {{ __('month') }}</p>

    <section class="mt-6 card p-6" aria-labelledby="owe-h">
        <h2 id="owe-h" class="text-[13px] font-semibold uppercase tracking-wide text-ink-3">{{ __('To pay') }}</h2>
        @if ($outstanding > 0)
            <p class="mt-2 font-display text-[2.6rem] leading-none num text-sindoor">{{ rupees($outstanding) }}</p>
            <div class="mt-5 grid sm:grid-cols-2 gap-4">
                <div class="rounded-xl border border-rule p-4">
                    <p class="font-semibold flex items-center gap-2"><x-icon name="smartphone" class="w-5 h-5 text-sindoor" />{{ __('Pay by UPI') }}</p>
                    <p class="mt-1 text-[14px] text-ink-2">{{ __('Scan with any UPI app. Put your name and lane in the note.') }}</p>
                    <p class="mt-2 font-semibold num select-all">{{ $club['upi'] }}</p>
                    <p class="mt-1 text-[13px] text-ink-3">{{ __('The treasurer confirms the payment and your receipt appears here.') }}</p>
                </div>
                <div class="rounded-xl border border-rule p-4">
                    <p class="font-semibold flex items-center gap-2"><x-icon name="banknote" class="w-5 h-5 text-sindoor" />{{ __('I’ll pay cash') }}</p>
                    <p class="mt-1 text-[14px] text-ink-2">{{ __('Daily 6–9 pm. The treasurer gives you a receipt on the spot.') }}</p>
                    <p class="mt-2 text-[14px] text-ink-2">{{ local_text($club['address'], $club['address_bn']) }}</p>
                </div>
            </div>
            @if ($site->isSample())
                <p class="mt-3 text-[13px] text-sindoor-deep">{{ __('Sample UPI ID. Please don’t send money.') }}</p>
            @endif
        @else
            <p class="mt-2 font-display text-[2rem] leading-none text-tulsi">{{ __('All paid up') }}</p>
            <p class="mt-2 text-ink-2">{{ __('Thank you. Your next month is due on the 1st.') }}</p>
        @endif
    </section>

    <section class="mt-6" aria-labelledby="statement-h">
        <h2 id="statement-h" class="font-display text-[1.5rem]">{{ __('Statement') }}</h2>
        <div class="mt-3 overflow-x-auto rounded-xl border border-rule bg-card khata">
            <table class="khata-table">
                <thead><tr><th scope="col" class="pl-16">{{ __('Month') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($dues as $due)
                        <tr>
                            <td class="pl-16">{{ local_date($due->period, 'F Y') }}</td>
                            <td class="amt">{{ rupees($due->amount) }}</td>
                            <td><span class="chip {{ $due->status === \App\Enums\DueStatus::Due ? 'bg-sindoor-wash text-sindoor-deep' : 'bg-tulsi-wash text-tulsi' }}">{{ $due->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="pl-16 text-ink-2">{{ __('Nothing here yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <a href="{{ route('member.receipts.index') }}" class="mt-4 inline-flex items-center gap-1 text-[14px] font-semibold text-sindoor hover:underline">{{ __('Receipts') }}<x-icon name="arrow-right" class="w-4 h-4" /></a>
    </section>
</x-layouts.member>
