@php
    $content = app(\App\Support\PageContent::class);
    $club = $site->club();
    $oldMembers = old('members', []);
@endphp
<x-layouts.site seo="join" :title="$content->text('join', 'title')">
    <x-page-banner page="join">
        <ul class="mt-6 flex flex-wrap gap-2 text-[15px]">
            <li class="chip h-9 px-4 bg-white/15 text-white border border-white/25 num">{{ rupees((int) $club['monthly_fee']) }} / {{ __('month') }}</li>
            <li class="chip h-9 px-4 bg-white/15 text-white border border-white/25 num">{{ rupees((int) $club['monthly_fee'] * 11) }} / {{ __('year') }}</li>
            <li class="chip h-9 px-4 bg-white/15 text-white border border-white/25 num">{{ __('Admission') }} {{ rupees((int) $club['admission_fee']) }}</li>
        </ul>
    </x-page-banner>

    <div class="mx-auto max-w-[1160px] px-5 py-14 grid lg:grid-cols-12 gap-10">
        <aside class="lg:col-span-4 space-y-5">
            <details class="card p-6" open>
                <summary class="font-display text-[1.4rem] cursor-pointer">{{ __('What members get') }}</summary>
                <ul class="mt-4 space-y-3">
                    @foreach ([
                        [__('Passes for bhog, the picnic and sports day'), __('For everyone in your household, in the app.')],
                        [__('Member prices'), __('On Maitree Serving and plants from the nursery.')],
                        [__('A vote at the AGM'), __('Elect the committee and pass the yearly accounts.')],
                        [__('One membership, whole household'), __('Add everyone at home on the same form.')],
                        [__('Dues, receipts and notices in the app'), __('Pay by UPI and get the receipt straight away.')],
                        [__('A digital ID card'), __('Scanned at the gate to let you in.')],
                    ] as [$title, $text])
                        <li class="flex gap-3"><x-icon name="check" class="w-5 h-5 mt-0.5 text-tulsi" /><div><p class="font-semibold">{{ $title }}</p><p class="text-[14px] text-ink-2">{{ $text }}</p></div></li>
                    @endforeach
                </ul>
            </details>
            <p class="text-[15px] text-ink-2">{{ __('Already a member?') }} <a href="{{ route('member.login') }}" class="font-semibold text-sindoor hover:underline">{{ __('Sign in to the app') }}</a></p>
            <p class="text-[15px] text-ink-2">{{ __('Want to help without joining?') }} <a href="{{ route('volunteer') }}" class="font-semibold text-sindoor hover:underline">{{ __('Volunteer') }}</a></p>
        </aside>

        <form method="POST" action="{{ route('forms.join') }}" class="lg:col-span-8 card p-6 sm:p-8 space-y-10"
            x-data="{ members: {{ Js::from(array_values(is_array($oldMembers) ? $oldMembers : [])) }}, name: {{ Js::from(old('name', '')) }} }">
            @csrf
            @include('site.partials.honeypot', ['form' => 'join'])
            <p class="text-[13px] text-ink-3 num">{{ __('Reg. No.') }} {{ $club['reg_no'] }}</p>

            <fieldset class="grid sm:grid-cols-2 gap-4">
                <legend class="font-display text-[1.6rem] mb-3">{{ __('About you') }}</legend>
                <x-field class="sm:col-span-2" name="name" :label="__('Full name')" autocomplete="name" x-model="name" required />
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
                <x-field name="phone" type="tel" :label="__('Mobile (for OTP login)')" autocomplete="tel" inputmode="tel" required />
                <x-field name="email" type="email" :label="__('Email (optional)')" autocomplete="email" />
            </fieldset>

            <fieldset class="grid sm:grid-cols-2 gap-4">
                <legend class="font-display text-[1.6rem] mb-3">{{ __('Address & family') }}</legend>
                <x-field class="sm:col-span-2" name="address" :label="__('Address')" textarea rows="2" autocomplete="street-address" required />
                <div>
                    <label for="f-join-area" class="label">{{ __('Lane / area') }}</label>
                    <select id="f-join-area" name="area" class="field" required>
                        @foreach (\App\Support\FormOptions::AREAS as $value => $option)
                            <option value="{{ $value }}" @selected(old('area') === $value)>{{ __($option['label']) }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="area" />
                </div>
                <x-field name="aadhaar_last4" :label="__('Aadhaar, last 4 digits')" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" :hint="__('We don’t store the full number. Show the card once at the club room.')" />

                <div class="sm:col-span-2">
                    <p class="font-semibold">{{ __('Family joining with you (optional)') }}</p>
                    <p class="text-[14px] text-ink-2">{{ __('People at the same address can join on your form. Each gets their own ID card.') }}</p>
                    <template x-for="(member, i) in members" :key="i">
                        <div class="mt-3 grid sm:grid-cols-[1fr_10rem_10rem_auto] gap-2 items-end">
                            <label class="block"><span class="label">{{ __('Name') }}</span><input class="field" :name="'members[' + i + '][name]'" x-model="member.name" maxlength="120"></label>
                            <label class="block"><span class="label">{{ __('Relation') }}</span><input class="field" :name="'members[' + i + '][relation]'" x-model="member.relation" maxlength="50"></label>
                            <label class="block"><span class="label">{{ __('Date of birth') }}</span><input type="date" class="field" :name="'members[' + i + '][dob]'" x-model="member.dob"></label>
                            <button type="button" class="btn btn-ghost h-11" @click="members.splice(i, 1)" :aria-label="{{ Js::from(__('Remove')) }}"><x-icon name="x" /></button>
                        </div>
                    </template>
                    <button type="button" class="mt-3 btn btn-line btn-sm" @click="members.push({ name: '', relation: '', dob: '' })" x-show="members.length < 8"><x-icon name="user-plus" /><span>{{ __('Add a family member') }}</span></button>
                    <x-field-error name="members" />
                </div>
            </fieldset>

            <fieldset>
                <legend class="font-display text-[1.6rem] mb-3">{{ __('Plan & sign') }}</legend>
                <p class="font-semibold">{{ __('How would you like to pay the subscription?') }}</p>
                <div class="mt-3 grid sm:grid-cols-2 gap-3">
                    <label class="card p-4 flex gap-3 cursor-pointer has-[:checked]:ring-2 has-[:checked]:ring-sindoor"><input type="radio" name="plan" value="monthly" class="accent-sindoor mt-1" @checked(old('plan', 'monthly') === 'monthly')><span><span class="block font-semibold num">{{ rupees((int) $club['monthly_fee']) }} / {{ __('month') }}</span><span class="text-[14px] text-ink-2">{{ __('Pay in cash or UPI') }}</span></span></label>
                    <label class="card p-4 flex gap-3 cursor-pointer has-[:checked]:ring-2 has-[:checked]:ring-sindoor"><input type="radio" name="plan" value="yearly" class="accent-sindoor mt-1" @checked(old('plan') === 'yearly')><span><span class="block font-semibold num">{{ rupees((int) $club['monthly_fee'] * 11) }} / {{ __('year') }}</span><span class="text-[14px] text-ink-2">{{ __('One month free') }}</span></span></label>
                </div>
                <x-field-error name="plan" />
                <label class="mt-6 flex gap-3 items-start">
                    <input type="checkbox" name="agree" value="1" class="w-5 h-5 mt-0.5 accent-sindoor" @checked(old('agree')) required @error('agree') aria-invalid="true" @enderror>
                    <span class="text-[15px]">
                        <span class="block">{{ __('I want to join Gede Maitree Club of my own free will. I will abide by all the rules and collective decisions of the club. Any anti-social activity on my part will end my membership.') }}</span>
                        <span class="block mt-1 text-[13px] text-ink-3">{{ __('Ticking this box is your signature. The club secretary signs when the committee approves.') }}</span>
                    </span>
                </label>
                <x-field-error name="agree" />
            </fieldset>

            <button type="submit" class="btn btn-primary h-12 px-6">{{ __('Send application') }}</button>
        </form>
    </div>
</x-layouts.site>
