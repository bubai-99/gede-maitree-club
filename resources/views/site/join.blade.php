@php
    $content = app(\App\Support\PageContent::class);
    $club = $site->club();
    $oldMembers = old('members', []);
    // After a failed submit, open the step that holds the first error
    $errorStep = $errors->hasAny(['name', 'dob', 'blood_group', 'phone', 'email']) ? 1 : ($errors->hasAny(['address', 'area', 'aadhaar_last4', 'members', 'members.*']) ? 2 : ($errors->any() ? 3 : 1));
    $steps = [1 => __('About you'), 2 => __('Address & family'), 3 => __('Plan & sign')];
@endphp
<x-layouts.site seo="join" :title="$content->text('join', 'title')">
    <x-page-banner page="join" />

    <div class="mx-auto max-w-[1160px] px-5 pt-10 pb-20 grid lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        <div class="lg:col-span-5 lg:sticky lg:top-24">
            <div class="flex flex-wrap gap-2 text-[14px]">
                <span class="chip h-8 px-3 bg-paper-2 text-ink"><span class="num">{{ rupees((int) $club['monthly_fee']) }}</span>&nbsp;<span>/ {{ __('month') }}</span></span>
                <span class="chip h-8 px-3 bg-paper-2 text-ink"><span class="num">{{ rupees((int) $club['monthly_fee'] * 11) }}</span>&nbsp;<span>/ {{ __('year') }}</span></span>
                <span class="chip h-8 px-3 bg-paper-2 text-ink"><span>{{ __('Admission') }}</span>&nbsp;<span class="num">{{ rupees((int) $club['admission_fee']) }}</span></span>
            </div>
            <details class="mt-6 rounded-2xl bg-sindoor-wash/60 p-5 group" open>
                <summary class="font-semibold cursor-pointer list-none flex items-center justify-between"><span>{{ __('What members get') }}</span><x-icon name="chevron-down" class="w-5 h-5 transition-transform group-open:rotate-180 lg:hidden" /></summary>
                <ul class="mt-4 space-y-3.5 text-[15px]">
                    @foreach ([
                        ['ticket', __('Passes for bhog, the picnic and sports day'), __('For everyone in your household, in the app.')],
                        ['badge-percent', __('Member prices'), __('On Maitree Serving and plants from the nursery.')],
                        ['vote', __('A vote at the AGM'), __('Elect the committee and pass the yearly accounts.')],
                        ['users', __('One membership, whole household'), __('Add everyone at home on the same form.')],
                        ['smartphone', __('Dues, receipts and notices in the app'), __('Pay by UPI and get the receipt straight away.')],
                        ['id-card', __('A digital ID card'), __('Scanned at the gate to let you in.')],
                    ] as [$icon, $title, $text])
                        <li class="flex gap-3"><span class="w-9 h-9 rounded-full bg-white grid place-items-center shrink-0"><x-icon :name="$icon" class="w-[18px] h-[18px] text-sindoor" /></span><span><span class="block font-semibold text-ink">{{ $title }}</span><span class="block text-ink-2">{{ $text }}</span></span></li>
                    @endforeach
                </ul>
                <p class="mt-4 pt-4 border-t border-sindoor/15 text-[14px] text-ink-2"><span>{{ __('Want to help without joining?') }}</span> <a href="{{ route('volunteer') }}" class="font-semibold text-sindoor hover:underline">{{ __('Volunteer') }}</a></p>
            </details>
            <div class="mt-6 text-[15px] text-ink-2 space-y-1">
                <p><span>{{ __('Already a member?') }}</span> <a href="{{ route('member.login') }}" class="font-semibold text-sindoor hover:underline">{{ __('Sign in to the app') }}</a></p>
                <p><span>{{ __('Questions?') }}</span> <a href="tel:{{ preg_replace('/\s+/', '', $club['phone']) }}" class="font-semibold text-sindoor hover:underline num">{{ $club['phone'] }}</a></p>
            </div>
        </div>

        <form method="POST" action="{{ route('forms.join') }}" class="lg:col-span-7 card p-5 sm:p-8"
            x-data="{ step: {{ $errorStep }}, members: {{ Js::from(array_values(is_array($oldMembers) ? $oldMembers : [])) }},
                next() { const box = $el.querySelector('[data-step=\'' + this.step + '\']'); if (window.checkFields && ! window.checkFields(box)) return; this.step++; $el.scrollIntoView({ block: 'start', behavior: 'smooth' }) } }"
            @field-invalid="const box = $event.target.closest('[data-step]'); if (box) step = Number(box.dataset.step)">
            @csrf
            @include('site.partials.honeypot', ['form' => 'join'])
            {{-- Progress --}}
            <div>
                <div class="flex items-baseline justify-between gap-3">
                    <p class="text-[13px] font-semibold text-ink-2">{{ __('Step') }} <span class="num" x-text="step">{{ digits($errorStep) }}</span> {{ __('of 3') }}</p>
                    <p class="text-[13px] text-ink-3 num">{{ __('Reg. No.') }} {{ $club['reg_no'] }}</p>
                </div>
                <ol class="mt-3 grid grid-cols-3 gap-2">
                    @foreach ($steps as $n => $label)
                        <li class="text-[13px] font-semibold" :class="step >= {{ $n }} ? '' : 'text-ink-3'"><span class="block h-1.5 rounded-full" :class="step >= {{ $n }} ? 'bg-sindoor' : 'bg-paper-3'"></span><span class="mt-2 block">{{ $label }}</span></li>
                    @endforeach
                </ol>
            </div>

            {{-- Step 1: About you --}}
            <div data-step="1" class="mt-7" x-show="step === 1">
                <h2 class="font-display text-[1.6rem] leading-tight">{{ __('About you') }}</h2>
                <p class="text-[14px] text-ink-2 mt-1">{{ __('Your photo for the ID card is taken at the club room when the committee approves.') }}</p>
                <div class="mt-6 grid sm:grid-cols-2 gap-4">
                    <x-field class="sm:col-span-2" name="name" :label="__('Full name')" autocomplete="name" required />
                    <x-field name="dob" type="date" :label="__('Date of birth')" :max="now()->subDay()->toDateString()" required />
                    <div>
                        <label for="f-blood" class="label">{{ __('Blood group') }}</label>
                        <select id="f-blood" name="blood_group" class="field">
                            <option value="">{{ __('Choose') }}</option>
                            @foreach (\App\Models\Member::BLOOD_GROUPS as $group)
                                <option value="{{ $group }}" @selected(old('blood_group') === $group)>{{ $group }}</option>
                            @endforeach
                        </select>
                        <x-field-error name="blood_group" />
                    </div>
                    <x-field name="phone" type="tel" :label="__('Mobile (for OTP login)')" autocomplete="tel" inputmode="tel" placeholder="98300 00000" required />
                    <x-field name="email" type="email" :label="__('Email (optional)')" autocomplete="email" placeholder="name@example.com" />
                </div>
            </div>

            {{-- Step 2: Address and family --}}
            <div data-step="2" class="mt-7" x-show="step === 2" x-cloak>
                <h2 class="font-display text-[1.6rem] leading-tight">{{ __('Address & family') }}</h2>
                <div class="mt-6 grid sm:grid-cols-2 gap-4">
                    <x-field class="sm:col-span-2" name="address" :label="__('Address')" textarea rows="2" autocomplete="street-address" required />
                    <div>
                        <label for="f-join-area" class="label">{{ __('Lane / area') }}</label>
                        <select id="f-join-area" name="area" class="field" required>
                            @foreach ($site->areas() as $value => $areaName)
                                <option value="{{ $value }}" @selected(old('area') === $value)>{{ $areaName }}</option>
                            @endforeach
                        </select>
                        <x-field-error name="area" />
                    </div>
                    <x-field name="aadhaar_last4" :label="__('Aadhaar, last 4 digits')" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" :hint="__('We don’t store the full number. Show the card once at the club room.')" />
                    <div class="sm:col-span-2">
                        <p class="font-semibold">{{ __('Family joining with you (optional)') }}</p>
                        <p class="text-[14px] text-ink-2">{{ __('People at the same address can join on your form. Each gets their own ID card.') }}</p>
                        <template x-for="(member, i) in members" :key="i">
                            <div class="mt-3 grid grid-cols-[1fr_110px_40px] sm:grid-cols-[1fr_120px_140px_40px] gap-2 items-end">
                                <label class="block"><span class="sr-only">{{ __('Name') }}</span><input class="field" :name="'members[' + i + '][name]'" x-model="member.name" maxlength="120" placeholder="{{ __('Name') }}"></label>
                                <label class="block"><span class="sr-only">{{ __('Relation') }}</span><input class="field" :name="'members[' + i + '][relation]'" x-model="member.relation" maxlength="50" placeholder="{{ __('Relation') }}"></label>
                                <label class="hidden sm:block"><span class="sr-only">{{ __('Date of birth') }}</span><input type="date" class="field" :name="'members[' + i + '][dob]'" x-model="member.dob"></label>
                                <button type="button" class="btn btn-ghost px-0 w-10" @click="members.splice(i, 1)" :aria-label="{{ Js::from(__('Remove')) }}"><x-icon name="x" /></button>
                            </div>
                        </template>
                        <button type="button" class="btn btn-line btn-sm mt-3" @click="members.push({ name: '', relation: '', dob: '' })" x-show="members.length < 8"><x-icon name="plus" /><span>{{ __('Add a family member') }}</span></button>
                        <x-field-error name="members" />
                    </div>
                </div>
            </div>

            {{-- Step 3: Plan and sign --}}
            <div data-step="3" class="mt-7" x-show="step === 3" x-cloak>
                <h2 class="font-display text-[1.6rem] leading-tight">{{ __('Plan & sign') }}</h2>
                <p class="mt-4 font-semibold">{{ __('How would you like to pay the subscription?') }}</p>
                <div class="mt-3 grid sm:grid-cols-2 gap-3">
                    <label class="rounded-xl border border-rule bg-card p-4 flex gap-3 cursor-pointer has-[:checked]:border-sindoor has-[:checked]:ring-1 has-[:checked]:ring-sindoor"><input type="radio" name="plan" value="monthly" class="accent-[#d21f1b] mt-1" @checked(old('plan', 'monthly') === 'monthly')><span><span class="block font-semibold num">{{ rupees((int) $club['monthly_fee']) }} / {{ __('month') }}</span><span class="text-[14px] text-ink-2">{{ __('Pay in cash or UPI') }}</span></span></label>
                    <label class="rounded-xl border border-rule bg-card p-4 flex gap-3 cursor-pointer has-[:checked]:border-sindoor has-[:checked]:ring-1 has-[:checked]:ring-sindoor"><input type="radio" name="plan" value="yearly" class="accent-[#d21f1b] mt-1" @checked(old('plan') === 'yearly')><span><span class="block font-semibold num">{{ rupees((int) $club['monthly_fee'] * 11) }} / {{ __('year') }}</span><span class="text-[14px] text-ink-2">{{ __('One month free') }}</span></span></label>
                </div>
                <x-field-error name="plan" />
                <div class="mt-6 rounded-xl bg-paper-2 p-4">
                    <label class="flex gap-3 items-start">
                        <input type="checkbox" name="agree" value="1" class="w-5 h-5 mt-0.5 accent-[#d21f1b]" @checked(old('agree')) required @error('agree') aria-invalid="true" @enderror>
                        <span class="text-[15px]">{{ __('I want to join Gede Maitree Club of my own free will. I will abide by all the rules and collective decisions of the club. Any anti-social activity on my part will end my membership.') }}</span>
                    </label>
                    <p class="mt-3 text-[13px] text-ink-3">{{ __('Ticking this box is your signature. The club secretary signs when the committee approves.') }}</p>
                </div>
                <x-field-error name="agree" />
            </div>

            {{-- Navigation --}}
            <div class="mt-8 pt-5 border-t border-rule flex items-center gap-3">
                <button type="button" class="btn btn-ghost h-12 px-4 -ml-2" x-show="step > 1" x-cloak @click="step--"><x-icon name="arrow-left" /><span>{{ __('Back') }}</span></button>
                <button type="button" class="btn btn-primary h-12 px-6 ml-auto sm:ml-0" x-show="step < 3" @click="next()"><span>{{ __('Continue') }}</span><x-icon name="arrow-right" /></button>
                <button type="submit" class="btn btn-primary h-12 px-6 ml-auto sm:ml-0" x-show="step === 3" x-cloak><span>{{ __('Send application') }}</span></button>
            </div>
        </form>
    </div>
</x-layouts.site>
