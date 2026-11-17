@php
    $statusTabs = ['due' => __('Due'), 'paid' => __('Paid'), 'waived' => __('Waived')];
    $minTabs = [1 => __('Any amount'), 3 => __('3+ months'), 6 => __('6+ months (lapsed)')];
@endphp
<x-layouts.admin :title="__('Dues & reminders')">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[2rem] leading-tight">{{ __('Dues & reminders') }}</h1>
            <p class="mt-1 text-ink-2">{{ __(':count families owe', ['count' => digits($owingCount)]) }} <strong class="text-sindoor num">{{ rupees($totalOwed) }}</strong></p>
        </div>
        <form method="POST" action="{{ route('admin.dues.generate') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <div>
                <label for="gen-month" class="label">{{ __('Add a month’s dues for every family') }}</label>
                <input id="gen-month" type="month" name="month" value="{{ old('month', $thisMonth->format('Y-m')) }}" class="field h-9 w-auto" required>
            </div>
            <button class="btn btn-ink btn-sm"><x-icon name="calendar-plus" /><span>{{ __('Add dues') }}</span></button>
            <x-field-error name="month" />
        </form>
    </div>
    <p class="mt-2 text-[13px] text-ink-3">{{ __(':month: :count families have this month’s due. Running it again only adds families that are missing.', ['month' => local_date($thisMonth, 'F Y'), 'count' => digits($thisMonthGenerated)]) }}</p>

    <form method="GET" class="mt-5 flex flex-wrap items-center gap-2">
        <label for="d-status" class="sr-only">{{ __('Status') }}</label>
        <select id="d-status" name="status" class="field h-9 text-sm w-auto">
            @foreach ($statusTabs as $value => $label)
                <option value="{{ $value }}" @selected($status->value === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($status === \App\Enums\DueStatus::Due)
            <input type="hidden" name="min" value="{{ $min }}">
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Months overdue') }}">
                @foreach ($minTabs as $value => $label)
                    <a href="{{ route('admin.dues.index', array_filter(['min' => $value, 'area' => $area])) }}" @if ($min === $value) aria-current="page" @endif
                        class="chip h-9 px-3.5 text-sm border border-rule bg-card hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-paper aria-[current=page]:border-ink">{{ $label }} <span class="opacity-75 num">{{ digits($counts[$value]) }}</span></a>
                @endforeach
            </nav>
        @else
            <label for="d-month" class="sr-only">{{ __('Month') }}</label>
            <input id="d-month" type="month" name="month" value="{{ $month?->format('Y-m') }}" class="field h-9 text-sm w-auto">
        @endif
        <label for="d-area" class="sr-only">{{ __('Area') }}</label>
        <select id="d-area" name="area" class="field h-9 text-sm w-auto">
            <option value="">{{ __('All lanes') }}</option>
            @foreach ($areas as $option)
                <option value="{{ $option }}" @selected($area === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button class="btn btn-line btn-sm">{{ __('Filter') }}</button>
    </form>

    @if ($families !== null)
        <form method="POST" action="{{ route('admin.dues.remind') }}" target="_blank" class="mt-4 grid lg:grid-cols-12 gap-6 items-start"
            x-data="{ picked: [], all: @js($families->pluck('id')->map(fn ($id) => (string) $id)->values()) }">
            @csrf
            <div class="lg:col-span-8 paper-card overflow-x-auto">
                <div class="px-4 py-3 border-b border-rule flex flex-wrap items-center gap-2" x-show="picked.length" x-cloak>
                    <span class="text-sm text-ink-2"><span x-text="picked.length"></span> {{ __('selected') }}</span>
                    <button class="btn btn-ink btn-sm"><x-icon name="send" /><span>{{ __('Remind selected') }}</span></button>
                    <button type="button" class="btn btn-ghost btn-sm" @click="picked = []">{{ __('Clear') }}</button>
                </div>
                <table class="khata-table min-w-[640px]">
                    <thead><tr>
                        <th class="w-10 !pr-0"><input type="checkbox" class="w-4 h-4 accent-[#2b2f36] align-middle" aria-label="{{ __('Select all') }}" :checked="all.length && picked.length === all.length" @change="picked = $event.target.checked ? [...all] : []"></th>
                        <th scope="col">{{ __('Family') }}</th><th scope="col">{{ __('Months') }}</th><th scope="col" class="amt">{{ __('Due') }}</th>
                        <th scope="col" class="hidden md:table-cell">{{ __('Last reminded') }}</th><th scope="col"><span class="sr-only">{{ __('Action') }}</span></th>
                    </tr></thead>
                    <tbody>
                        @forelse ($families as $family)
                            @php($months = (int) $family->due_months)
                            <tr>
                                <td class="!pr-0"><input type="checkbox" name="families[]" value="{{ $family->id }}" x-model="picked" class="w-4 h-4 accent-[#2b2f36] align-middle" aria-label="{{ $family->head_name }}"></td>
                                <td><a href="{{ route('admin.dues.family', $family) }}" class="font-semibold hover:text-sindoor">{{ $family->tr('head_name') }}</a><span class="block text-[13px] text-ink-3 num">{{ $family->family_no }} · {{ $family->tr('area') }}</span></td>
                                <td><span class="flex gap-0.5" role="img" aria-label="{{ __(':count months due', ['count' => digits($months)]) }}">@for ($k = 0; $k < 8; $k++)<span class="w-2.5 h-5 rounded-sm {{ $k < $months ? ($months >= 6 ? 'bg-sindoor' : 'bg-marigold') : 'bg-paper-2' }}"></span>@endfor</span><span class="text-[12.5px] text-ink-3 num">{{ digits($months) }}</span></td>
                                <td class="amt font-semibold">{{ rupees((int) $family->due_total) }}</td>
                                <td class="hidden md:table-cell text-sm {{ $family->last_reminded_at?->isToday() ? 'text-tulsi font-semibold' : 'text-ink-3' }}">{{ $family->last_reminded_at ? $family->last_reminded_at->diffForHumans() : __('Never') }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <button form="remind-{{ $family->id }}" class="btn btn-sm btn-ink" @disabled(! $family->phone)><x-icon name="message-circle" /><span>{{ __('Remind') }}</span></button>
                                    <a href="{{ route('admin.collect', ['family' => $family->id]) }}" class="btn btn-sm btn-line"><x-icon name="hand-coins" /><span class="sr-only sm:not-sr-only">{{ __('Collect') }}</span></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="!py-12 text-center text-ink-3">{{ __('No family owes here. Nice.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="lg:col-span-4">
                <p class="text-sm font-semibold text-ink-3 mb-2">{{ __('WhatsApp message') }}</p>
                <div class="rounded-lg bg-[#e7ddc9] p-4">
                    <div class="bg-[#dcf3d0] rounded-lg rounded-tr-sm p-3.5 text-[15px] leading-relaxed ml-6">
                        @php($sample = $families->first())
                        {{ $sample ? \App\Support\DueReminder::message($sample, (int) $sample->due_months, (int) $sample->due_total) : __('No family owes here. Nice.') }}
                    </div>
                </div>
                <p class="text-sm text-ink-2 mt-3">{{ __('Opens WhatsApp on your phone with this filled in. Nothing is sent automatically and it costs nothing.') }}</p>
            </div>
        </form>
        @foreach ($families as $family)
            <form id="remind-{{ $family->id }}" method="POST" action="{{ route('admin.dues.remind') }}" target="_blank" class="hidden">
                @csrf
                <input type="hidden" name="families[]" value="{{ $family->id }}">
            </form>
        @endforeach
        @error('families')<p class="mt-3 field-error">{{ $message }}</p>@enderror
        <div class="mt-6">{{ $families->links() }}</div>
    @else
        <div class="mt-4 paper-card overflow-x-auto">
            <table class="khata-table min-w-[640px]">
                <thead><tr><th scope="col">{{ __('Month') }}</th><th scope="col">{{ __('Family') }}</th><th scope="col">{{ __('Details') }}</th><th scope="col" class="amt">{{ __('Amount') }}</th></tr></thead>
                <tbody>
                    @forelse ($dues as $due)
                        <tr>
                            <td class="whitespace-nowrap">{{ local_date($due->period, 'M Y') }}</td>
                            <td><a href="{{ route('admin.dues.family', $due->family) }}" class="font-semibold hover:text-sindoor">{{ $due->family->tr('head_name') }}</a> <span class="text-[13px] text-ink-3 num">{{ $due->family->family_no }}</span></td>
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
                        <tr><td colspan="4" class="!py-12 text-center text-ink-3">{{ __('Nothing here yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $dues->links() }}</div>
    @endif
</x-layouts.admin>
