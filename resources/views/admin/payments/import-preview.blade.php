<x-layouts.admin :title="__('Import bank statement')">
    <a href="{{ route('admin.payments.import') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Upload another file') }}</a>
    <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Check the matches') }}</h1>
    <p class="mt-1 text-ink-2"><span class="num">{{ $fileName }}</span> · {{ __(':count lines', ['count' => digits($lineCount)]) }}</p>

    <form method="GET" action="{{ route('admin.payments.import.show', $token) }}" class="mt-5 paper-card p-5 grid sm:grid-cols-4 gap-3 items-end">
        @foreach (['date' => __('Date column'), 'amount' => __('Money received column'), 'reference' => __('Description column')] as $key => $label)
            <div>
                <label for="col-{{ $key }}" class="label">{{ $label }}</label>
                <select id="col-{{ $key }}" name="{{ $key }}" class="field h-10">
                    @if ($key === 'date')<option value="">{{ __('None') }}</option>@endif
                    @foreach ($header as $i => $name)
                        <option value="{{ $i }}" @selected($columns[$key] === $i)>{{ $name !== '' ? $name : __('Column :n', ['n' => $i + 1]) }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <button class="btn btn-line h-10">{{ __('Match again') }}</button>
    </form>

    @if ($result === null)
        <p class="mt-6 rounded-lg bg-marigold-wash p-4 text-turmeric">{{ __('Choose the money received column and the description column.') }}</p>
    @else
        <section class="mt-6" aria-labelledby="matched-h">
            <h2 id="matched-h" class="font-display text-[1.5rem]">{{ __('Matched') }} <span class="num text-ink-3">{{ digits(count($result['matched'])) }}</span></h2>
            <div class="mt-2 paper-card overflow-x-auto">
                <table class="khata-table min-w-[680px]">
                    <thead><tr><th scope="col">{{ __('Line') }}</th><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('UTR') }}</th><th scope="col">{{ __('Family') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @forelse ($result['matched'] as $row)
                            <tr>
                                <td class="num text-ink-3">{{ $row['line'] }}</td>
                                <td class="num whitespace-nowrap">{{ $row['date'] ? local_date(\Illuminate\Support\Carbon::parse($row['date']), 'j M Y') : '—' }}</td>
                                <td class="num">{{ $row['utr'] }}</td>
                                <td><span class="font-semibold">{{ $row['payment']->family->tr('head_name') }}</span> <span class="text-[13px] text-ink-3 num">{{ $row['payment']->family->family_no }}</span></td>
                                <td class="amt font-semibold">{{ rupees($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="!py-8 text-center text-ink-3">{{ __('No line matches a UPI payment waiting for confirmation.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (count($result['matched']) > 0)
                <form method="POST" action="{{ route('admin.payments.import.confirm', $token) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="date" value="{{ $columns['date'] }}">
                    <input type="hidden" name="amount" value="{{ $columns['amount'] }}">
                    <input type="hidden" name="reference" value="{{ $columns['reference'] }}">
                    <button class="btn btn-primary h-12"><x-icon name="check" /><span>{{ __('Confirm :count matched payments', ['count' => digits(count($result['matched']))]) }}</span></button>
                </form>
            @endif
        </section>

        <section class="mt-8" aria-labelledby="unmatched-h">
            <h2 id="unmatched-h" class="font-display text-[1.5rem]">{{ __('Not matched') }} <span class="num text-ink-3">{{ digits(count($result['unmatched'])) }}</span></h2>
            <p class="text-sm text-ink-3">{{ __(':count lines without money received were left out.', ['count' => digits($result['skipped'])]) }}</p>
            <div class="mt-2 paper-card overflow-x-auto">
                <table class="khata-table min-w-[680px]">
                    <thead><tr><th scope="col">{{ __('Line') }}</th><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Bank description') }}</th><th scope="col">{{ __('Why') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @forelse ($result['unmatched'] as $row)
                            <tr>
                                <td class="num text-ink-3">{{ $row['line'] }}</td>
                                <td class="num whitespace-nowrap">{{ $row['date'] ? local_date(\Illuminate\Support\Carbon::parse($row['date']), 'j M Y') : '—' }}</td>
                                <td class="text-[13px] num break-all">{{ $row['text'] }}</td>
                                <td class="text-[13px] text-ink-2">{{ __($row['reason']) }}</td>
                                <td class="amt">{{ $row['amount'] !== null ? rupees($row['amount']) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="!py-8 text-center text-ink-3">{{ __('Every line with money received matched.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.admin>
