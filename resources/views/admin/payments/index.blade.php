@php
    $tabs = [
        'waiting' => __('Waiting for the treasurer'),
        'online' => __('Razorpay'),
        'review' => __('Needs a look'),
        'all' => __('All'),
    ];
@endphp
<x-layouts.admin :title="__('Online payments')">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Online payments') }}</h1>
            <p class="mt-1 text-ink-2">
                @if ($razorpay)
                    {{ __('Members pay with Razorpay; receipts are issued automatically. UPI payments to the club’s own UPI ID wait here for you.') }}
                @else
                    {{ __('Razorpay is not set up, so members pay by UPI to the club and send the UTR. Check each one against the bank and confirm it.') }}
                @endif
            </p>
        </div>
        <a href="{{ route('admin.payments.import') }}" class="btn btn-line btn-sm"><x-icon name="upload" /><span>{{ __('Import bank statement') }}</span></a>
    </div>

    <nav class="mt-5 flex flex-wrap gap-2" aria-label="{{ __('Show') }}">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('admin.payments.index', ['show' => $value]) }}" @if ($show === $value) aria-current="page" @endif
                class="chip h-9 px-3.5 text-sm border border-rule bg-card aria-[current=page]:bg-ink aria-[current=page]:text-paper aria-[current=page]:border-ink">
                {{ $label }}@if (isset($counts[$value]) && $counts[$value] > 0) <span class="opacity-75 num">{{ digits($counts[$value]) }}</span>@endif
            </a>
        @endforeach
    </nav>

    <div class="mt-4 paper-card overflow-x-auto">
        <table class="khata-table min-w-[760px]">
            <thead><tr>
                <th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Family') }}</th><th scope="col">{{ __('Months') }}</th>
                <th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th>
                <th scope="col"><span class="sr-only">{{ __('Action') }}</span></th>
            </tr></thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="align-top">
                        <td class="num whitespace-nowrap text-[13px] text-ink-2">{{ local_date($payment->submitted_at ?? $payment->created_at, 'j M, g:i a') }}</td>
                        <td><span class="font-semibold">{{ $payment->family->tr('head_name') }}</span><span class="block text-[13px] text-ink-3 num">{{ $payment->family->family_no }}</span></td>
                        <td class="text-[14px]">{{ $payment->monthsLabel() ?: '—' }}</td>
                        <td class="text-[13px] num break-all">
                            @if ($payment->upi_ref)<span class="block">{{ __('UTR') }} {{ $payment->upi_ref }}</span>@endif
                            @if ($payment->gateway_order_id)<span class="block">{{ $payment->gateway_order_id }}</span>@endif
                            @if ($payment->gateway_payment_id)<span class="block">{{ $payment->gateway_payment_id }}</span>@endif
                            <span class="block text-ink-3">{{ $payment->reference }}</span>
                        </td>
                        <td>
                            <span class="chip {{ $payment->status->tone() }}">{{ $payment->status->label() }}</span>
                            <span class="block mt-1 text-[12.5px] text-ink-3">{{ $payment->gateway?->label() }}@if ($payment->receipt_no) · <a href="{{ route('admin.receipts.show', $payment) }}" class="num hover:underline">{{ $payment->receipt_no }}</a>@endif</span>
                            @if ($payment->note)<span class="block mt-1 text-[12.5px] text-sindoor-deep max-w-[220px]">{{ __($payment->note) }}</span>@endif
                        </td>
                        <td class="amt font-semibold">{{ rupees($payment->amount) }}</td>
                        <td class="text-right">
                            @can('confirm', $payment)
                                <div class="flex flex-col items-end gap-2">
                                    <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}">
                                        @csrf
                                        <button class="btn btn-ink btn-sm"><x-icon name="check" /><span>{{ __('Confirm') }}</span></button>
                                    </form>
                                    <details class="text-left">
                                        <summary class="cursor-pointer text-sm font-semibold text-sindoor">{{ __('Reject…') }}</summary>
                                        <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="mt-2 w-56 space-y-2">
                                            @csrf
                                            <label for="reason-{{ $payment->id }}" class="label">{{ __('Reason (the member sees it)') }}</label>
                                            <input id="reason-{{ $payment->id }}" name="reason" class="field h-10" required minlength="3" maxlength="200" value="{{ __('Not found in the bank statement') }}">
                                            <button class="btn btn-line btn-sm">{{ __('Reject payment') }}</button>
                                        </form>
                                    </details>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="!py-12 text-center text-ink-3">{{ $show === 'waiting' ? __('No UPI payments are waiting. All caught up.') : __('Nothing here yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @error('reason')<p class="mt-3 field-error">{{ $message }}</p>@enderror
    <div class="mt-6">{{ $payments->links() }}</div>
</x-layouts.admin>
