@php
    $content = app(\App\Support\PageContent::class);
    // Shown only when the committee publishes the drive's totals (Console → Puja chanda → Budget).
    $target = $public ? (int) $event->chanda_target : 0;
    $collected = $summary['collected'] ?? 0;
    $budget = $public ? array_map(fn ($b) => ['head' => $b['head'] ?? '', 'head_bn' => $b['head_bn'] ?? null, 'amount' => (int) ($b['amount'] ?? 0)], $event->budget ?? []) : [];
    $budgetTotal = array_sum(array_column($budget, 'amount'));
    $lastPuja = $chanda['last_puja'] ?? null;
    $spendTotal = $lastPuja ? array_sum(array_column($lastPuja['spend'], 'amount')) : 0;
    $percent = $target > 0 ? min(100, (int) round($collected * 100 / $target)) : 0;
    $club = $site->club();
@endphp
<x-layouts.site seo="chanda" :title="$content->text('chanda', 'title')">
    <x-page-banner page="chanda" />

    <div class="mx-auto max-w-[1160px] px-5 py-14">
        @if ($public && $target > 0)
            <section class="grid lg:grid-cols-12 gap-8 items-start" aria-labelledby="drive-h">
                <div class="lg:col-span-5">
                    <p class="gz-kicker">{{ __('Raising now') }}</p>
                    <h2 id="drive-h" class="mt-2 gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ $event->tr('title') }}</h2>
                    <p class="mt-4 font-display text-[2.6rem] num">{{ rupees($collected) }} <span class="text-[1.2rem] text-ink-2">/ {{ rupees($target) }}</span></p>
                    <div class="mt-3 h-3 rounded-full bg-paper-2 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-label="{{ __('Raised so far') }}">
                        <div class="h-full bg-sindoor rounded-full bar-grow" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="mt-2 text-[14px] text-ink-2 num">{{ __(':percent% of the target', ['percent' => digits($percent)]) }}</p>
                    @if ($event)
                        <a href="{{ route('events.show', $event) }}" class="mt-4 inline-flex items-center gap-1.5 font-semibold text-sindoor hover:underline"><span>{{ __('About the puja') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
                    @endif
                </div>
                <div class="lg:col-span-7">
                    <h3 class="font-display text-[1.5rem]">{{ __('How it will be spent') }}</h3>
                    <p class="text-ink-2">{{ __('The draft budget passed by the committee, shown per ₹100.') }}</p>
                    <div class="mt-4">@include('site.partials.spend-ledger', ['spend' => array_map(fn ($b) => $b + ['colour' => '#d21f1b'], $budget), 'total' => $budgetTotal])</div>
                </div>
            </section>

            <section class="mt-16" aria-labelledby="lanes-h">
                <h2 id="lanes-h" class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Lane by lane') }}</h2>
                <p class="mt-2 text-ink-2">{{ __('One volunteer per lane goes door to door. Every gift gets a printed receipt.') }}</p>
                <div class="mt-5 overflow-x-auto rounded-xl border border-rule khata">
                    <table class="khata-table">
                        <caption class="sr-only">{{ __('Chanda collected by lane') }}</caption>
                        <thead><tr><th scope="col" class="pl-16">{{ __('Lane') }}</th><th scope="col">{{ __('Volunteer') }}</th><th scope="col" class="amt">{{ __('Houses visited') }}</th><th scope="col" class="amt">{{ __('Collected') }}</th></tr></thead>
                        <tbody>
                            @foreach ($areas as $area)
                                <tr>
                                    <td class="pl-16">{{ $area->tr('name') }}</td>
                                    <td>{{ $area->collector?->name ?? '—' }}</td>
                                    <td class="amt">@if ($area->houses){{ digits((int) $area->visited) }} / {{ digits($area->houses) }}@else — @endif</td>
                                    <td class="amt">{{ rupees((int) ($summary['by_area'][$area->id] ?? 0)) }} <span class="text-ink-3">/ {{ rupees($area->target) }}</span></td>
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
            </section>
        @endif

        <section class="mt-16 grid lg:grid-cols-12 gap-8" aria-labelledby="give-h">
            <div class="lg:col-span-5">
                <h2 id="give-h" class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('How to give') }}</h2>
                <p class="mt-2 text-ink-2">{{ __('Give what you can. There is no fixed amount and nobody will press you.') }}</p>
                <ul class="mt-5 space-y-4">
                    <li class="flex gap-3"><x-icon name="smartphone" class="w-6 h-6 text-sindoor" /><div><p class="font-semibold">{{ __('UPI') }} · <span class="num">{{ $club['upi'] }}</span></p><p class="text-ink-2">{{ __('Scan with any UPI app. Put your name and lane in the note.') }}</p></div></li>
                    <li class="flex gap-3"><x-icon name="banknote" class="w-6 h-6 text-sindoor" /><div><p class="font-semibold">{{ __('Cash at the club room') }}</p><p class="text-ink-2">{{ __('Daily 6–9 pm. The treasurer gives you a receipt on the spot.') }}</p></div></li>
                    <li class="flex gap-3"><x-icon name="house" class="w-6 h-6 text-sindoor" /><div><p class="font-semibold">{{ __('A volunteer visits') }}</p><p class="text-ink-2">{{ __('Tell us below and your lane’s volunteer will call at your door.') }}</p></div></li>
                </ul>
                @if ($site->isSample())
                    <p class="mt-4 text-[13px] text-sindoor-deep">{{ __('Sample UPI ID. Please don’t send money.') }}</p>
                @endif
            </div>
            <form method="POST" action="{{ route('forms.chanda') }}" class="lg:col-span-7 card p-6 grid sm:grid-cols-2 gap-4">
                @csrf
                @include('site.partials.honeypot', ['form' => 'pledge'])
                <h3 class="sm:col-span-2 font-display text-[1.5rem]">{{ __('Pledge a gift') }}</h3>
                <x-field name="name" :label="__('Your name')" autocomplete="name" required />
                <x-field name="phone" type="tel" :label="__('Mobile')" autocomplete="tel" inputmode="tel" required />
                <div>
                    <label for="f-area" class="label">{{ __('Lane / area') }}</label>
                    <select id="f-area" name="area" class="field" required>
                        @foreach (\App\Support\FormOptions::AREAS as $value => $option)
                            <option value="{{ $value }}" @selected(old('area') === $value)>{{ __($option['label']) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field name="amount" type="number" :label="__('Amount')" min="10" step="1" inputmode="numeric" required />
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('How you’ll give') }}</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach (\App\Support\FormOptions::PAY_BY as $value => $option)
                            <label class="flex items-center gap-2"><input type="radio" name="pay_by" value="{{ $value }}" class="accent-sindoor" @checked(old('pay_by', 'upi') === $value)> {{ __($option['label']) }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="sm:col-span-2 flex items-center gap-2.5 text-[15px]"><input type="checkbox" name="anonymous" value="1" class="w-4 h-4 accent-sindoor"> {{ __('Don’t show my name on the public list') }}</label>
                <div class="sm:col-span-2"><button type="submit" class="btn btn-primary h-12 px-6">{{ __('Send pledge') }}</button></div>
            </form>
        </section>

        @if ($sponsors->isNotEmpty())
            <section class="mt-16" aria-labelledby="sponsors-h">
                <h2 id="sponsors-h" class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Sponsors') }}</h2>
                <p class="mt-2 text-ink-2">{{ __('Local shops and businesses. Each gets a place at the puja: a banner, a board or a stage mention.') }}</p>
                <ul class="mt-5 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($sponsors as $sponsor)
                        <li class="card p-5"><p class="font-semibold">{{ $sponsor->tr('name') }}</p><p class="text-ink-2 text-[14px]">{{ $sponsor->tr('deliverable') }}</p><p class="mt-1 font-display text-[1.3rem] num">{{ rupees($sponsor->amount) }}</p></li>
                    @endforeach
                </ul>
                <a href="https://wa.me/{{ $club['whatsapp'] }}" target="_blank" rel="noopener" class="mt-5 btn btn-line"><x-icon name="message-circle" /><span>{{ __('I’d like to sponsor') }}</span></a>
            </section>
        @endif

        @if ($lastPuja && $spendTotal > 0)
            <section class="mt-16 grid lg:grid-cols-12 gap-8 items-start" aria-labelledby="last-h">
                <div class="lg:col-span-5">
                    <h2 id="last-h" class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __(':puja: where it went', ['puja' => local_text($lastPuja['title'], $lastPuja['title_bn'] ?? null)]) }}</h2>
                    <p class="mt-2 text-ink-2">{{ __('Audited figures. Every bill is in the khata, and members can see the bill photos in the app.') }}</p>
                    <p class="mt-4 font-display text-[2.2rem] num">{{ rupees($spendTotal) }}</p>
                </div>
                <div class="lg:col-span-7">@include('site.partials.spend-ledger', ['spend' => $lastPuja['spend'], 'total' => $spendTotal])</div>
            </section>
        @endif

        <section class="mt-16" aria-labelledby="books-h">
            <h2 id="books-h" class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('The club’s three books') }}</h2>
            <p class="mt-2 text-ink-2 max-w-[62ch]">{{ __('The club keeps three separate books. What the serving team and the nursery earn goes to the club’s work.') }}</p>
            <ul class="mt-5 grid md:grid-cols-3 gap-4">
                @foreach ([['book-club', \App\Enums\BookCode::Club], ['book-catering', \App\Enums\BookCode::Catering], ['book-nursery', \App\Enums\BookCode::Nursery]] as [$class, $book])
                    <li class="{{ $class }} card p-5 border-t-4" style="border-top-color: var(--book)"><p class="font-display text-[1.4rem]">{{ $book->label() }}</p><p class="text-[14px] text-ink-3 num">{{ $book->receiptPrefix() }}-…</p></li>
                @endforeach
            </ul>
            <p class="mt-4 text-ink-2 max-w-[62ch]">{{ __('Members see every receipt, expense and bill photo in the member app. The audited yearly accounts are presented at the AGM.') }}</p>
        </section>

        @if (! empty($chanda['documents']))
            <section class="mt-16" aria-labelledby="docs-h">
                <h2 id="docs-h" class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Documents') }}</h2>
                <p class="mt-2 text-ink-2">{{ __('Want to see the originals? Ask the treasurer at the club room.') }}</p>
                <ul class="mt-5 grid sm:grid-cols-2 gap-4">
                    @foreach ($chanda['documents'] as $doc)
                        <li class="card p-5 flex gap-4"><x-icon name="file-text" class="w-6 h-6 text-ink-3" /><div><p class="font-semibold">{{ local_text($doc['title'], $doc['title_bn'] ?? null) }}</p><p class="text-[14px] text-ink-2">{{ local_text($doc['note'], $doc['note_bn'] ?? null) }} · {{ local_text($doc['size'], $doc['size_bn'] ?? null) }}</p></div></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.site>
