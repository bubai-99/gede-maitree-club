@php
    $content = app(\App\Support\PageContent::class);
    // Shown only when the committee publishes the drive's totals (Console → Puja chanda → Budget).
    $target = $public ? (int) $event->chanda_target : 0;
    $collected = $summary['collected'] ?? 0;
    $budget = $public ? array_map(fn ($b) => ['head' => $b['head'] ?? '', 'head_bn' => $b['head_bn'] ?? null, 'amount' => (int) ($b['amount'] ?? 0)], $event->budget ?? []) : [];
    $budgetShares = \App\Support\Shares::of100(array_column($budget, 'amount'));
    $lastPuja = $chanda['last_puja'] ?? null;
    $spendTotal = $lastPuja ? array_sum(array_column($lastPuja['spend'], 'amount')) : 0;
    $spendShares = $lastPuja ? \App\Support\Shares::of100(array_column($lastPuja['spend'], 'amount')) : [];
    $percent = $target > 0 ? (int) round($collected * 100 / $target) : 0;
    $club = $site->club();
    $houses = $areas->sum('houses');
    $visited = $areas->sum('visited');
    $daysLeft = $event ? max(0, (int) now()->startOfDay()->diffInDays($event->starts_at->copy()->startOfDay(), false)) : 0;
    $colour = fn ($row) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($row['colour'] ?? '')) ? $row['colour'] : '#2b2f36';
    $upiUri = 'upi://pay?'.http_build_query(['pa' => $club['upi'], 'pn' => $club['name'], 'tn' => ($event ? $event->title.' ' : '').'chanda', 'cu' => 'INR']);
