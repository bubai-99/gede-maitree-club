<x-layouts.admin :title="__('Dues') . ' · ' . $family->family_no">
    <a href="{{ route('admin.dues.index') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Dues & reminders') }}</a>
    <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ $family->tr('head_name') }}</h1>
            <p class="text-ink-2 num">{{ $family->family_no }} · {{ $family->tr('area') }}@if ($family->phone) · <a href="tel:+91{{ $family->phone }}" class="hover:underline">{{ $family->phone }}</a>@endif</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.collect', ['family' => $family->id]) }}" class="btn btn-primary btn-sm"><x-icon name="hand-coins" /><span>{{ __('Collect payment') }}</span></a>
        </div>
    </div>
    <p class="mt-3 text-[15px]">{{ __('Owes') }} <strong class="num {{ $owed ? 'text-sindoor' : 'text-tulsi' }}">{{ rupees($owed) }}</strong></p>

    <form method="POST" action="{{ route('admin.dues.waive', $family) }}" class="mt-5 grid lg:grid-cols-12 gap-6 items-start" x-data="{ picked: [] }">
        @csrf
        <div class="lg:col-span-8 paper-card overflow-x-auto">
            <table class="khata-table min-w-[560px]">
                <thead><tr><th class="w-10 !pr-0"><span class="sr-only">{{ __('Select') }}</span></th><th scope="col">{{ __('Month') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Details') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th></tr></thead>
                <tbody>
                    @forelse ($dues as $due)
                        @php($payableDue = in_array($due->id, $payable, true))
                        <tr>
                            <td class="!pr-0">@if ($payableDue)<input type="checkbox" name="dues[]" value="{{ $due->id }}" x-model="picked" class="w-4 h-4 accent-[#2b2f36] align-middle" aria-label="{{ local_date($due->period, 'F Y') }}">@endif</td>
                            <td class="whitespace-nowrap">{{ local_date($due->period, 'F Y') }}</td>
                            <td>
                                @php($waiting = $due->payments->contains(fn ($p) => $p->status === \App\Enums\PaymentStatus::AwaitingConfirmation))
                                <span class="chip {{ match ($due->status) { \App\Enums\DueStatus::Paid => 'bg-tulsi-wash text-tulsi', \App\Enums\DueStatus::Waived => 'bg-paper-2 text-ink-2', default => $waiting ? 'bg-marigold-wash text-turmeric' : 'bg-sindoor-wash text-sindoor-deep' } }}">{{ $waiting && $due->status === \App\Enums\DueStatus::Due ? __('Checking') : $due->status->label() }}</span>
                            </td>
                            <td class="text-[14px] text-ink-2">
                                @if ($due->status === \App\Enums\DueStatus::Waived)
                                    {{ $due->waived_reason }}@if ($due->waiver) · {{ $due->waiver->name }}@endif
                                @else
                                    @foreach ($due->payments->where('status', \App\Enums\PaymentStatus::Confirmed) as $payment)
                                        <a href="{{ route('admin.receipts.show', $payment) }}" class="num hover:underline">{{ $payment->receipt_no }}</a>
                                    @endforeach
                                @endif
                            </td>
                            <td class="amt">{{ rupees($due->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="!py-12 text-center text-ink-3">{{ __('Nothing here yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="lg:col-span-4 paper-card p-5 space-y-3">
            <h2 class="font-display text-xl">{{ __('Waive months') }}</h2>
            <p class="text-sm text-ink-2">{{ __('For hardship or honorary members. Tick the months, give the reason, and they no longer count as due. It goes in the audit log.') }}</p>
            <div>
                <label for="waive-reason" class="label">{{ __('Reason') }}</label>
                <input id="waive-reason" name="reason" value="{{ old('reason') }}" class="field" maxlength="200" required @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror>
                <x-field-error name="reason" />
                <x-field-error name="dues" />
            </div>
            <button class="btn btn-ink w-full" :disabled="picked.length === 0">{{ __('Waive selected months') }}</button>
        </div>
    </form>
</x-layouts.admin>
