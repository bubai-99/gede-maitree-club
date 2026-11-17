@php
    $content = app(\App\Support\PageContent::class);
    $club = $site->club();
    $topics = \App\Support\FormOptions::CONTACT_TOPICS;
    // Who each enquiry goes to, for the hint under the topic picker
    $goesTo = collect($topics)->map(function (array $topic) use ($committee): string {
        $person = $committee->get($topic['role']);

        return $person ? __('Goes to :name, :role.', ['name' => $person->tr('name'), 'role' => $person->tr('role')]) : '';
    });
    $phoneHref = 'tel:'.preg_replace('/\s+/', '', $club['phone']);
@endphp
<x-layouts.site seo="contact" :title="$content->text('contact', 'title')">
    <x-page-banner page="contact" :crumb="__('Contact')" />

    <div class="mx-auto max-w-[1160px] px-5 pt-12 pb-20">
        <div class="grid lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-7">
                <figure class="frame bg-paper-2">
                    @include('site.partials.para-map')
                    <figcaption class="border-t border-rule bg-paper px-4 py-3 flex flex-wrap items-center gap-3 text-[14px]">
                        <span class="text-ink-2 flex-1 min-w-[200px]">{{ __('Sketch, not to scale. A real map goes here once the club confirms the location.') }}</span>
                        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($club['address']) }}" target="_blank" rel="noopener" class="btn btn-ink btn-sm"><x-icon name="navigation" /><span>{{ __('Open in Maps') }}</span></a>
                    </figcaption>
                </figure>
                <dl class="mt-8 grid sm:grid-cols-2 gap-6 text-[15px]">
                    <div class="flex gap-3"><x-icon name="map-pin" class="w-5 h-5 text-sindoor shrink-0 mt-0.5" /><div><dt class="font-semibold">{{ __('Address') }}</dt><dd class="text-ink-2 mt-0.5">{{ local_text($club['address'], $club['address_bn']) }}</dd></div></div>
                    <div class="flex gap-3"><x-icon name="navigation" class="w-5 h-5 text-sindoor shrink-0 mt-0.5" /><div><dt class="font-semibold">{{ __('Landmark') }}</dt><dd class="text-ink-2 mt-0.5">{{ __('North corner of the para ground, two minutes from the School Road crossing.') }}</dd></div></div>
                    <div class="flex gap-3"><x-icon name="clock" class="w-5 h-5 text-sindoor shrink-0 mt-0.5" /><div><dt class="font-semibold">{{ __('Club room open') }}</dt><dd class="text-ink-2 mt-0.5">{{ local_text($club['hours'], $club['hours_bn']) }}</dd></div></div>
                    <div class="flex gap-3"><x-icon name="sprout" class="w-5 h-5 text-tulsi shrink-0 mt-0.5" /><div><dt class="font-semibold">{{ __('Nursery open') }}</dt><dd class="text-ink-2 mt-0.5">{{ __('7 – 10 am and 5 – 8 pm') }}</dd></div></div>
                </dl>
                <div class="mt-8 flex flex-wrap gap-2.5">
                    <a href="{{ $phoneHref }}" class="btn btn-line h-12 px-5"><x-icon name="phone" /><span class="num">{{ $club['phone'] }}</span></a>
                    <a href="https://wa.me/{{ $club['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-line h-12 px-5"><x-icon name="message-circle" /><span>WhatsApp</span></a>
                </div>
            </div>
            <form method="POST" action="{{ route('forms.contact') }}" class="lg:col-span-5 paper-card p-5 sm:p-7 grid gap-4"
                x-data="{ topic: {{ Js::from(old('topic', request('topic', 'membership'))) }}, who: {{ Js::from($goesTo) }} }">
                @csrf
                @include('site.partials.honeypot', ['form' => 'contact'])
                <div>
                    <h2 class="font-display text-[1.8rem] leading-tight">{{ __('Write to us') }}</h2>
                    <p class="mt-1 text-[15px] text-ink-2">{{ __('We usually reply within a day, by phone or WhatsApp.') }}</p>
                </div>
                <x-field name="name" :label="__('Your name')" autocomplete="name" required />
                <x-field name="phone" type="tel" :label="__('Mobile')" autocomplete="tel" inputmode="tel" placeholder="98300 00000" required />
                <div>
                    <label for="f-topic" class="label">{{ __('About') }}</label>
                    <select id="f-topic" name="topic" class="field" x-model="topic" required>
                        @foreach ($topics as $value => $topic)
                            <option value="{{ $value }}" @selected(old('topic', request('topic')) === $value)>{{ __($topic['label']) }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="topic" />
                    <p class="mt-2 text-[13px] text-ink-3" aria-live="polite" x-text="who[topic] || ''"></p>
                </div>
                <x-field name="message" :label="__('Message')" textarea rows="4" required />
                <fieldset>
                    <legend class="label">{{ __('How should we reply?') }}</legend>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 text-[15px]">
                        @foreach (\App\Support\FormOptions::REPLY_BY as $value => $option)
                            <label class="flex items-center gap-2"><input type="radio" name="reply_by" value="{{ $value }}" class="accent-[#d21f1b]" @checked(old('reply_by', 'whatsapp') === $value)><span>{{ __($option['label']) }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                <div><button type="submit" class="btn btn-primary h-12 px-6"><span>{{ __('Send') }}</span></button></div>
            </form>
        </div>

        @if ($committee->isNotEmpty())
            <div class="mt-20">
                <h2 class="font-display leading-tight text-[2rem]">{{ __('Who to ask') }}</h2>
                <ul class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 border-t border-ink/80">
                    @foreach ($committee as $person)
                        @php $asks = collect($topics)->where('role', $person->role)->map(fn ($t) => __($t['label']))->unique()->implode(', '); @endphp
                        <li class="flex gap-4 py-5 border-b border-rule">
                            <span class="w-11 h-11 rounded-full bg-sindoor-wash text-sindoor-deep grid place-items-center font-display text-lg shrink-0" aria-hidden="true">{{ $person->initials() }}</span>
                            <span class="min-w-0"><span class="block font-semibold">{{ $person->tr('name') }} <span class="font-normal text-ink-3">· {{ $person->tr('role') }}</span></span>@if ($asks)<span class="block text-[15px] text-ink-2 mt-0.5">{{ $asks }}</span>@endif</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- FAQ --}}
    <div class="mx-auto max-w-[1160px] px-5 pb-20 grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
        <div class="lg:col-span-4">
            <h2 class="font-display text-[clamp(1.9rem,4vw,2.6rem)] leading-tight">{{ __('Common questions') }}</h2>
            <p class="mt-4 text-[17px] text-ink-2">{{ __('Anything else? Call the secretary, or drop in at the club any evening.') }}</p>
            <a href="{{ $phoneHref }}" class="mt-6 btn btn-line"><x-icon name="phone" /><span class="num">{{ $club['phone'] }}</span></a>
        </div>
        <div class="lg:col-span-8 border-t border-rule">
            @include('site.partials.faq')
        </div>
    </div>
</x-layouts.site>
