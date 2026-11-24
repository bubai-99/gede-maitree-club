@php
    $content = app(\App\Support\PageContent::class);
    $discount = app(\App\Support\SiteSettings::class)->memberDiscount();
    $packageData = $packages->map(fn ($p) => ['slug' => $p->slug, 'rate' => $p->rate, 'member' => $p->memberRate(), 'min' => $p->min_guests])->values();
    $from = $packages->min('rate');
    // Sample photos for the package cards until the club adds its own
    $packagePhotos = ['catPlating', 'catBanana', 'catBuffet'];
    $club = $site->club();
    // How booking works and the occasions: text from Website → Pages → Serving
    $steps = collect(['message-circle', 'file-text', 'wallet', 'receipt'])->map(fn ($icon, $i) => [
        $icon, $content->text('catering', 'step'.($i + 1).'_title'), $content->text('catering', 'step'.($i + 1).'_text'),
    ])->filter(fn ($step) => trim($step[1]) !== '')->values();
    // Occasions: one per line; the icon comes from the English line in the same place (a new one gets a general icon)
    $lines = fn (string $text) => array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: [])));
    $occasionNames = $lines($content->text('catering', 'occasions'));
    $englishOccasions = app()->getLocale() === 'bn' ? $lines((string) (($content->page('catering')?->blocks ?? [])['occasions'] ?? '') ?: (string) config('pages.catering.fields.occasions.default')) : $occasionNames;
    $occasionLook = function (string $name): array {
        $n = mb_strtolower($name);
        foreach ([
            ['annaprash', 'baby', 'bg-marigold-wash text-turmeric'], ['rice', 'baby', 'bg-marigold-wash text-turmeric'],
            ['wedding', 'heart', 'bg-sindoor-wash text-sindoor'], ['marriage', 'heart', 'bg-sindoor-wash text-sindoor'],
            ['reception', 'party-popper', 'bg-turmeric-wash text-turmeric'], ['bou bhat', 'party-popper', 'bg-turmeric-wash text-turmeric'],
            ['upanayan', 'flame', 'bg-sindoor-wash text-sindoor-deep'], ['puja', 'flame', 'bg-sindoor-wash text-sindoor-deep'],
            ['birthday', 'cake', 'bg-marigold-wash text-turmeric'], ['anniversary', 'cake', 'bg-marigold-wash text-turmeric'],
            ['shraddha', 'flower-2', 'bg-tulsi-wash text-tulsi'], ['niyom', 'flower-2', 'bg-tulsi-wash text-tulsi'],
            ['office', 'briefcase', 'bg-paper-2 text-ink-2'], ['lunch', 'briefcase', 'bg-paper-2 text-ink-2'],
            ['reunion', 'users', 'bg-tulsi-wash text-tulsi'], ['get-together', 'users', 'bg-tulsi-wash text-tulsi'],
        ] as [$word, $icon, $tone]) {
            if (str_contains($n, $word)) {
                return [$icon, $tone];
            }
        }

        return ['sparkles', 'bg-turmeric-wash text-turmeric'];
    };
