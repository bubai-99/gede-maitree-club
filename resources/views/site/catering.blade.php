@php
    $content = app(\App\Support\PageContent::class);
    $discount = (int) config('club.member_discount_percent');
    $packageData = $packages->map(fn ($p) => ['slug' => $p->slug, 'rate' => $p->rate, 'member' => $p->memberRate(), 'min' => $p->min_guests])->values();
    $from = $packages->min('rate');
@endphp
<x-layouts.site seo="catering" :title="$content->text('catering', 'title')">
    <div class="book-catering"><x-page-banner page="catering" /></div>

    <div class="mx-auto max-w-[1160px] px-5 py-14 book-catering">
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ([[__('Guests'), digits('50–300')], [__('Per guest from'), $from ? rupees((int) $from) : '—'], [__('Book with'), __('20% advance')], [__('Servers'), __('1 per 15 guests')]] as [$label, $value])
                <div class="card p-5"><dt class="text-[13px] font-semibold uppercase tracking-wide text-ink-3">{{ $label }}</dt><dd class="mt-1 font-display text-[1.6rem] num">{{ $value }}</dd></div>
            @endforeach
        </dl>

        <div class="mt-16 flex flex-wrap items-end justify-between gap-4">
            <div><p class="gz-kicker text-turmeric">{{ __('At work') }}</p><h2 class="mt-2 gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('See the team at work') }}</h2></div>
            <p class="text-ink-2 max-w-[44ch]">{{ __('Uniformed servers, gloves on at all times, and tables cleared between every batch.') }}</p>
        </div>
        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ([['catBuffet', __('Buffet counters, Full setup')], ['catPlating', __('Gloves on, always')], ['catWaiter', __('Uniformed servers')], ['catTable', __('Tables laid before guests arrive')]] as [$key, $caption])
                <figure>
                    <div class="aspect-[4/5] rounded-[14px] overflow-hidden bg-paper-3"><x-photo :key="$key" :width="640" sizes="(min-width: 1024px) 25vw, 50vw" /></div>
                    <figcaption class="mt-2 text-[14px] text-ink-2">{{ $caption }} <span class="block text-[12px] text-ink-3"><x-photo-credit :key="$key" /></span></figcaption>
                </figure>
            @endforeach
        </div>
        <p class="mt-3 text-[13px] text-ink-3">{{ __('To be replaced with the club’s own photos.') }}</p>

        <h2 class="mt-16 gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Packages, per guest') }}</h2>
        <div class="mt-6 grid md:grid-cols-3 gap-5">
            @foreach ($packages as $package)
                <article class="card p-6 flex flex-col {{ $package->popular ? 'ring-2 ring-turmeric' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-display text-[1.6rem] leading-tight">{{ $package->tr('name') }}</h3>
                        @if ($package->popular)
                            <span class="chip bg-turmeric-wash text-turmeric">{{ __('Most booked') }}</span>
                        @endif
                    </div>
                    <p class="mt-1 text-ink-2">{{ $package->tr('for') }}</p>
                    <p class="mt-4"><span class="font-display text-[2.4rem] num">{{ rupees($package->rate) }}</span> <span class="text-ink-2">{{ __('a guest') }}</span></p>
                    <p class="text-[14px] text-ink-3 num">{{ __('Members :rate · at least :min guests', ['rate' => rupees($package->memberRate()), 'min' => digits($package->min_guests)]) }}</p>
                    <ul class="mt-5 space-y-2 text-[15px] flex-1">
                        @foreach ($package->tr('menu') ?? [] as $line)
                            <li class="flex gap-2.5"><x-icon name="check" class="w-4 h-4 mt-1 text-turmeric" /><span>{{ $line }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('catering', ['package' => $package->slug]) }}#quote" class="mt-6 btn btn-line">{{ __('Ask for a quote') }}</a>
                </article>
            @endforeach
        </div>
        <p class="mt-4 text-ink-2">{{ $content->text('catering', 'note') }}</p>

        <div class="mt-16 grid lg:grid-cols-2 gap-10">
            <div>
                <h2 class="font-display text-[1.8rem]">{{ __('How booking works') }}</h2>
                <p class="mt-2 text-ink-2">{{ __('One person looks after you, from the first call to the last batch.') }}</p>
                <ol class="mt-6 space-y-4">
                    @foreach ([[__('Send an enquiry'), __('Below or on WhatsApp: date, guests, package.')], [__('Get a quotation'), __('Within a day, as a stamped PDF.')], [__('Book with 20% advance'), __('Cash or UPI, with a receipt on the spot.')], [__('Settle after the event'), __('Final bill on the actual guest count.')]] as $i => [$title, $text])
                        <li class="flex gap-4"><span class="w-9 h-9 rounded-full bg-turmeric text-white grid place-items-center font-semibold num shrink-0">{{ digits($i + 1) }}</span><div><p class="font-semibold">{{ $title }}</p><p class="text-ink-2">{{ $text }}</p></div></li>
                    @endforeach
                </ol>
            </div>
            <div>
                <h2 class="font-display text-[1.8rem]">{{ __('Occasions we serve at') }}</h2>
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ([__('Annaprashan'), __('Wedding'), __('Reception'), __('Upanayan'), __('Birthday'), __('Shraddha & niyombhongo'), __('Office lunch'), __('Reunion')] as $occasion)
                        <li class="chip bg-turmeric-wash text-turmeric">{{ $occasion }}</li>
                    @endforeach
                </ul>
                <p class="mt-4 text-ink-2">{{ __('Vegetarian and shraddha meals are served with separate utensils and a separate team.') }}</p>
            </div>
        </div>

        {{-- Enquiry with a live estimate --}}
        <div id="quote" class="mt-16 grid lg:grid-cols-12 gap-8 scroll-mt-24"
            x-data="{ packages: {{ Js::from($packageData) }}, package: '{{ old('package', request('package', $packages->firstWhere('popular', true)?->slug ?? $packages->first()?->slug)) }}', guests: {{ (int) old('guests', 120) }}, member: {{ old('is_member') ? 'true' : 'false' }},
                get rate() { const p = this.packages.find(p => p.slug === this.package); return p ? (this.member ? p.member : p.rate) : 0 },
                get servers() { return Math.max(2, Math.ceil((parseInt(this.guests) || 0) / 15)) },
                get total() { return this.rate * (parseInt(this.guests) || 0) },
                inr(n) { return new Intl.NumberFormat('{{ app()->getLocale() === 'bn' ? 'bn-IN' : 'en-IN' }}').format(n) } }">
            <div class="lg:col-span-5">
                <h2 class="font-display text-[2rem] leading-tight">{{ __('Ask for a quote') }}</h2>
                <p class="mt-3 text-ink-2">{{ $content->text('catering', 'quote') }}</p>
                <div class="mt-6 gz-ticket p-6" aria-live="polite">
                    <p class="text-[12px] font-bold uppercase tracking-[0.14em]">{{ __('Estimate') }}</p>
                    <p class="mt-2 font-display text-[2.4rem] num">₹<span x-text="inr(total)"></span></p>
                    <p class="text-[14px] num"><span x-text="inr(rate)"></span> × <span x-text="inr(parseInt(guests) || 0)"></span> · <span x-text="inr(servers)"></span> {{ __('servers') }}</p>
                </div>
                <p class="mt-3 text-[13px] text-ink-3">{{ __('The final bill is on the actual guest count.') }}</p>
            </div>
            <form method="POST" action="{{ route('forms.catering') }}" class="lg:col-span-7 card p-6 grid sm:grid-cols-2 gap-4">
                @csrf
                @include('site.partials.honeypot')
                <x-field name="name" :label="__('Your name')" autocomplete="name" required />
                <x-field name="phone" type="tel" :label="__('Phone')" autocomplete="tel" inputmode="tel" placeholder="98300 00000" required />
                <div>
                    <label for="f-occasion" class="label">{{ __('Occasion') }}</label>
                    <select id="f-occasion" name="occasion" class="field" required>
                        @foreach (\App\Support\FormOptions::OCCASIONS as $value => $option)
                            <option value="{{ $value }}" @selected(old('occasion') === $value)>{{ __($option['label']) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field name="event_date" type="date" :label="__('Date')" :min="now()->toDateString()" required />
                <x-field name="guests" type="number" :label="__('Guests')" min="10" max="1000" x-model="guests" :value="old('guests', 120)" required />
                <div>
                    <label for="f-package" class="label">{{ __('Package') }}</label>
                    <select id="f-package" name="package" class="field" x-model="package" required>
                        @foreach ($packages as $package)
                            <option value="{{ $package->slug }}">{{ $package->tr('name') }} · {{ rupees($package->rate) }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="package" />
                </div>
                <label class="sm:col-span-2 flex items-center gap-2.5 text-[15px]"><input type="checkbox" name="is_member" value="1" x-model="member" class="w-4 h-4 accent-turmeric"> {{ __('I am a Gede Maitree Club member') }}</label>
                <x-field class="sm:col-span-2" name="venue" :label="__('Venue and anything else')" textarea rows="3" :placeholder="__('e.g. on the roof, from 7 pm, 20 vegetarian guests')" />
                <div class="sm:col-span-2"><button type="submit" class="btn btn-primary h-12 px-6">{{ __('Send enquiry') }}</button></div>
            </form>
        </div>
    </div>
</x-layouts.site>
