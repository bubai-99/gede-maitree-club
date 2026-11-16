{{-- How each ₹100 was spent, as a khata page. $spend: list of {head, head_bn, amount, colour}; $total: sum. --}}
<div class="gz-ledger rounded-[18px] border gz-hair shadow-[0_1px_2px_rgba(0,0,0,.04)] overflow-hidden bg-[#fffdf8]">
    <div class="pl-12 pr-5 h-12 flex items-center justify-between border-b border-ink/25 text-[12.5px] font-bold uppercase tracking-wide text-ink-2"><span>{{ __('Spent on') }}</span><span>{{ __('Of every ₹100') }}</span></div>
    <ol class="pl-12 pr-5">
        @foreach ($spend as $row)
            @php($share = (int) round($row['amount'] * 100 / max(1, $total)))
            <li class="py-2.5 border-b border-rule-blue last:border-0">
                <div class="leader text-[15px]"><span>{{ local_text($row['head'], $row['head_bn'] ?? null) }}</span><span class="font-semibold num">₹{{ digits($share) }}</span></div>
                <div class="mt-1.5 h-1.5 rounded-full bg-paper-2 overflow-hidden" aria-hidden="true"><div class="h-full rounded-full bar-grow" style="width: {{ $share }}%; background: {{ preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($row['colour'] ?? '')) ? $row['colour'] : '#2b2f36' }}"></div></div>
            </li>
        @endforeach
    </ol>
</div>
