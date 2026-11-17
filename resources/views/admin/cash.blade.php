<x-layouts.admin :title="__('Cash & bank')">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Cash & bank') }}</h1>
            <p class="mt-1 text-ink-2 max-w-2xl">{{ __('How much of each book is in cash and how much in the bank, as the khata says. Count the cash box and check the bank passbook against these.') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('import', \App\Models\Payment::class)
                <a href="{{ route('admin.payments.import') }}" class="btn btn-line btn-sm"><x-icon name="upload" /><span>{{ __('Import statement') }}</span></a>
            @endcan
            @if ($canWrite)
                <a href="{{ route('admin.khata.create', ['type' => 'deposit']) }}" class="btn btn-ink btn-sm"><x-icon name="landmark" /><span>{{ __('Deposit to bank') }}</span></a>
            @endif
        </div>
    </div>

    <div class="mt-5 grid grid-cols-2 lg:grid-cols-3 gap-px bg-rule rounded-lg overflow-hidden border border-rule">
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __('Cash in hand') }}</p><p class="font-display text-2xl num {{ $cash < 0 ? 'text-sindoor' : '' }}">{{ $cash < 0 ? '−' : '' }}{{ rupees(abs($cash)) }}</p><p class="text-[13px] text-ink-3">{{ __('Bank anything over ₹5,000') }}</p></div>
        <div class="bg-card p-4"><p class="text-sm text-ink-3">{{ __('In the bank (as per khata)') }}</p><p class="font-display text-2xl num">{{ $bank < 0 ? '−' : '' }}{{ rupees(abs($bank)) }}</p><p class="text-[13px] text-ink-3">{{ __('UPI and online payments go straight to the bank') }}</p></div>
        @if ($waiting)
            <div class="bg-card p-4 col-span-2 lg:col-span-1"><p class="text-sm text-ink-3">{{ __('UPI waiting to be matched') }}</p><p class="font-display text-2xl num {{ (int) $waiting->n ? 'text-sindoor' : 'text-tulsi' }}">{{ digits((int) $waiting->n) }} · {{ rupees((int) $waiting->total) }}</p><p class="text-[13px] text-ink-3"><a href="{{ route('admin.payments.index') }}" class="hover:underline">{{ __('Confirm them against the bank') }} →</a></p></div>
        @endif
    </div>

    <div class="mt-6 grid xl:grid-cols-12 gap-6 items-start">
        <section class="xl:col-span-7 paper-card overflow-hidden" aria-labelledby="books-h">
            <h2 id="books-h" class="px-5 pt-4 pb-3 border-b border-rule font-display text-xl">{{ __('By book') }}</h2>
            <div class="overflow-x-auto"><table class="khata-table min-w-[520px]">
                <thead><tr><th scope="col">{{ __('Book') }}</th><th scope="col" class="amt">{{ __('Cash') }}</th><th scope="col" class="amt">{{ __('Bank') }}</th><th scope="col" class="amt">{{ __('Balance') }}</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td><a href="{{ route('admin.khata.index', ['book' => $row['book']->code->value]) }}" class="book-{{ $row['book']->code->value }} flex items-center gap-2.5 font-semibold hover:underline"><span class="inline-block w-2.5 h-2.5 rounded-full bg-[color:var(--book)]"></span>{{ $row['book']->code->label() }}</a></td>
                            <td class="amt">{{ $row['cash'] < 0 ? '−' : '' }}{{ rupees(abs($row['cash'])) }}</td>
                            <td class="amt">{{ $row['bank'] < 0 ? '−' : '' }}{{ rupees(abs($row['bank'])) }}</td>
                            <td class="amt font-semibold">{{ $row['balance'] < 0 ? '−' : '' }}{{ rupees(abs($row['balance'])) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-paper-2/50 font-semibold [&>td]:border-t [&>td]:border-t-ink/20 [&>td]:border-b-[3px] [&>td]:border-double [&>td]:border-b-ink/25">
                        <td>{{ __('Total') }}</td>
                        <td class="amt">{{ $cash < 0 ? '−' : '' }}{{ rupees(abs($cash)) }}</td>
                        <td class="amt">{{ $bank < 0 ? '−' : '' }}{{ rupees(abs($bank)) }}</td>
                        <td class="amt">{{ $cash + $bank < 0 ? '−' : '' }}{{ rupees(abs($cash + $bank)) }}</td>
                    </tr>
                </tbody>
            </table></div>
            <p class="px-5 py-3 text-sm text-ink-2 border-t border-rule">{{ __('Club rule: collectors hand cash to the treasurer within 7 days, and the treasurer banks it. Record each deposit here so cash and bank stay right.') }}</p>
        </section>

        <section class="xl:col-span-5 paper-card overflow-hidden" aria-labelledby="dep-h">
            <h2 id="dep-h" class="px-5 pt-4 pb-3 border-b border-rule font-display text-xl">{{ __('Cash put in or taken from the bank') }}</h2>
            <ul class="divide-y divide-rule">
                @forelse ($transfers as $entry)
                    <li class="px-5 py-3 flex items-center gap-3 text-[15px]">
                        <span class="num text-[13px] text-ink-3 w-16 shrink-0">{{ local_date($entry->entry_date, 'j M') }}</span>
                        <span class="flex-1 min-w-0"><span class="block">{{ $entry->type->label() }}</span><span class="block text-[12.5px] text-ink-3">{{ $entry->book->code->label() }}@if ($entry->note) · {{ $entry->note }}@endif</span></span>
                        <span class="font-semibold num">{{ $entry->reverses_id ? '−' : '' }}{{ rupees($entry->amount) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-ink-3">{{ __('No deposits recorded yet.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.admin>
