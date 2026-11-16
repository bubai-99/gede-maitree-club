@php($content = app(\App\Support\PageContent::class))
<x-layouts.site seo="volunteer" :title="$content->text('volunteer', 'title')">
    <x-page-banner page="volunteer" :crumb="__('Get involved')">
        <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-[15px] text-white/85">
            <li class="flex items-center gap-2"><x-icon name="check" class="w-4 h-4 text-marigold" />{{ __('No experience needed') }}</li>
            <li class="flex items-center gap-2"><x-icon name="check" class="w-4 h-4 text-marigold" />{{ __('Members and non-members') }}</li>
            <li class="flex items-center gap-2"><x-icon name="check" class="w-4 h-4 text-marigold" />{{ __('On your own time') }}</li>
        </ul>
        <a href="#vol-form" class="mt-6 btn btn-primary h-11">{{ __('Sign up') }}</a>
    </x-page-banner>

    <div class="mx-auto max-w-[1160px] px-5 py-14">
        @if ($events->isNotEmpty())
            <h2 class="gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Where the next events need hands') }}</h2>
            <ul class="mt-6 grid md:grid-cols-3 gap-4">
                @foreach ($events as $event)
                    <li class="card p-5">
                        <p class="text-[13px] text-ink-3 num">{{ local_date($event->starts_at, 'j F') }}</p>
                        <a href="{{ route('events.show', $event) }}" class="mt-1 block font-display text-[1.3rem] hover:text-sindoor">{{ $event->tr('title') }}</a>
                        <p class="mt-1 text-ink-2 text-[14px]">{{ $event->tr('place') }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

        <h2 class="mt-14 gz-headline text-[clamp(1.8rem,4vw,2.6rem)]">{{ __('Regular roles') }}</h2>
        <p class="mt-2 text-ink-2">{{ __('Not tied to one day. Give as much time as suits you.') }}</p>
        <ul class="mt-5 flex flex-wrap gap-2">
            @foreach (\App\Support\FormOptions::VOLUNTEER_SKILLS as $skill)
                <li class="chip h-9 px-4 bg-sindoor-wash text-sindoor-deep">{{ __($skill['label']) }}</li>
            @endforeach
        </ul>

        <div class="mt-14 grid lg:grid-cols-12 gap-10">
            <div class="lg:col-span-5">
                <h2 class="font-display text-[1.8rem]">{{ __('Three steps') }}</h2>
                <ol class="mt-5 space-y-4">
                    @foreach ([[__('Sign up'), __('Tell us what you like doing and when you’re free.')], [__('We call you'), __('The secretary calls or WhatsApps you within two days to settle a duty.')], [__('Turn up'), __('You get a reminder the day before, and anything you need to know is shown on the spot.')]] as $i => [$title, $text])
                        <li class="flex gap-4"><span class="w-9 h-9 rounded-full bg-sindoor text-white grid place-items-center font-semibold num shrink-0">{{ digits($i + 1) }}</span><div><p class="font-semibold">{{ $title }}</p><p class="text-ink-2">{{ $text }}</p></div></li>
                    @endforeach
                </ol>
            </div>
            <form id="vol-form" method="POST" action="{{ route('forms.volunteer') }}" class="lg:col-span-7 card p-6 grid sm:grid-cols-2 gap-4 scroll-mt-24">
                @csrf
                @include('site.partials.honeypot', ['form' => 'volunteer'])
                <div class="sm:col-span-2">
                    <h2 class="font-display text-[1.8rem]">{{ __('Sign up to volunteer') }}</h2>
                    <p class="text-ink-2">{{ __('A one-minute form. You can take your name off any time.') }}</p>
                </div>
                <x-field name="name" :label="__('Your name')" autocomplete="name" required />
                <x-field name="phone" type="tel" :label="__('Mobile')" autocomplete="tel" inputmode="tel" required />
                <div class="sm:col-span-2">
                    <label for="f-vol-area" class="label">{{ __('Where you live') }}</label>
                    <select id="f-vol-area" name="area" class="field" required>
                        @foreach (\App\Support\FormOptions::AREAS as $value => $option)
                            <option value="{{ $value }}" @selected(old('area') === $value)>{{ __($option['label']) }}</option>
                        @endforeach
                    </select>
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('What you’d like to do (pick any)') }}</legend>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (\App\Support\FormOptions::VOLUNTEER_SKILLS as $value => $skill)
                            <label class="flex items-center gap-2"><input type="checkbox" name="skills[]" value="{{ $value }}" class="w-4 h-4 accent-sindoor" @checked(in_array($value, (array) old('skills', []), true))> {{ __($skill['label']) }}</label>
                        @endforeach
                    </div>
                    <x-field-error name="skills" />
                </fieldset>
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('When you’re usually free') }}</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach (\App\Support\FormOptions::VOLUNTEER_TIMES as $value => $time)
                            <label class="flex items-center gap-2"><input type="checkbox" name="times[]" value="{{ $value }}" class="w-4 h-4 accent-sindoor" @checked(in_array($value, (array) old('times', ['weekends']), true))> {{ __($time['label']) }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="sm:col-span-2"><button type="submit" class="btn btn-primary h-12 px-6">{{ __('Sign up') }}</button></div>
            </form>
        </div>
    </div>
</x-layouts.site>