@endphp
<x-layouts.site seo="chanda" :title="$content->text('chanda', 'title')">
    <x-page-banner page="chanda" />

    <div class="mx-auto max-w-[1160px] px-5 pt-12 pb-20">
        @if ($public && $target > 0)
            <div class="grid lg:grid-cols-12 gap-10 items-start">
                <div class="lg:col-span-7">
                    <p class="eyebrow text-ink-2">{{ __('Raising now') }}</p>
                    <h2 class="mt-2 font-display leading-tight text-[clamp(1.9rem,4vw,2.6rem)]">{{ $event->tr('title') }} <span class="block mt-2 font-sans text-[17px] text-ink-2 tracking-normal">{{ local_date($event->starts_at, 'j M Y') }} · <a href="{{ route('events.show', $event) }}" class="font-semibold text-sindoor hover:underline">{{ __('Puja details') }}</a></span></h2>
                    <div class="mt-6 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="font-display text-[clamp(2.8rem,7vw,4.5rem)] leading-none text-sindoor num">{{ digits($percent) }}%</span>
                        <span class="text-[17px] text-ink-2">{{ __('of the target raised so far') }}</span>
                    </div>
                    <div class="mt-5 h-3 rounded-full overflow-hidden bg-paper-2" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100, $percent) }}" aria-label="{{ __(':percent% of the target', ['percent' => digits($percent)]) }}"><div class="h-full bg-sindoor bar-grow" style="width: {{ min(100, $percent) }}%"></div></div>
                    <p class="mt-2 text-[14px] text-ink-2 num">{{ rupees($collected) }} {{ __('of') }} {{ rupees($target) }}</p>
                    <dl class="mt-6 grid grid-cols-3 gap-px bg-rule border border-rule rounded-lg overflow-hidden">
                        <div class="bg-paper p-4"><dt class="text-[13px] text-ink-2">{{ __('Houses visited') }}</dt><dd class="mt-1 font-display text-[1.9rem] leading-none num">{{ $houses ? digits($visited.'/'.$houses) : '—' }}</dd></div>
                        <div class="bg-paper p-4"><dt class="text-[13px] text-ink-2">{{ __('Sponsors') }}</dt><dd class="mt-1 font-display text-[1.9rem] leading-none num">{{ digits($sponsors->count()) }}</dd></div>
                        <div class="bg-paper p-4"><dt class="text-[13px] text-ink-2">{{ __('Days to the puja') }}</dt><dd class="mt-1 font-display text-[1.9rem] leading-none num">{{ digits($daysLeft) }}</dd></div>
                    </dl>
                    <p class="mt-3 text-sm text-ink-3">{{ __('Figures come straight from the club khata.') }}</p>
                </div>
                @if ($budget)
                    <div class="lg:col-span-5 rounded-2xl bg-paper-2 p-6">
                        <h3 class="font-display text-[1.6rem] leading-tight">{{ __('How it will be spent') }}</h3>
                        <p class="mt-1 text-[15px] text-ink-2">{{ __('The draft budget passed by the committee, shown per ₹100.') }}</p>
                        <ul class="mt-4 space-y-2.5 text-[15px]">
                            @foreach ($budget as $i => $row)
                                <li class="leader"><span>{{ local_text($row['head'], $row['head_bn']) }}</span><span class="font-semibold num">₹{{ digits($budgetShares[$i]) }}</span></li>
                            @endforeach
                            <li class="flex items-baseline justify-between pt-3 mt-1 border-t border-rule font-semibold"><span>{{ __('Of every ₹100') }}</span><span class="num">₹{{ digits(100) }}</span></li>
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Lanes --}}
            @if ($areas->isNotEmpty())
                <div class="mt-16">
                    <h2 class="font-display leading-tight text-[2rem]">{{ __('Lane by lane') }}</h2>
                    <p class="mt-2 text-[17px] text-ink-2 max-w-[60ch]">{{ __('One volunteer per lane goes door to door. Every gift gets a printed receipt.') }}</p>
                    <div class="mt-6 overflow-x-auto -mx-5 px-5">
                        <table class="w-full min-w-[560px] text-[15px]">
                            <caption class="sr-only">{{ __('Chanda collected by lane') }}</caption>
                            <thead><tr class="text-left text-[12.5px] uppercase tracking-wide text-ink-2 border-b border-rule">
                                <th scope="col" class="py-2.5 pr-4 font-semibold">{{ __('Lane') }}</th>
                                <th scope="col" class="py-2.5 pr-4 font-semibold">{{ __('Volunteer') }}</th>
                                <th scope="col" class="py-2.5 pr-4 font-semibold">{{ __('Houses visited') }}</th>
                                <th scope="col" class="py-2.5 font-semibold w-[38%]">{{ __('Collected') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($areas as $area)
                                    @php $p = $area->target ? (int) round(($summary['by_area'][$area->id] ?? 0) * 100 / $area->target) : 0; @endphp
                                    <tr class="border-b border-rule">
                                        <th scope="row" class="py-3 pr-4 text-left font-semibold">{{ $area->tr('name') }}</th>
                                        <td class="py-3 pr-4 text-ink-2">{{ $area->collector?->name ?? '—' }}</td>
                                        <td class="py-3 pr-4 num">@if ($area->houses){{ digits((int) $area->visited.' / '.$area->houses) }}@else — @endif</td>
                                        <td class="py-3"><div class="flex items-center gap-3"><div class="flex-1 h-2 bg-paper-2 rounded-full overflow-hidden" aria-hidden="true"><div class="h-full bg-sindoor rounded-full bar-grow" style="width: {{ min(100, $p) }}%"></div></div><span class="whitespace-nowrap text-[14px] font-semibold w-12 text-right num">{{ digits($p) }}%</span></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($donors->isNotEmpty())
                        <h3 class="mt-8 font-display text-[1.4rem]">{{ __('Thank you') }}</h3>
                        <p class="mt-1 text-ink-2 text-[15px]">{{ __('Donors who agreed to be named. Everyone else gives quietly, and that is just as welcome.') }}</p>
                        <p class="mt-3 text-[15px] leading-relaxed">{{ $donors->implode(' · ') }}</p>
                    @endif
                </div>
            @endif
        @endif

        {{-- How to give --}}
        <div class="{{ $public && $target > 0 ? 'mt-20' : '' }} grid lg:grid-cols-12 gap-10 items-start scroll-mt-24" id="give"
            x-data="{ amount: {{ Js::from(old('amount', 501)) }} }">
            <div class="lg:col-span-5">
                <h2 class="font-display leading-tight text-[2rem]">{{ __('How to give') }}</h2>
                <p class="mt-2 text-[17px] text-ink-2 max-w-[44ch]">{{ __('Give what you can. There is no fixed amount and nobody will press you.') }}</p>
                <ul class="mt-6 space-y-5">
                    <li class="flex gap-4"><span class="w-10 h-10 rounded-full bg-ink text-white grid place-items-center shrink-0"><x-icon name="qr-code" class="w-5 h-5" /></span><div><p class="font-semibold">{{ __('UPI') }}</p><p class="text-[15px] text-ink-2"><span>{{ __('Scan with any UPI app. Put your name and lane in the note.') }}</span> <span class="num font-semibold text-ink">{{ $club['upi'] }}</span></p></div></li>
                    <li class="flex gap-4"><span class="w-10 h-10 rounded-full bg-ink text-white grid place-items-center shrink-0"><x-icon name="landmark" class="w-5 h-5" /></span><div><p class="font-semibold">{{ __('Cash at the club room') }}</p><p class="text-[15px] text-ink-2">{{ __('Daily 6–9 pm. The treasurer gives you a receipt on the spot.') }}</p></div></li>
                    <li class="flex gap-4"><span class="w-10 h-10 rounded-full bg-ink text-white grid place-items-center shrink-0"><x-icon name="hand-coins" class="w-5 h-5" /></span><div><p class="font-semibold">{{ __('A volunteer visits') }}</p><p class="text-[15px] text-ink-2">{{ __('Tell us below and your lane’s volunteer will call at your door.') }}</p></div></li>
                </ul>
                <div class="mt-8 inline-flex items-center gap-5 card p-4">
                    <div class="w-32 h-32 shrink-0 [&>svg]:w-full [&>svg]:h-full" role="img" aria-label="{{ __('UPI QR code for the club account') }}">{!! \App\Support\QrCode::svg($upiUri, 128) !!}</div>
                    @if ($site->isSample())
                        <p class="text-[13px] text-ink-2 max-w-[18ch]">{{ __('Sample UPI ID. Please don’t send money.') }}</p>
                    @else
                        <p class="text-[13px] text-ink-2 max-w-[18ch]">{{ __('Scan with any UPI app.') }}</p>
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('forms.chanda') }}" class="lg:col-span-7 paper-card p-5 sm:p-8 grid sm:grid-cols-2 gap-4">
                @csrf
                @include('site.partials.honeypot', ['form' => 'pledge'])
                <div class="sm:col-span-2">
                    <p class="eyebrow text-sindoor">{{ __('Pledge a gift') }}</p>
                    <p class="mt-1 font-display text-[1.6rem] leading-tight">{{ __('Tell us how much and how') }}</p>
                </div>
                <x-field name="name" :label="__('Your name')" autocomplete="name" required />
                <x-field name="phone" type="tel" :label="__('Mobile')" autocomplete="tel" inputmode="tel" placeholder="98300 00000" required />
                <div class="sm:col-span-2">
                    <label for="f-area" class="label">{{ __('Lane / area') }}</label>
                    <select id="f-area" name="area" class="field" required>
                        @foreach (\App\Support\FormOptions::AREAS as $value => $option)
                            <option value="{{ $value }}" @selected(old('area') === $value)>{{ __($option['label']) }}</option>
                        @endforeach
                    </select>
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('Amount') }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([101, 251, 501, 1001, 2001] as $preset)
                            <button type="button" class="amt-chip" @click="amount = {{ $preset }}" :aria-pressed="(amount == {{ $preset }}).toString()">{{ rupees($preset) }}</button>
                        @endforeach
                    </div>
                    <label class="mt-3 block max-w-[220px]"><span class="sr-only">{{ __('Other amount') }}</span><input class="field num" name="amount" type="number" min="10" step="1" required inputmode="numeric" x-model="amount" placeholder="{{ __('Other amount (₹)') }}"></label>
                    <x-field-error name="amount" />
                </fieldset>
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('How you’ll give') }}</legend>
                    <div class="grid sm:grid-cols-3 gap-2">
                        @foreach (\App\Support\FormOptions::PAY_BY as $value => $option)
                            <label class="flex items-center gap-2.5 rounded-lg border border-rule p-3 cursor-pointer has-[:checked]:border-sindoor has-[:checked]:bg-sindoor-wash/50"><input type="radio" name="pay_by" value="{{ $value }}" class="accent-[#d21f1b]" @checked(old('pay_by', 'upi') === $value)><span>{{ __($option['label']) }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="flex items-start gap-2.5 sm:col-span-2 text-[15px]"><input type="checkbox" name="anonymous" value="1" class="mt-1 w-4 h-4 accent-[#d21f1b]" @checked(old('anonymous'))><span>{{ __('Don’t show my name on the public list') }}</span></label>
                <div class="sm:col-span-2"><button type="submit" class="btn btn-primary h-12 px-6"><span>{{ __('Send pledge') }}</span></button></div>
            </form>
        </div>

        @if ($sponsors->isNotEmpty())
            <div class="mt-20 grid lg:grid-cols-12 gap-10 items-start">
                <div class="lg:col-span-5">
                    <h2 class="font-display leading-tight text-[2rem]">{{ __('Sponsors') }}</h2>
                    <p class="mt-2 text-[17px] text-ink-2 max-w-[44ch]">{{ __('Local shops and businesses. Each gets a place at the puja: a banner, a board or a stage mention.') }}</p>
                    <a href="https://wa.me/{{ $club['whatsapp'] }}?text={{ rawurlencode(__('I would like to sponsor the puja')) }}" target="_blank" rel="noopener" class="btn btn-line mt-6"><x-icon name="message-circle" /><span>{{ __('I’d like to sponsor') }}</span></a>
                </div>
                <ul class="lg:col-span-7 border-t border-rule">
                    @foreach ($sponsors as $sponsor)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 py-4 border-b border-rule">
                            <span class="min-w-0 flex-1"><span class="block font-semibold text-[17px]">{{ $sponsor->tr('name') }}</span><span class="block text-[14px] text-ink-2">{{ $sponsor->tr('deliverable') }}</span></span>
                            @if ($sponsor->status === \App\Enums\SponsorStatus::Promised)
                                <span class="chip bg-paper-2 text-ink-2"><x-icon name="clock" />{{ __('Promised') }}</span>
                            @else
                                <span class="chip bg-tulsi-wash text-tulsi"><x-icon name="check" />{{ __('Paid') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($lastPuja && $spendTotal > 0)
            <div class="mt-20 grid lg:grid-cols-12 gap-10 items-start">
                <div class="lg:col-span-5">
                    <h2 class="font-display leading-tight text-[2rem]">{{ __(':puja: where it went', ['puja' => local_text($lastPuja['title'], $lastPuja['title_bn'] ?? null)]) }}</h2>
                    <p class="mt-2 text-[17px] text-ink-2 max-w-[44ch]">{{ __('Audited figures. Every bill is in the khata, and members can see the bill photos in the app.') }}</p>
                </div>
                <div class="lg:col-span-7">
                    <p class="flex items-baseline gap-3"><span class="font-display text-[clamp(2.4rem,6vw,3.5rem)] leading-none text-sindoor num">₹{{ digits(100) }}</span><span class="text-[15px] text-ink-2">{{ __('of chanda, shared out') }}</span></p>
                    <div class="mt-5 flex h-10 rounded-xl overflow-hidden" role="img" aria-label="{{ __('Of every ₹100') }}: {{ collect($lastPuja['spend'])->map(fn ($row, $i) => local_text($row['head'], $row['head_bn'] ?? null).' ₹'.digits($spendShares[$i]))->implode(', ') }}">
                        @foreach ($lastPuja['spend'] as $i => $row)
                            <div style="flex: {{ $spendShares[$i] }}; background: {{ $colour($row) }}" class="h-full border-r-2 border-paper last:border-r-0"></div>
                        @endforeach
                    </div>
                    <ul class="mt-5 grid sm:grid-cols-2 gap-x-8">
                        @foreach ($lastPuja['spend'] as $i => $row)
                            <li class="flex items-center gap-3 py-2.5 border-b border-rule"><span class="w-3 h-3 rounded-sm shrink-0" style="background: {{ $colour($row) }}" aria-hidden="true"></span><span class="text-[15px]">{{ local_text($row['head'], $row['head_bn'] ?? null) }}</span><span class="ml-auto text-[15px] font-semibold num">₹{{ digits($spendShares[$i]) }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Three books --}}
        <div class="mt-20">
            <h2 class="font-display leading-tight text-[2rem]">{{ __('The club’s three books') }}</h2>
            <p class="mt-2 text-[17px] text-ink-2 max-w-[60ch]">{{ __('The club keeps three separate books. What the serving team and the nursery earn goes to the club’s work.') }}</p>
            <div class="mt-6 grid md:grid-cols-3 gap-5">
                @foreach ([[\App\Enums\BookCode::Club, __('Subscriptions, chanda and the pujas')], [\App\Enums\BookCode::Catering, __('Maitree Serving bookings and wages')], [\App\Enums\BookCode::Nursery, __('Plant sales and stock')]] as [$book, $about])
                    <article class="book-{{ $book->value }} card border-t-4 p-6" style="border-top-color: var(--book)">
                        <h3 class="font-display text-[1.6rem] leading-tight" style="color: var(--book)">{{ $book->label() }}</h3>
                        <p class="mt-2 text-[15px] text-ink-2">{{ $about }}</p>
                        <p class="mt-4 text-[13.5px] text-ink-3 flex items-center gap-1.5"><x-icon name="lock" class="w-4 h-4" />{{ __('Figures and bills for members') }}</p>
                    </article>
                @endforeach
            </div>
            <div class="mt-5 rounded-2xl bg-paper-2 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4">
                <span class="w-11 h-11 rounded-full bg-ink text-white grid place-items-center shrink-0"><x-icon name="book-open-check" class="w-5 h-5" /></span>
                <p class="flex-1 text-[15px] text-ink-2">{{ __('Members see every receipt, expense and bill photo in the member app. The audited yearly accounts are presented at the AGM, and anyone can ask the treasurer to see them at the club room.') }}</p>
                <a href="{{ route('member.login') }}" class="btn btn-ink shrink-0"><x-icon name="log-in" /><span>{{ __('Member login') }}</span></a>
            </div>
        </div>

        @if (! empty($chanda['documents']))
            <div class="mt-20">
                <h2 class="font-display leading-tight text-[2rem]">{{ __('Documents') }}</h2>
                <ul class="mt-6 border-t border-rule">
                    @foreach ($chanda['documents'] as $doc)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-4 border-b border-rule">
                            <span class="w-10 h-10 rounded-lg bg-paper-2 grid place-items-center shrink-0"><x-icon name="file-text" class="w-5 h-5 text-ink-2" /></span>
                            <span class="min-w-0 flex-1"><span class="block font-semibold">{{ local_text($doc['title'], $doc['title_bn'] ?? null) }}</span><span class="block text-[14px] text-ink-2"><span class="num">{{ local_text($doc['note'], $doc['note_bn'] ?? null) }}</span> · PDF · {{ local_text($doc['size'], $doc['size_bn'] ?? null) }}</span></span>
                            @if (! empty($doc['members']))
                                <a href="{{ route('member.login') }}" class="btn btn-line btn-sm"><x-icon name="lock" />{{ __('Members: sign in') }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 text-sm text-ink-3">{{ __('Want to see the originals? Ask the treasurer at the club room.') }}</p>
            </div>
        @endif
    </div>
</x-layouts.site>
