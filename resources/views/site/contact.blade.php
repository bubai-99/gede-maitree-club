@php
    $content = app(\App\Support\PageContent::class);
    $club = $site->club();
@endphp
<x-layouts.site seo="contact" :title="$content->text('contact', 'title')">
    <x-page-banner page="contact" />

    <div class="mx-auto max-w-[1160px] px-5 py-14 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-5 space-y-6">
            <dl class="card p-6 space-y-4">
                <div class="flex gap-3"><x-icon name="map-pin" class="w-5 h-5 mt-0.5 text-sindoor" /><div><dt class="font-semibold">{{ __('Address') }}</dt><dd class="text-ink-2">{{ local_text($club['address'], $club['address_bn']) }}</dd></div></div>
                <div class="flex gap-3"><x-icon name="landmark" class="w-5 h-5 mt-0.5 text-sindoor" /><div><dt class="font-semibold">{{ __('Landmark') }}</dt><dd class="text-ink-2">{{ __('North corner of the para ground, two minutes from the School Road crossing.') }}</dd></div></div>
                <div class="flex gap-3"><x-icon name="clock" class="w-5 h-5 mt-0.5 text-sindoor" /><div><dt class="font-semibold">{{ __('Club room open') }}</dt><dd class="text-ink-2">{{ local_text($club['hours'], $club['hours_bn']) }}</dd></div></div>
                <div class="flex gap-3"><x-icon name="sprout" class="w-5 h-5 mt-0.5 text-tulsi" /><div><dt class="font-semibold">{{ __('Nursery open') }}</dt><dd class="text-ink-2">{{ __('7 – 10 am and 5 – 8 pm') }}</dd></div></div>
            </dl>
            <div class="flex flex-wrap gap-2">
                <a href="tel:{{ preg_replace('/\s+/', '', $club['phone']) }}" class="btn btn-line"><x-icon name="phone" /><span class="num">{{ $club['phone'] }}</span></a>
                <a href="https://wa.me/{{ $club['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-line"><x-icon name="message-circle" /><span>WhatsApp</span></a>
                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($club['address']) }}" target="_blank" rel="noopener" class="btn btn-line"><x-icon name="map" /><span>{{ __('Open in Maps') }}</span></a>
            </div>

            <div>
                <h2 class="font-display text-[1.6rem]">{{ __('Who to ask') }}</h2>
                <ul class="mt-3 border-t border-ink/80">
                    @foreach (\App\Support\FormOptions::CONTACT_TOPICS as $topic)
                        @php($person = $committee->get($topic['role']))
                        <li class="py-3 border-b border-rule flex flex-wrap justify-between gap-2"><span>{{ __($topic['label']) }}</span><span class="text-ink-2">{{ $person ? $person->tr('name').' · '.$person->tr('role') : __($topic['role']) }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="lg:col-span-7">
            <form method="POST" action="{{ route('forms.contact') }}" class="card p-6 grid sm:grid-cols-2 gap-4">
                @csrf
                @include('site.partials.honeypot', ['form' => 'contact'])
                <div class="sm:col-span-2">
                    <h2 class="font-display text-[1.8rem]">{{ __('Write to us') }}</h2>
                    <p class="text-ink-2">{{ __('We usually reply within a day, by phone or WhatsApp.') }}</p>
                </div>
                <x-field name="name" :label="__('Your name')" autocomplete="name" required />
                <x-field name="phone" type="tel" :label="__('Mobile')" autocomplete="tel" inputmode="tel" required />
                <div class="sm:col-span-2">
                    <label for="f-topic" class="label">{{ __('About') }}</label>
                    <select id="f-topic" name="topic" class="field" required>
                        @foreach (\App\Support\FormOptions::CONTACT_TOPICS as $value => $topic)
                            <option value="{{ $value }}" @selected(old('topic', request('topic')) === $value)>{{ __($topic['label']) }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="topic" />
                </div>
                <x-field class="sm:col-span-2" name="message" :label="__('Message')" textarea rows="5" required />
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('How should we reply?') }}</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach (\App\Support\FormOptions::REPLY_BY as $value => $option)
                            <label class="flex items-center gap-2"><input type="radio" name="reply_by" value="{{ $value }}" class="accent-sindoor" @checked(old('reply_by', 'whatsapp') === $value)> {{ __($option['label']) }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="sm:col-span-2"><button type="submit" class="btn btn-primary h-12 px-6">{{ __('Send') }}</button></div>
            </form>

            <div class="mt-12 border-t border-ink/80">
                <h2 class="mt-6 font-display text-[1.8rem]">{{ __('Common questions') }}</h2>
                @include('site.partials.faq')
            </div>
        </div>
    </div>
</x-layouts.site>
