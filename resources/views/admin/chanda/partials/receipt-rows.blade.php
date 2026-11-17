{{-- A table of chanda receipts. Expects $receipts. --}}
<div class="overflow-x-auto"><table class="khata-table min-w-[680px]">
    <thead><tr><th scope="col">{{ __('Receipt no.') }}</th><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Donor') }}</th><th scope="col">{{ __('Area') }}</th><th scope="col">{{ __('Mode') }}</th><th scope="col">{{ __('Collected by') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th></tr></thead>
    <tbody>
        @forelse ($receipts as $receipt)
            <tr class="{{ $receipt->isCancelled() ? 'text-ink-3' : '' }}">
                <td class="num whitespace-nowrap"><a href="{{ route('admin.chanda.receipt', $receipt) }}" class="font-semibold hover:underline">{{ $receipt->receipt_no }}</a>@if ($receipt->isCancelled()) <span class="chip bg-ink text-paper text-[11px] h-5 px-1.5">{{ __('Cancelled') }}</span>@endif</td>
                <td class="num whitespace-nowrap">{{ local_date($receipt->received_at, 'j M') }}</td>
                <td>{{ $receipt->donor_name }}</td>
                <td class="text-ink-2">{{ $receipt->area?->tr('name') ?? ($receipt->sponsor_id ? __('Sponsor') : '—') }}</td>
                <td>{{ $receipt->mode->label() }}</td>
                <td class="text-ink-2">{{ $receipt->collector?->name ?? '—' }}</td>
                <td class="amt font-semibold {{ $receipt->isCancelled() ? 'line-through' : '' }}">{{ rupees($receipt->amount) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-ink-2">{{ __('No receipts yet.') }}</td></tr>
        @endforelse
    </tbody>
</table></div>