@endphp
<x-layouts.site seo="catering" :title="$content->text('catering', 'title')">
    <div class="book-catering"
        x-data="{ packages: {{ Js::from($packageData) }}, package: '{{ old('package', request('package', $packages->firstWhere('popular', true)?->slug ?? $packages->first()?->slug)) }}', guests: {{ (int) old('guests', 120) }}, member: {{ old('is_member') ? 'true' : 'false' }},
            get pick() { return this.packages.find(p => p.slug === this.package) },
            get rate() { const p = this.pick; return p ? (this.member ? p.member : p.rate) : 0 },
            get servers() { return Math.max(2, Math.ceil((parseInt(this.guests) || 0) / 15)) },
            get total() { return this.rate * (parseInt(this.guests) || 0) },
            choose(slug) { this.package = slug; document.getElementById('quote').scrollIntoView({ behavior: 'smooth', block: 'start' }) },
            inr(n) { return new Intl.NumberFormat('{{ app()->getLocale() === 'bn' ? 'bn-IN' : 'en-IN' }}').format(n) } }">
        <x-page-banner page="catering" :crumb="__('Catering')" kicker-class="text-[#f5c463]">
            <div class="mt-7 flex flex-wrap gap-2.5">
                <a href="#quote" class="btn bg-turmeric text-white hover:bg-[#7a4f00] h-12 px-6"><span>{{ __('Get a quote') }}</span></a>
                <a href="https://wa.me/{{ $club['whatsapp'] }}?text={{ rawurlencode(__('Catering enquiry')) }}" target="_blank" rel="noopener" class="btn btn-glass h-12 px-5"><x-icon name="message-circle" /><span>{{ __('WhatsApp us') }}</span></a>
            </div>
        </x-page-banner>
        <div class="border-b border-rule">
            <dl class="mx-auto max-w-[1160px] px-5 grid grid-cols-2 md:grid-cols-4">
                <div class="py-6 pr-4 md:border-r border-rule"><dt class="text-sm text-ink-2">{{ __('Guests') }}</dt><dd class="font-display text-[1.9rem] leading-tight num">{{ digits('50–300') }}</dd></div>
                <div class="py-6 md:px-6 md:border-r border-rule"><dt class="text-sm text-ink-2">{{ __('Per guest from') }}</dt><dd class="font-display text-[1.9rem] leading-tight num">{{ $from ? rupees((int) $from) : '—' }}</dd></div>
                <div class="py-6 pr-4 md:px-6 md:border-r border-rule"><dt class="text-sm text-ink-2">{{ __('Book with') }}</dt><dd class="font-display text-[1.9rem] leading-tight">{{ __('20% advance') }}</dd></div>
                <div class="py-6 md:pl-6"><dt class="text-sm text-ink-2">{{ __('Servers') }}</dt><dd class="font-display text-[1.9rem] leading-tight">{{ __('1 per 15 guests') }}</dd></div>
            </dl>
        </div>

        {{-- See the team at work --}}
        <div class="mx-auto max-w-[1160px] px-5 pt-14">
            <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                <div><p class="eyebrow text-turmeric">{{ __('At work') }}</p><h2 class="mt-2 font-display leading-tight text-[clamp(1.9rem,4vw,2.6rem)]">{{ __('See the team at work') }}</h2></div>
                <p class="text-[16px] text-ink-2 max-w-[44ch]">{{ __('Uniformed servers, gloves on at all times, and tables cleared between every batch.') }}</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 auto-rows-[150px] sm:auto-rows-[190px] md:auto-rows-[210px] gap-2.5 sm:gap-3">
                @foreach ([['catBuffet', __('Buffet counters, Full setup'), 'col-span-2 row-span-2'], ['catPlating', __('Gloves on, always'), ''], ['catWaiter', __('Uniformed servers'), ''], ['catTable', __('Tables laid before guests arrive'), 'col-span-2']] as [$key, $caption, $span])
                    <figure class="relative overflow-hidden rounded-2xl bg-paper-2 group {{ $span }}"><div class="absolute inset-0 transition-transform duration-700 group-hover:scale-[1.04]"><x-photo :key="$key" :width="900" sizes="(min-width: 768px) 570px, 100vw" /></div><figcaption class="absolute left-3 bottom-3 chip h-7 px-2.5 bg-black/65 text-white text-[12.5px]">{{ $caption }}</figcaption></figure>
                @endforeach
            </div>
            <p class="mt-3 text-[12px] text-ink-3">{{ __('Photos') }}: <x-photo-credit key="catBuffet" />, <x-photo-credit key="catPlating" />, <x-photo-credit key="catWaiter" />, <x-photo-credit key="catTable" />. {{ __('To be replaced with the club’s own photos.') }}</p>
        </div>

        <div class="mx-auto max-w-[1160px] px-5 py-14">
            <h2 class="font-display leading-tight text-[clamp(1.9rem,4vw,2.6rem)]">{{ __('Packages, per guest') }}</h2>
            <div class="mt-6 grid md:grid-cols-3 gap-5">
                @foreach ($packages as $i => $package)
                    <article class="relative card card-hover p-6 flex flex-col {{ $package->popular ? '!border-sindoor ring-1 ring-sindoor' : '' }}">
                        @if ($package->popular)
                            <span class="absolute -top-3.5 left-6 chip bg-sindoor text-white">{{ __('Most booked') }}</span>
                        @endif
                        <div class="relative -mx-2 -mt-2 mb-5 aspect-[16/10] rounded-xl overflow-hidden bg-paper-2"><x-photo :key="$packagePhotos[$i] ?? 'feast'" :width="700" sizes="(min-width: 768px) 360px, 100vw" class="absolute inset-0 w-full h-full object-cover" /></div>
                        <h3 class="font-display text-[1.9rem] leading-tight">{{ $package->tr('name') }}</h3>
                        <p class="text-[15px] text-ink-2 mt-1">{{ $package->tr('for') }}</p>
                        <p class="mt-4 flex items-baseline gap-1.5"><span class="font-display text-4xl text-turmeric num">{{ rupees($package->rate) }}</span><span class="text-sm text-ink-3">{{ __('per guest') }}</span></p>
                        <ul class="mt-5 pt-5 border-t border-rule space-y-2.5 text-[15px] flex-1">
                            @foreach ($package->tr('menu') ?? [] as $line)
                                <li class="flex gap-2.5"><x-icon name="check" class="w-[18px] h-[18px] mt-0.5 shrink-0 text-turmeric" /><span>{{ $line }}</span></li>
                            @endforeach
                        </ul>
                        <p class="mt-4 text-sm text-ink-3 num">{{ __('Minimum :count guests', ['count' => digits($package->min_guests)]) }} · {{ __('Members :rate', ['rate' => rupees($package->memberRate())]) }}</p>
                        <a href="{{ route('catering', ['package' => $package->slug]) }}#quote" @click.prevent="choose('{{ $package->slug }}')" class="btn {{ $package->popular ? 'btn-primary' : 'btn-line' }} mt-6 w-full">{{ __('Get a quote for this') }}<x-icon name="arrow-down" /></a>
                    </article>
                @endforeach
            </div>
            <p class="mt-4 text-[15px] text-ink-3">{{ str_replace(':percent', digits($discount), $content->text('catering', 'note')) }}</p>

            {{-- How booking works: four numbered step cards, each with its icon --}}
            <section class="mt-16" aria-labelledby="how-book">
                <p class="eyebrow text-turmeric">{{ __('Four steps') }}</p>
                <h2 id="how-book" class="mt-2 font-display leading-tight text-[clamp(1.9rem,4vw,2.6rem)]">{{ __('How booking works') }}</h2>
                <p class="mt-2 text-[17px] text-ink-2 max-w-[52ch]">{{ __('One person looks after you, from the first call to the last batch.') }}</p>
                <ol class="mt-8 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
                    @foreach ($steps as $i => [$icon, $title, $text])
                        {{-- Phones: the icon beside the text; wider: the icon on top with the big number --}}
                        <li class="relative flex gap-4 sm:block rounded-2xl bg-card border border-rule p-4 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,.04)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-18px_rgba(120,80,0,.45)] hover:border-turmeric/40">
                            <div class="shrink-0 sm:flex sm:items-center sm:justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-turmeric-wash text-turmeric grid place-items-center"><x-icon :name="$icon" class="w-6 h-6" /></span>
                                <span class="absolute right-4 top-3 sm:static font-display text-[2rem] sm:text-[2.6rem] leading-none text-turmeric/20 num" aria-hidden="true">{{ digits($i + 1) }}</span>
                            </div>
                            <div class="min-w-0 pr-6 sm:pr-0">
                                <p class="sm:mt-4 text-[12px] font-semibold uppercase tracking-[0.14em] text-turmeric">{{ __('Step :n', ['n' => digits($i + 1)]) }}</p>
                                <h3 class="mt-1 font-semibold text-[17px] leading-snug">{{ $title }}</h3>
                                <p class="mt-1.5 text-[15px] text-ink-2 leading-relaxed">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl bg-turmeric-wash/45 px-5 py-4">
                    <p class="flex-1 min-w-[15rem] flex gap-3 text-[15px] text-ink-2"><x-icon name="clock" class="w-5 h-5 text-turmeric shrink-0 mt-0.5" /><span>{{ $content->text('catering', 'steps_note') }}</span></p>
                    <a href="#quote" class="btn btn-sm bg-turmeric text-white hover:bg-[#7d5100]"><x-icon name="arrow-down" />{{ __('Ask for a quote') }}</a>
                    <a href="https://wa.me/{{ $club['whatsapp'] }}?text={{ rawurlencode(__('Namaskar, I would like to book Maitree Serving.')) }}" target="_blank" rel="noopener" class="btn btn-sm btn-line bg-white"><x-icon name="message-circle" />WhatsApp</a>
                </div>
            </section>

            {{-- Occasions: a tile with an icon for each --}}
            <section class="mt-16" aria-labelledby="occasions">
                <p class="eyebrow text-turmeric">{{ __('From first rice to reunions') }}</p>
                <h2 id="occasions" class="mt-2 font-display leading-tight text-[clamp(1.7rem,3.6vw,2.2rem)]">{{ __('Occasions we serve at') }}</h2>
                @if ($occasionNames)
                <ul class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    @foreach ($occasionNames as $i => $name)
                        @php
                            [$icon, $tone] = $occasionLook($englishOccasions[$i] ?? $name);
                        @endphp
                        <li class="group min-w-0 flex items-center gap-3 rounded-2xl bg-card border border-rule p-3.5 sm:p-4 transition hover:border-turmeric/40 hover:shadow-[0_10px_24px_-18px_rgba(120,80,0,.5)]">
                            <span class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl {{ $tone }} grid place-items-center shrink-0 transition group-hover:scale-105"><x-icon :name="$icon" class="w-5 h-5 sm:w-6 sm:h-6" /></span>
                            <span class="min-w-0 font-semibold text-[15px] leading-snug [overflow-wrap:anywhere]">{{ $name }}</span>
                        </li>
                    @endforeach
                </ul>
                @endif
                @if (trim($content->text('catering', 'food_note')) !== '')
                <p class="mt-4 flex gap-3 items-start rounded-2xl bg-tulsi-wash/60 px-5 py-4 text-[15px] text-ink-2"><x-icon name="leaf" class="w-5 h-5 text-tulsi shrink-0 mt-0.5" /><span>{{ $content->text('catering', 'food_note') }}</span></p>
                @endif
            </section>

            {{-- What families say (published reviews, console → Reviews) --}}
            <x-reviews kind="catering" class="mt-16" />

            {{-- Enquiry with a live estimate --}}
            <div id="quote" class="mt-16 grid lg:grid-cols-12 gap-10 scroll-mt-24">
                <div class="lg:col-span-5">
                    <h2 class="font-display leading-tight text-[clamp(1.9rem,4vw,2.6rem)]">{{ __('Ask for a quote') }}</h2>
                    <p class="mt-3 text-[17px] text-ink-2 max-w-[44ch]">{{ $content->text('catering', 'quote') }}</p>
                    <div class="mt-6 paper-card p-5" aria-live="polite">
                        <p class="text-sm text-ink-3">{{ __('Estimate') }}</p>
                        <p class="font-display text-4xl text-turmeric mt-1 num">₹<span x-text="inr(total)"></span></p>
                        <p class="text-sm text-ink-3 mt-1 num"><span x-text="inr(parseInt(guests) || 0)"></span> × ₹<span x-text="inr(rate)"></span> · <span x-text="inr(servers)"></span> {{ __('servers') }} · {{ __('advance') }} ₹<span x-text="inr(Math.round(total * 0.2))"></span>
                            <span x-show="pick && (parseInt(guests) || 0) < pick.min" x-cloak class="block mt-1 text-sindoor-deep font-semibold">{{ __('This package needs more guests.') }}</span></p>
                    </div>
                    <p class="mt-3 text-[13px] text-ink-3">{{ __('The final bill is on the actual guest count.') }}</p>
                </div>
                <form method="POST" action="{{ route('forms.catering') }}" class="lg:col-span-7 grid sm:grid-cols-2 gap-4 content-start">
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
                    <label class="sm:col-span-2 flex items-center gap-2.5 text-[15px]"><input type="checkbox" name="is_member" value="1" x-model="member" class="w-5 h-5 accent-[#9a6400]"> {{ __('I am a Gede Maitree Club member') }}</label>
                    <x-field class="sm:col-span-2" name="venue" :label="__('Venue and anything else')" textarea rows="3" :placeholder="__('e.g. on the roof, from 7 pm, 20 vegetarian guests')" />
                    <div class="sm:col-span-2"><button type="submit" class="btn bg-turmeric text-white hover:bg-[#7a4f00] h-12 px-6">{{ __('Send enquiry') }}</button></div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.site>
