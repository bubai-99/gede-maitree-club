@php
    $content = app(\App\Support\PageContent::class);
    $club = $site->club();
    $picked = (array) old('skills', request()->filled('skill') ? [request('skill')] : []);
    $roles = [
        ['hand-coins', 'chanda', __('Lane chanda volunteer'), __('Sept to Oct, a few evenings'), __('Visit the houses in your own lane before Durga Puja and give app receipts for chanda.')],
        ['utensils', 'serving', __('Maitree Serving team'), __('Weddings and occasions, booked ahead'), __('Set up, serve and clear at the para’s family occasions. You choose which dates you can do.')],
        ['sprout', null, __('Nursery helper'), __('Weekend mornings'), __('Water the plants, help buyers choose and pack orders at the club gate.')],
        ['speaker', 'sound', __('Sound & lights'), __('Pujas and sports day'), __('Run the mic, the speakers and the pandal lights. Someone will show you the panel.')],
    ];
@endphp
<x-layouts.site seo="volunteer" :title="$content->text('volunteer', 'title')">
    <x-page-banner page="volunteer" :crumb="__('Get involved')">
        <div class="mt-6 flex flex-wrap gap-2 text-[14px]">
            @foreach ([__('No experience needed'), __('Members and non-members'), __('On your own time')] as $point)
                <span class="chip h-8 px-3 bg-white/15 text-white"><x-icon name="check" /><span>{{ $point }}</span></span>
            @endforeach
        </div>
        <div class="mt-7"><a href="#vol-form" class="btn btn-primary h-12 px-6 text-base"><span>{{ __('Sign up') }}</span><x-icon name="arrow-down" /></a></div>
    </x-page-banner>

    <div class="mx-auto max-w-[1160px] px-5 pt-12 pb-24">
        {{-- Open now: the coming events. Duty lists are agreed by phone, so none are listed here yet. --}}
        @if ($events->isNotEmpty())
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div><p class="gz-kicker">{{ __('Open now') }}</p><h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('Where the next events need hands') }}</h2></div>
            </div>
            <div class="mt-8 grid lg:grid-cols-2 gap-5 items-start">
                @foreach ($events as $event)
                    @php $d = (int) now()->startOfDay()->diffInDays($event->starts_at->copy()->startOfDay()); @endphp
                    <article class="card p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <span class="w-14 shrink-0 rounded-xl bg-sindoor-wash text-sindoor-deep text-center py-2"><span class="block font-display text-[1.6rem] leading-none num">{{ local_date($event->starts_at, 'd') }}</span><span class="block text-[12px] font-semibold uppercase mt-1">{{ local_date($event->starts_at, 'M') }}</span></span>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-[1.45rem] leading-tight"><a href="{{ route('events.show', $event) }}" class="hover:text-sindoor">{{ $event->tr('title') }}</a></h3>
                                <p class="text-[14px] text-ink-3 mt-1">{{ $event->tr('place') }} · <span class="num">{{ $d === 0 ? __('today') : __('in :count days', ['count' => digits($d)]) }}</span></p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-rule flex flex-wrap items-center gap-x-4 gap-y-2">
                            <p class="flex-1 basis-[14rem] text-[14.5px] text-ink-2">{{ __('The secretary shares the duties for this event by phone. Sign up and say what you would like to do.') }}</p>
                            <a href="#vol-form" class="btn btn-line btn-sm shrink-0">{{ __('I’ll help') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        {{-- All year round --}}
        <div class="{{ $events->isNotEmpty() ? 'mt-20' : '' }}"><p class="gz-kicker">{{ __('All year round') }}</p><h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('Regular roles') }}</h2>
            <p class="mt-3 text-[17px] text-ink-2 max-w-[58ch]">{{ __('Not tied to one day. Give as much time as suits you.') }}</p></div>
        <ul class="mt-8 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($roles as [$icon, $skill, $title, $when, $what])
                <li class="rounded-2xl bg-paper-2 p-5 flex flex-col">
                    <x-icon :name="$icon" class="w-6 h-6 text-sindoor" />
                    <h3 class="mt-3 font-semibold text-[17px] leading-snug">{{ $title }}</h3>
                    <p class="mt-1 text-[13.5px] font-semibold text-ink-3">{{ $when }}</p>
                    <p class="mt-2 text-[15px] text-ink-2 flex-1">{{ $what }}</p>
                    <a href="{{ route('volunteer', array_filter(['skill' => $skill])) }}#vol-form" class="mt-4 self-start text-[14.5px] font-semibold text-sindoor hover:underline inline-flex items-center gap-1"><span>{{ __('Sign up for this') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
                </li>
            @endforeach
        </ul>

        {{-- How it works + sign-up --}}
        <div class="mt-20 grid lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-5 lg:sticky lg:top-24">
                <p class="gz-kicker">{{ __('How it works') }}</p>
                <h2 class="mt-2 gz-headline text-[clamp(2rem,4.5vw,3rem)]">{{ __('Three steps') }}</h2>
                <ol class="mt-6 space-y-5">
                    @foreach ([[__('Sign up'), __('Tell us what you like doing and when you’re free.')], [__('We call you'), __('The secretary calls or WhatsApps you within two days to settle a duty.')], [__('Turn up'), __('You get a reminder the day before, and anything you need to know is shown on the spot.')]] as $i => [$title, $text])
                        <li class="flex gap-4"><span class="w-9 h-9 rounded-full bg-ink text-white grid place-items-center font-semibold shrink-0 num">{{ digits($i + 1) }}</span><div><p class="font-semibold">{{ $title }}</p><p class="text-[15px] text-ink-2">{{ $text }}</p></div></li>
                    @endforeach
                </ol>
                <p class="mt-8 text-[15px] text-ink-2"><span>{{ __('Questions?') }}</span> <a href="https://wa.me/{{ $club['whatsapp'] }}?text={{ rawurlencode(__('I would like to volunteer')) }}" target="_blank" rel="noopener" class="font-semibold text-sindoor hover:underline">{{ __('Message us on WhatsApp') }}</a></p>
            </div>
            <form id="vol-form" method="POST" action="{{ route('forms.volunteer') }}" class="lg:col-span-7 paper-card p-5 sm:p-7 grid sm:grid-cols-2 gap-4 scroll-mt-24">
                @csrf
                @include('site.partials.honeypot', ['form' => 'volunteer'])
                <div class="sm:col-span-2">
                    <h2 class="font-display text-[1.8rem] leading-tight">{{ __('Sign up to volunteer') }}</h2>
                    <p class="mt-1 text-[15px] text-ink-2">{{ __('A one-minute form. You can take your name off any time.') }}</p>
                </div>
                <x-field name="name" :label="__('Your name')" autocomplete="name" required />
                <x-field name="phone" type="tel" :label="__('Mobile')" autocomplete="tel" inputmode="tel" placeholder="98300 00000" required />
                <div class="sm:col-span-2">
                    <label for="f-vol-area" class="label">{{ __('Where you live') }}</label>
                    <select id="f-vol-area" name="area" class="field" required>
                        <option value="">{{ __('Choose') }}</option>
                        @foreach ($site->areas() as $value => $areaName)
                            <option value="{{ $value }}" @selected(old('area') === $value)>{{ $areaName }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="area" />
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('What you’d like to do (pick any)') }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Support\FormOptions::VOLUNTEER_SKILLS as $value => $skill)
                            <label class="cursor-pointer"><input type="checkbox" name="skills[]" value="{{ $value }}" class="peer sr-only" @checked(in_array($value, $picked, true))><span class="inline-flex items-center h-9 px-3.5 rounded-full border border-rule text-[14.5px] peer-checked:bg-ink peer-checked:text-white peer-checked:border-ink peer-focus-visible:outline-2 peer-focus-visible:outline-sindoor">{{ __($skill['label']) }}</span></label>
                        @endforeach
                    </div>
                    <x-field-error name="skills" />
                </fieldset>
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('When you’re usually free') }}</legend>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 text-[15px]">
                        @foreach (\App\Support\FormOptions::VOLUNTEER_TIMES as $value => $time)
                            <label class="flex items-center gap-2"><input type="checkbox" name="times[]" value="{{ $value }}" class="w-4 h-4 accent-[#d21f1b]" @checked(in_array($value, (array) old('times', ['weekends']), true))><span>{{ __($time['label']) }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="sm:col-span-2"><button type="submit" class="btn btn-primary h-12 px-6"><span>{{ __('Sign up') }}</span></button></div>
            </form>
        </div>
    </div>
</x-layouts.site>
