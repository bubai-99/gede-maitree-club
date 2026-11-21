@php
    use App\Enums\PanjikaKind;

    $tithis = ['Pratipada', 'Dwitiya', 'Tritiya', 'Chaturthi', 'Panchami', 'Shashthi', 'Saptami', 'Ashtami', 'Navami', 'Dashami', 'Ekadashi', 'Dwadashi', 'Trayodashi', 'Chaturdashi'];
    $tithiName = fn (int $i): string => $i === 14 ? __('Purnima') : ($i === 29 ? __('Amavasya') : __($tithis[$i % 15]));
    $chip = ['puja' => 'bg-sindoor text-white', 'parbon' => 'bg-marigold text-ink', 'wedding' => 'bg-turmeric-wash text-ink border border-turmeric/40'];
@endphp
<x-layouts.admin :title="__('Panjika')">
    <div data-panjika-console>
        <div class="flex flex-wrap items-end gap-3">
            <div class="mr-auto">
                <h1 class="font-display text-[2rem] leading-tight">{{ __('Panjika') }}</h1>
                <p class="text-ink-2">{{ __('Puja days, parbon and wedding days from the club’s panjika. They show on the website’s Panjika page.') }}</p>
            </div>
            <a href="{{ route('panjika') }}" target="_blank" rel="noopener" class="btn btn-line btn-sm"><x-icon name="external-link" />{{ __('See on the website') }}</a>
        </div>

        <div class="mt-6 grid lg:grid-cols-3 gap-4">
            <div class="paper-card p-5 lg:col-span-2" data-pj-today>
                <p class="text-[12.5px] font-semibold uppercase tracking-wide text-ink-3">{{ __('Today, calculated') }} · {{ __('Gede, Nadia') }}</p>
                <p class="mt-2 font-display text-[1.7rem] leading-tight" data-pj-today-date>…</p>
                <p class="mt-1 text-[15px] text-ink-2" data-pj-today-line></p>
                <p class="mt-3 text-[13.5px] text-ink-3">{{ __('Tithi, nakshatra, sunrise and the Bengali date are worked out automatically. You only enter the days below.') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.panjika.source') }}" class="paper-card p-5 space-y-3">
                @csrf @method('PUT')
                <fieldset @disabled(! $canEdit) class="space-y-3">
                    <x-field name="source" :label="__('Panjika we follow')" :value="$source['en']" required maxlength="120" />
                    <x-field name="source_bn" :label="__('In Bengali')" :value="$source['bn']" maxlength="120" lang="bn" />
                    <p class="text-[13px] text-ink-3">{{ __('Shown on the website next to the dates.') }}</p>
                    @if ($canEdit)<button class="btn btn-line btn-sm">{{ __('Save') }}</button>@endif
                </fieldset>
            </form>
        </div>

        @if ($canEdit)
            <div class="mt-6 grid lg:grid-cols-2 gap-4">
                <details class="paper-card" @if ($errors->any() && old('kind') !== 'wedding') open @endif>
                    <summary class="cursor-pointer p-4 font-semibold flex items-center gap-2"><x-icon name="plus" class="w-4 h-4" />{{ __('Add puja or parbon') }}</summary>
                    <form method="POST" action="{{ route('admin.panjika.store') }}" class="px-4 pb-4 space-y-3" data-pj-form>
                        @csrf
                        <fieldset>
                            <legend class="label">{{ __('Kind') }}</legend>
                            <div class="flex gap-5">
                                <label class="flex items-center gap-2"><input type="radio" name="kind" value="puja" class="accent-sindoor" @checked(old('kind', 'puja') === 'puja')> {{ PanjikaKind::Puja->label() }}</label>
                                <label class="flex items-center gap-2"><input type="radio" name="kind" value="parbon" class="accent-sindoor" @checked(old('kind') === 'parbon')> {{ PanjikaKind::Parbon->label() }}</label>
                            </div>
                        </fieldset>
                        <div>
                            <x-field name="date" type="date" :label="__('Date')" :min="now()->toDateString()" required />
                            <p class="mt-1.5 text-[13.5px] text-ink-2" data-pj-hint aria-live="polite"></p>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <x-field name="name" :label="__('Name in English')" placeholder="Jagaddhatri Puja" maxlength="120" />
                            <x-field name="name_bn" :label="__('Name in Bengali')" placeholder="জগদ্ধাত্রী পূজা" maxlength="120" lang="bn" />
                        </div>
                        <div>
                            <label for="f-tithi" class="label">{{ __('Tithi, as in the panjika') }}</label>
                            <select id="f-tithi" name="tithi" class="field">
                                <option value="">{{ __('Not tied to a tithi (sankranti, Poila Boishakh)') }}</option>
                                @for ($i = 0; $i < 30; $i++)
                                    <option value="{{ $i }}" @selected(old('tithi') !== null && (int) old('tithi') === $i)>{{ $tithiName($i) }} · {{ $i < 15 ? __('Shukla') : __('Krishna') }}</option>
                                @endfor
                            </select>
                        </div>
                        @if ($events->isNotEmpty())
                            <div>
                                <label for="f-event_id" class="label">{{ __('Club event on this day (optional)') }}</label>
                                <select id="f-event_id" name="event_id" class="field">
                                    <option value="">—</option>
                                    @foreach ($events as $event)
                                        <option value="{{ $event->id }}" @selected((int) old('event_id') === $event->id)>{{ $event->tr('title') }} · {{ local_date($event->starts_at, 'j M Y') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <button class="btn btn-primary btn-sm">{{ __('Add') }}</button>
                    </form>
                </details>
                <details class="paper-card" @if ($errors->any() && old('kind') === 'wedding') open @endif>
                    <summary class="cursor-pointer p-4 font-semibold flex items-center gap-2"><x-icon name="heart" class="w-4 h-4" />{{ __('Add wedding day') }}</summary>
                    <form method="POST" action="{{ route('admin.panjika.store') }}" class="px-4 pb-4 space-y-3" data-pj-form>
                        @csrf
                        <input type="hidden" name="kind" value="wedding">
                        <div>
                            <x-field name="date" id="f-wdate" type="date" :label="__('Date')" :min="now()->toDateString()" required />
                            <p class="mt-1.5 text-[13.5px] text-ink-2" data-pj-hint aria-live="polite"></p>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <x-field name="lagna" :label="__('Lagna (English)')" placeholder="8:10 pm – 1:40 am" maxlength="80" required />
                            <x-field name="lagna_bn" :label="__('Lagna (Bengali)')" placeholder="রাত ৮:১০ – ১:৪০" maxlength="80" lang="bn" />
                        </div>
                        <button class="btn btn-primary btn-sm">{{ __('Add') }}</button>
                    </form>
                </details>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap items-center gap-2 mb-3">
            <a href="{{ route('admin.panjika.index') }}" @if (! $all) aria-current="page" @endif class="chip h-9 px-3.5 text-sm border {{ $all ? 'border-rule bg-card' : 'bg-ink text-paper border-ink' }}">{{ __('Coming up') }}</a>
            <a href="{{ route('admin.panjika.index', ['show' => 'all']) }}" @if ($all) aria-current="page" @endif class="chip h-9 px-3.5 text-sm border {{ $all ? 'bg-ink text-paper border-ink' : 'border-rule bg-card' }}">{{ __('All') }}</a>
            <span class="ml-auto text-[13.5px] text-ink-3">{{ trans_choice(':count day|:count days', $days->count(), ['count' => digits($days->count())]) }}</span>
        </div>
        <div class="paper-card overflow-x-auto">
            <table class="khata-table">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('What') }}</th><th class="hidden md:table-cell">{{ __('Kind') }}</th><th>{{ __('Tithi at sunrise') }}</th><th><span class="sr-only">{{ __('Remove') }}</span></th></tr></thead>
                <tbody>
                    @forelse ($days as $day)
                        <tr data-pj-row="{{ $day->date->toDateString() }}" data-pj-entered="{{ $day->tithi ?? '' }}">
                            <td class="whitespace-nowrap"><span class="font-semibold">{{ local_date($day->date, 'j M Y') }}</span><span class="block text-[13px] text-ink-3" data-pj-bdate></span></td>
                            <td><span class="font-semibold">{{ $day->tr('name') }}</span>@if ($day->lagna)<span class="block text-[13px] text-ink-2">{{ __('Lagna') }} {{ $day->tr('lagna') }}</span>@endif @if ($day->event)<span class="block text-[13px] text-ink-3">{{ $day->event->tr('title') }}</span>@endif</td>
                            <td class="hidden md:table-cell"><span class="chip h-7 px-2.5 text-[12.5px] {{ $chip[$day->kind->value] }}">{{ $day->kind->label() }}</span></td>
                            <td data-pj-tithi>@if ($day->tithi !== null){{ $tithiName($day->tithi) }}@endif</td>
                            <td class="text-right">
                                @if ($canEdit)
                                    <form method="POST" action="{{ route('admin.panjika.destroy', $day) }}" x-data @submit="if (! confirm({{ Js::from(__('Remove this day?')) }})) $event.preventDefault()">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-ghost btn-sm px-2" aria-label="{{ __('Remove :name, :date', ['name' => $day->tr('name'), 'date' => local_date($day->date, 'j M Y')]) }}"><x-icon name="trash-2" /></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-ink-3 py-8">{{ __('Nothing entered yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @include('partials.panjika-data')
</x-layouts.admin>
