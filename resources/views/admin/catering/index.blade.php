@php
    use App\Enums\CateringOrderStatus as S;
    use App\Enums\CateringPaymentKind;
    use App\Enums\LedgerAccount;
    use App\Http\Controllers\Admin\CateringOrderController;
    use App\Models\CateringPackage;
    use App\Support\FormOptions;

    $occasion = fn (?string $key): string => $key && isset(FormOptions::OCCASIONS[$key]) ? __(FormOptions::OCCASIONS[$key]['label']) : '';
    $wage = (int) config('club.catering_server_wage');
@endphp
<x-layouts.admin :title="__('Catering orders')">
    <div class="flex flex-wrap items-end gap-3">
        <div class="mr-auto">
            <h1 class="font-display text-[2rem] leading-tight">{{ $closed ? __('Lost and cancelled orders') : __('Catering orders') }}</h1>
            <p class="text-ink-2">{{ __('The club serves; the customer arranges the food. Money goes into the Catering book.') }}</p>
        </div>
        @if ($closed)
            <a href="{{ route('admin.catering.index') }}" class="btn btn-line btn-sm"><x-icon name="arrow-left" />{{ __('Back to the board') }}</a>
        @else
            <a href="{{ route('admin.catering.index', ['show' => 'closed']) }}" class="btn btn-ghost btn-sm text-ink-2">{{ __('Lost and cancelled') }} <span class="num">({{ digits($closedCount) }})</span></a>
        @endif
        @can('viewAny', CateringPackage::class)
            <a href="{{ route('admin.packages.index') }}" class="btn btn-line btn-sm"><x-icon name="utensils" />{{ __('Packages') }}</a>
        @endcan
        <a href="{{ route('admin.catering.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" />{{ __('New enquiry') }}</a>
    </div>

    @if ($openEnquiries->isNotEmpty() && ! $closed)
        <section class="mt-5 paper-card p-4" aria-labelledby="web-enq">
            <h2 id="web-enq" class="text-sm font-semibold text-ink-2">{{ __('From the website, not yet on the board') }}</h2>
            <ul class="mt-2 divide-y divide-rule">
                @foreach ($openEnquiries as $enquiry)
                    <li class="py-2.5 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span class="font-semibold">{{ $enquiry->name }}</span>
                        <span class="text-sm text-ink-3">{{ $occasion($enquiry->payload['occasion'] ?? null) }}@if (! empty($enquiry->payload['event_date'])) · {{ local_date(\Illuminate\Support\Carbon::parse($enquiry->payload['event_date'])) }}@endif @if (! empty($enquiry->payload['guests'])) · <span class="num">{{ digits((int) $enquiry->payload['guests']) }}</span> {{ __('guests') }}@endif</span>
                        <a href="{{ route('admin.catering.create', ['enquiry' => $enquiry->id]) }}" class="btn btn-line btn-sm ml-auto"><x-icon name="plus" />{{ __('Put on the board') }}</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="mt-6 grid xl:grid-cols-12 gap-6 items-start">
        <div class="xl:col-span-7 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
            @if ($closed)
                <ul class="space-y-2">
                    @forelse ($orders as $item)
                        <li><a href="{{ route('admin.catering.index', ['show' => 'closed', 'order' => $item->id]) }}" class="block paper-card p-4 hover:border-turmeric/60 {{ $order?->id === $item->id ? 'ring-2 ring-turmeric/25 border-turmeric' : '' }}">
                            <span class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ $item->customer_name }}</span><span class="chip bg-paper-2 text-ink-2">{{ $item->status->label() }}</span><span class="ml-auto text-sm text-ink-3 num">{{ $item->number() }}</span></span>
                            <span class="block text-sm text-ink-3 mt-0.5">{{ local_date($item->event_date) }} · {{ __($item->closed_reason ?? '') }}</span>
                        </a></li>
                    @empty
                        <li class="text-ink-3">{{ __('Nothing here') }}</li>
                    @endforelse
                </ul>
            @else
                <div class="grid grid-cols-5 gap-3 min-w-[920px]">
                    @foreach ($columns as $status => $list)
                        <div class="bg-paper-2/70 rounded-lg p-2.5">
                            <h2 class="px-1.5 pt-1 pb-2.5 text-sm font-semibold text-ink-2 flex">{{ S::from($status)->label() }}<span class="ml-auto num text-ink-3">{{ digits($list->count()) }}</span></h2>
                            <div class="space-y-2">
                                @forelse ($list as $item)
                                    <a href="{{ route('admin.catering.index', ['order' => $item->id]) }}" @if ($order?->id === $item->id) aria-current="true" @endif class="block bg-card rounded-lg border {{ $order?->id === $item->id ? 'border-turmeric ring-2 ring-turmeric/25' : 'border-rule' }} p-3 hover:border-turmeric/60">
                                        <span class="block font-semibold leading-tight">{{ $item->customer_name }}</span>
                                        <span class="block text-[13px] text-ink-3 mt-0.5">{{ $occasion($item->occasion) ?: __('Catering') }} · {{ local_date($item->event_date, 'j M') }}</span>
                                        <span class="mt-2 flex items-center gap-2 text-sm whitespace-nowrap"><span class="num text-ink-2">{{ digits($item->guests) }} {{ __('guests') }}</span>@if ($item->is_member)<span class="chip h-5 px-1.5 text-[11px] bg-sindoor-wash text-sindoor-deep">{{ __('Member') }}</span>@endif</span>
                                        @if ($item->rate)<span class="block mt-1 font-semibold">{{ rupees($item->total()) }}</span>@endif
                                    </a>
                                @empty
                                    <p class="text-sm text-ink-3 px-1.5 py-3">{{ __('Nothing here') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($order && $summary)
            @php
                $status = $order->status;
                $servers = CateringPackage::serversFor($order->guests);
                $paidIn = $order->payments->reject->isRefund();
                $quoteText = $order->rate && $order->package ? __('Gede Maitree Club quotation :number for :occasion on :date: :package, :guests guests at :rate each, total :total (:servers servers and a team leader). An advance of :advance confirms the booking.', [
                    'number' => $order->number(),
                    'occasion' => $occasion($order->occasion) ?: __('Catering'),
                    'date' => local_date($order->event_date),
                    'package' => $order->package->tr('name'),
                    'guests' => digits($order->guests),
                    'rate' => rupees((int) $order->rate),
                    'total' => rupees($order->total()),
                    'servers' => digits($servers),
                    'advance' => rupees($order->advanceDue()),
                ]).($order->quote_note ? "\n".$order->quote_note : '') : '';
                $quoteLink = $quoteText !== '' ? CateringOrderController::whatsapp($order->phone, $quoteText) : null;
                $teamText = __('Serving duty: :customer, :date at :venue. Report 1 hour before, in club uniform. Team: :team', [
                    'customer' => $order->customer_name,
                    'date' => local_date($order->event_date),
                    'venue' => $order->venue ?: '—',
                    'team' => $order->roster->pluck('server_name')->join(', '),
                ]);
            @endphp
            <section class="xl:col-span-5 paper-card book-catering" aria-labelledby="order-h">
                <div class="p-5 border-b border-rule">
                    <p class="text-sm text-ink-3"><span class="num">{{ $order->number() }}</span> · {{ $status->label() }}</p>
                    <h2 id="order-h" class="font-display text-2xl leading-tight">{{ $order->customer_name }}</h2>
                    <p class="text-ink-2 text-[15px]">{{ $occasion($order->occasion) ?: __('Catering') }} · {{ local_date($order->event_date) }} · <span class="num">{{ digits($order->guests) }}</span> {{ __('guests') }}</p>
                    <p class="text-sm text-ink-3 mt-1"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->phone) }}" class="num hover:underline">{{ $order->phone }}</a>@if ($order->venue) · {{ $order->venue }}@endif @if ($order->is_member) · <span class="text-sindoor-deep font-semibold">{{ __('Member') }}</span>@endif</p>
                </div>

                <div class="p-5">
                    @if ($order->rate && $order->package)
                        <h3 class="text-sm font-semibold text-ink-3">{{ __('Quotation') }}</h3>
                        <table class="w-full text-[15px] mt-2">
                            <tr><td class="py-1.5">{{ $order->package->tr('name') }}</td><td class="text-right num">{{ digits($order->guests) }} × {{ rupees((int) $order->rate) }}</td></tr>
                            <tr><td class="py-1.5 text-ink-2">{{ __('Servers') }}</td><td class="text-right text-ink-2 num">{{ digits($servers) }} {{ __('+ team leader') }}</td></tr>
                            <tr class="border-t border-ink/20 font-semibold"><td class="py-2">{{ __('Total') }}</td><td class="text-right num">{{ rupees($summary['total']) }}</td></tr>
                            <tr><td class="py-1.5 text-ink-2">{{ __('Advance (:percent%)', ['percent' => digits((int) config('club.catering_advance_percent'))]) }}</td><td class="text-right num">{{ rupees($summary['advance_due']) }}</td></tr>
                            <tr><td class="py-1.5 text-ink-2">{{ __('Received') }}</td><td class="text-right text-tulsi font-semibold num">{{ rupees($summary['received']) }}</td></tr>
                            @if ($summary['refunded'])
                                <tr><td class="py-1.5 text-ink-2">{{ __('Refunded') }}</td><td class="text-right num">−{{ rupees($summary['refunded']) }}</td></tr>
                            @endif
                            @unless ($status->isClosed())
                                <tr class="font-semibold"><td class="py-1.5">{{ __('Balance') }}</td><td class="text-right text-sindoor num">{{ rupees($summary['balance']) }}</td></tr>
                            @endunless
                        </table>
                        @if ($order->package->tr('menu'))
                            <h3 class="text-sm font-semibold text-ink-3 mt-5">{{ __('Included') }}</h3>
                            <p class="text-[15px] text-ink-2 mt-1 leading-relaxed">{{ implode(' · ', (array) $order->package->tr('menu')) }}</p>
                        @endif
                        @if ($order->quote_valid_until && in_array($status, [S::Enquiry, S::Quoted], true))
                            <p class="text-sm text-ink-3 mt-2">{{ __('Quote valid until :date', ['date' => local_date($order->quote_valid_until)]) }}</p>
                        @endif
                    @else
                        <p class="text-[15px] text-ink-2">{{ __('No package chosen yet. Send a quotation to fix the guests and price.') }}</p>
                    @endif

                    {{-- The next step for this stage --}}
                    <div class="mt-5 space-y-2">
                        @if ($status === S::Quoted)
                            <form method="POST" action="{{ route('admin.catering.receive', $order) }}" class="rounded-lg border border-rule p-3.5 space-y-3" x-data="{ mode: 'cash' }">
                                @csrf
                                <input type="hidden" name="kind" value="{{ CateringPaymentKind::Advance->value }}">
                                <p class="font-semibold">{{ __('Record advance (confirms the booking)') }}</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <x-field name="amount" type="number" min="1" inputmode="numeric" :label="__('Amount')" :value="$summary['advance_due']" required />
                                    @include('admin.catering.partials.mode')
                                </div>
                                <button class="btn btn-primary w-full"><x-icon name="indian-rupee" />{{ __('Record and print receipt') }}</button>
                            </form>
                        @elseif ($status === S::Advance)
                            <form method="POST" action="{{ route('admin.catering.served', $order) }}" class="rounded-lg border border-rule p-3.5 space-y-3">
                                @csrf
                                <p class="font-semibold">{{ __('Mark as served') }}</p>
                                <x-field name="guests" type="number" min="1" inputmode="numeric" :label="__('Final guest count')" :value="$order->guests" required />
                                <p class="text-sm text-ink-3">{{ __('Extra guests are added to the final bill at the same rate.') }}</p>
                                <button class="btn btn-primary w-full"><x-icon name="utensils" />{{ __('Mark as served') }}</button>
                            </form>
                        @elseif ($status === S::Done)
                            <form method="POST" action="{{ route('admin.catering.receive', $order) }}" class="rounded-lg border border-rule p-3.5 space-y-3" x-data="{ mode: 'cash' }">
                                @csrf
                                <input type="hidden" name="kind" value="{{ CateringPaymentKind::Final->value }}">
                                <p class="font-semibold">{{ __('Final bill & settle') }}</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <x-field name="amount" type="number" min="1" inputmode="numeric" :label="__('Amount')" :value="$summary['balance']" required />
                                    @include('admin.catering.partials.mode')
                                </div>
                                <p class="text-sm text-ink-3">{{ __('When the balance is paid the order is settled.') }}</p>
                                <button class="btn btn-primary w-full"><x-icon name="file-check" />{{ __('Record and print receipt') }}</button>
                            </form>
                        @elseif ($status === S::Settled)
                            <div class="rounded-lg bg-tulsi-wash text-tulsi p-3.5 flex items-center gap-2.5 font-semibold"><x-icon name="circle-check" class="w-5 h-5" />{{ __('Settled · profit :profit in the Catering book', ['profit' => rupees($summary['profit'])]) }}</div>
                        @elseif ($status->isClosed())
                            <div class="rounded-lg bg-paper-2 text-ink-2 p-3.5">
                                <p class="font-semibold">{{ $status->label() }} · {{ local_date($order->closed_at) }}</p>
                                <p class="text-sm">{{ __($order->closed_reason ?? '') }}@if ($order->closer) · {{ $order->closer->name }}@endif</p>
                            </div>
                        @endif

                        @if (in_array($status, [S::Enquiry, S::Quoted, S::Advance], true))
                            <details class="rounded-lg border border-rule" @if ($status === S::Enquiry || $errors->has('package_id')) open @endif>
                                <summary class="cursor-pointer p-3.5 font-semibold">{{ $status === S::Enquiry ? __('Send quotation') : __('Change guests / package') }}</summary>
                                <form method="POST" action="{{ route('admin.catering.quote', $order) }}" class="px-3.5 pb-3.5 space-y-3">
                                    @csrf @method('PUT')
                                    <div class="grid grid-cols-2 gap-3">
                                        <x-field name="guests" type="number" min="1" inputmode="numeric" :label="__('Guests')" :value="$order->guests" required />
                                        <div>
                                            <label for="f-package_id" class="label">{{ __('Package') }}</label>
                                            <select id="f-package_id" name="package_id" class="field" required>
                                                @foreach ($packages as $package)
                                                    <option value="{{ $package->id }}" @selected((int) old('package_id', $order->package_id) === $package->id)>{{ $package->tr('name') }} · {{ rupees($order->is_member ? $package->memberRate() : $package->rate) }}</option>
                                                @endforeach
                                            </select>
                                            <x-field-error name="package_id" />
                                        </div>
                                    </div>
                                    <x-field name="valid_until" type="date" :label="__('Valid until')" :value="$order->quote_valid_until?->toDateString() ?? now()->addDays(7)->toDateString()" />
                                    <x-field name="note" :label="__('Note to customer')" textarea :rows="2" :value="$order->quote_note ?? __('We serve only; the food is arranged by you. Final guest count needed 3 days before.')" />
                                    <button class="btn btn-line w-full"><x-icon name="save" />{{ __('Save quotation') }}</button>
                                </form>
                            </details>
                        @endif

                        @if ($quoteLink && in_array($status, [S::Quoted, S::Advance], true))
                            <a href="{{ $quoteLink }}" target="_blank" rel="noopener" class="btn btn-line w-full"><x-icon name="message-circle" />{{ __('Send the quote on WhatsApp') }}</a>
                        @endif

                        @if (in_array($status, [S::Quoted, S::Advance, S::Done], true) && $summary['balance'] > 0)
                            <details class="rounded-lg border border-rule">
                                <summary class="cursor-pointer p-3.5 font-semibold">{{ __('Record payment') }}</summary>
                                <form method="POST" action="{{ route('admin.catering.receive', $order) }}" class="px-3.5 pb-3.5 space-y-3" x-data="{ mode: 'cash' }">
                                    @csrf
                                    <input type="hidden" name="kind" value="{{ CateringPaymentKind::Part->value }}">
                                    <div class="grid grid-cols-2 gap-3">
                                        <x-field name="amount" type="number" min="1" :max="$summary['balance']" inputmode="numeric" :label="__('Amount')" required />
                                        @include('admin.catering.partials.mode')
                                    </div>
                                    <x-field name="note" :label="__('Note')" maxlength="200" />
                                    <button class="btn btn-line w-full"><x-icon name="indian-rupee" />{{ __('Record and print receipt') }}</button>
                                </form>
                            </details>
                        @endif

                        @if (in_array($status, [S::Enquiry, S::Quoted], true) && $summary['received'] === 0)
                            <details>
                                <summary class="cursor-pointer text-sm font-semibold text-ink-2">{{ __('Customer went elsewhere? Mark as lost') }}</summary>
                                <form method="POST" action="{{ route('admin.catering.lost', $order) }}" class="mt-2 flex flex-wrap gap-2 items-end">
                                    @csrf
                                    <div class="flex-1 min-w-[180px]">
                                        <label for="f-reason" class="label">{{ __('Why?') }}</label>
                                        <select id="f-reason" name="reason" class="field">
                                            @foreach (CateringOrderController::lostReasons() as $key => $reason)
                                                <option value="{{ $key }}">{{ __($reason['label']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="btn btn-line btn-sm">{{ __('Mark as lost') }}</button>
                                </form>
                            </details>
                        @endif

                        @if (in_array($status, [S::Quoted, S::Advance], true) && ($status === S::Advance || $summary['received'] > 0))
                            <details>
                                <summary class="cursor-pointer text-sm font-semibold text-sindoor">{{ __('Booking cancelled? Refund or keep advance') }}</summary>
                                <form method="POST" action="{{ route('admin.catering.cancel', $order) }}" class="mt-2 space-y-3" x-data="{ mode: 'cash' }">
                                    @csrf
                                    <p class="text-[15px]">{{ __('Received') }}: <strong class="num">{{ rupees($summary['received']) }}</strong></p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <x-field name="refund" type="number" min="0" :max="$summary['received']" inputmode="numeric" :label="__('Refund')" :value="0" required />
                                        @include('admin.catering.partials.mode', ['upi' => false])
                                    </div>
                                    <x-field name="reason" :label="__('Reason')" required minlength="3" maxlength="200" />
                                    <p class="text-sm text-ink-3">{{ __('A refund is an expense in the Catering book; what is kept stays as income.') }}</p>
                                    <button class="btn btn-line btn-sm text-sindoor-deep">{{ __('Cancel booking') }}</button>
                                </form>
                            </details>
                        @endif
                    </div>

                    {{-- Money received --}}
                    @if ($order->payments->isNotEmpty())
                        <h3 class="text-sm font-semibold text-ink-3 mt-6">{{ __('Payments') }}</h3>
                        <ul class="mt-1 divide-y divide-rule text-[15px]">
                            @foreach ($order->payments->sortBy('id') as $payment)
                                <li class="py-2 flex items-center gap-3">
                                    <span class="flex-1">{{ $payment->kind->label() }} · {{ $payment->mode->label() }}<span class="block text-[13px] text-ink-3">{{ local_date($payment->paid_on ?? $payment->created_at) }}@if ($payment->receiver) · {{ $payment->receiver->name }}@endif</span></span>
                                    @if ($payment->receipt_no)
                                        <a href="{{ route('admin.catering.receipt', $payment) }}" class="text-sm num hover:underline">{{ $payment->receipt_no }}</a>
                                    @endif
                                    <span class="w-24 text-right num {{ $payment->isRefund() ? 'text-sindoor' : '' }}">{{ $payment->isRefund() ? '−' : '' }}{{ rupees($payment->amount) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Serving team --}}
                    @if ($status !== S::Lost && $status !== S::Cancelled)
                        <h3 class="text-sm font-semibold text-ink-3 mt-6">{{ __('Serving team') }} · <span class="num">{{ digits($order->roster->count()) }} / {{ digits($servers + 1) }}</span></h3>
                        <p class="text-[13px] text-ink-3">{{ __(':guests guests at 1 server per 15, plus a team leader.', ['guests' => digits($order->guests)]) }}</p>
                        @if ($order->roster->isNotEmpty())
                            <ul class="mt-2 divide-y divide-rule border-y border-rule text-[15px]">
                                @foreach ($order->roster->sortByDesc('is_leader') as $server)
                                    <li class="py-2 flex items-center gap-2">
                                        <span class="flex-1">{{ $server->server_name }}@if ($server->is_leader) <span class="chip h-5 px-1.5 text-[11px] bg-ink text-white">{{ __('Team leader') }}</span>@endif @if ($server->phone)<span class="block text-[13px] text-ink-3 num">{{ $server->phone }}</span>@endif</span>
                                        <span class="text-sm num text-ink-2">{{ rupees($server->wage) }}</span>
                                        <form method="POST" action="{{ route('admin.catering.team.confirm', [$order, $server]) }}">@csrf<button class="chip h-7 px-2 text-[12.5px] {{ $server->confirmed ? 'bg-tulsi-wash text-tulsi' : 'bg-paper-2 text-ink-2' }}" aria-pressed="{{ $server->confirmed ? 'true' : 'false' }}">{{ $server->confirmed ? __('Confirmed') : __('Not confirmed') }}</button></form>
                                        @unless ($order->wages_paid_at)
                                            <form method="POST" action="{{ route('admin.catering.team.destroy', [$order, $server]) }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-ink-3" aria-label="{{ __('Remove :name', ['name' => $server->server_name]) }}"><x-icon name="x" class="w-4 h-4" /></button></form>
                                        @endunless
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="mt-2 grid gap-2">
                            @unless ($order->wages_paid_at)
                                <details class="rounded-lg border border-rule">
                                    <summary class="cursor-pointer p-3 text-sm font-semibold">{{ __('Add to the team') }}</summary>
                                    <form method="POST" action="{{ route('admin.catering.team.store', $order) }}" class="px-3 pb-3 space-y-3">
                                        @csrf
                                        <div class="grid grid-cols-2 gap-3">
                                            <x-field name="server_name" :label="__('Name')" required maxlength="80" />
                                            <x-field name="phone" type="tel" inputmode="tel" :label="__('Mobile')" />
                                        </div>
                                        <div class="grid grid-cols-2 gap-3 items-end">
                                            <x-field name="wage" type="number" min="0" inputmode="numeric" :label="__('Wage')" :value="$wage" required />
                                            <label class="flex items-center gap-2 h-11 text-[15px]"><input type="checkbox" name="is_leader" value="1" class="w-4 h-4 accent-sindoor"> {{ __('Team leader') }}</label>
                                        </div>
                                        <button class="btn btn-line btn-sm">{{ __('Add') }}</button>
                                    </form>
                                </details>
                            @endunless
                            @if ($order->roster->isNotEmpty())
                                <a href="https://wa.me/?text={{ rawurlencode($teamText) }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-icon name="message-circle" />{{ __('Send to team') }}</a>
                            @endif
                            @if ($order->wages_paid_at)
                                <p class="text-sm text-tulsi">{{ __('Wages of :amount paid :date.', ['amount' => rupees((int) $order->roster->sum('wage')), 'date' => local_date($order->wages_paid_at)]) }}</p>
                            @elseif (in_array($status, [S::Done, S::Settled], true) && $order->roster->sum('wage') > 0)
                                <form method="POST" action="{{ route('admin.catering.wages', $order) }}" class="flex gap-2 items-center">
                                    @csrf
                                    <select name="account" class="field h-9 text-sm w-auto" aria-label="{{ __('Paid from') }}">
                                        @foreach (LedgerAccount::cases() as $account)<option value="{{ $account->value }}">{{ $account->label() }}</option>@endforeach
                                    </select>
                                    <button class="btn btn-line btn-sm flex-1">{{ __('Pay wages :amount', ['amount' => rupees((int) $order->roster->sum('wage'))]) }}</button>
                                </form>
                            @endif
                        </div>
                    @endif

                    {{-- Expenses for this order --}}
                    @if ($status !== S::Lost)
                        <h3 class="text-sm font-semibold text-ink-3 mt-6">{{ __('Expenses') }} · <span class="num">{{ rupees($summary['expenses']) }}</span></h3>
                        @if ($order->expenses->isNotEmpty())
                            <ul class="mt-1 divide-y divide-rule text-[15px]">
                                @foreach ($order->expenses->sortBy('id') as $line)
                                    <li class="py-2 flex items-center gap-3"><span class="flex-1">{{ $line->head->label() }}<span class="block text-[13px] text-ink-3">{{ local_date($line->entry_date) }}@if ($line->recorder) · {{ $line->recorder->name }}@endif @if ($line->reverses_id) · {{ __('Reversal') }}@endif</span></span><span class="num">{{ $line->reverses_id ? '−' : '' }}{{ rupees($line->amount) }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                        <details class="mt-2 rounded-lg border border-rule">
                            <summary class="cursor-pointer p-3 text-sm font-semibold">{{ __('Add catering expense') }}</summary>
                            <form method="POST" action="{{ route('admin.catering.expense', $order) }}" enctype="multipart/form-data" class="px-3 pb-3 space-y-3">
                                @csrf
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="f-head" class="label">{{ __('Head') }}</label>
                                        <select id="f-head" name="head" class="field">
                                            @foreach ($expenseHeads as $head)<option value="{{ $head->value }}">{{ $head->label() }}</option>@endforeach
                                        </select>
                                    </div>
                                    <x-field name="amount" type="number" min="1" inputmode="numeric" :label="__('Amount')" required />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="f-account" class="label">{{ __('Paid from') }}</label>
                                        <select id="f-account" name="account" class="field">
                                            @foreach (LedgerAccount::cases() as $account)<option value="{{ $account->value }}">{{ $account->label() }}</option>@endforeach
                                        </select>
                                    </div>
                                    <x-field name="note" :label="__('Note')" maxlength="120" :placeholder="__('e.g. plates and glasses')" />
                                </div>
                                <div>
                                    <label for="f-bill" class="label">{{ __('Photo of the bill') }}</label>
                                    <input id="f-bill" type="file" name="bill" accept="image/*" capture="environment" class="field py-2">
                                    <x-field-error name="bill" />
                                </div>
                                <button class="btn btn-line btn-sm">{{ __('Save expense') }}</button>
                            </form>
                        </details>
                        @if (in_array($status, [S::Done, S::Settled], true))
                            <p class="mt-3 text-[15px]">{{ __('Profit on this order') }}: <strong class="num {{ $summary['profit'] < 0 ? 'text-sindoor' : 'text-tulsi' }}">{{ rupees($summary['profit']) }}</strong></p>
                        @endif
                    @endif
                </div>
            </section>
        @elseif (! $closed)
            <div class="xl:col-span-5 paper-card p-6 text-ink-2">{{ __('No catering orders yet. Add the first enquiry.') }}</div>
        @endif
    </div>
</x-layouts.admin>
