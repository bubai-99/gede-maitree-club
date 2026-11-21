<x-layouts.auth :title="__('Member sign-in')" portal="member">
    <div x-data="{ tab: '{{ $errors->has('phone') ? 'code' : 'password' }}' }">
        <h1 class="font-display text-[2.3rem] leading-tight">{{ __('Sign in') }}</h1>
        <p class="mt-2 text-[15px] text-ink-2">{{ __('Members of Gede Maitree Club, Gede Uttarpara.') }}</p>

        <div class="mt-8 grid grid-cols-2 p-1 rounded-[7px] bg-paper-2" role="tablist" aria-label="{{ __('How to sign in') }}">
            <button type="button" role="tab" id="tab-password" aria-controls="panel-password" :aria-selected="(tab === 'password').toString()" @click="tab = 'password'"
                class="h-10 rounded-[5px] text-[15px] font-semibold text-ink-2 aria-selected:bg-white aria-selected:text-ink aria-selected:shadow-[0_1px_2px_rgba(0,0,0,.12)]">{{ __('Password') }}</button>
            <button type="button" role="tab" id="tab-code" aria-controls="panel-code" :aria-selected="(tab === 'code').toString()" @click="tab = 'code'"
                class="h-10 rounded-[5px] text-[15px] font-semibold text-ink-2 aria-selected:bg-white aria-selected:text-ink aria-selected:shadow-[0_1px_2px_rgba(0,0,0,.12)]">{{ __('One-time code') }}</button>
        </div>

        <form method="POST" action="{{ route('member.login.store') }}" class="mt-6 space-y-5" id="panel-password" role="tabpanel" aria-labelledby="tab-password" x-show="tab === 'password'">
            @csrf
            <x-field name="login" :label="__('Mobile number or email')" autocomplete="username" placeholder="98300 12345" required autofocus inputmode="email" />
            <x-password-field :label="__('Password')" required />
            <div class="flex items-center justify-between gap-3 text-[15px] -mt-2">
                <label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="w-5 h-5 accent-[#2b2f36]"> {{ __('Keep me signed in') }}</label>
                <a href="{{ route('password.request') }}" class="font-semibold text-sindoor hover:underline">{{ __('Forgot password?') }}</a>
            </div>
            <button type="submit" class="btn btn-primary w-full h-12 text-base">{{ __('Sign in') }}</button>
        </form>

        <form method="POST" action="{{ route('member.otp.send') }}" class="mt-6 space-y-5" id="panel-code" role="tabpanel" aria-labelledby="tab-code" x-show="tab === 'code'" x-cloak>
            @csrf
            <div>
                <label for="f-phone" class="label">{{ __('Mobile number') }}</label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-[4px] border border-r-0 border-rule bg-paper-2 text-ink-2 text-[15px] num">+91</span>
                    <input id="f-phone" name="phone" class="field rounded-l-none num" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="11" placeholder="98300 12345" value="{{ old('phone') }}" required @error('phone') aria-invalid="true" @enderror />
                </div>
                <x-field-error name="phone" />
            </div>
            <p class="text-[15px] text-ink-2">{{ __('We’ll text a 6-digit code to this number. No password needed.') }}</p>
            <button type="submit" class="btn btn-primary w-full h-12 text-base">{{ __('Send code') }}</button>
        </form>

        <div class="mt-8 pt-6 border-t-2 border-dotted border-sindoor/60 space-y-2 text-[15px]">
            @if (Route::has('join'))
                <p class="text-ink-2">{{ __('Not a member yet?') }} <a href="{{ route('join') }}" class="font-semibold text-sindoor hover:underline">{{ __('Apply to join') }}</a></p>
            @endif
            <p class="text-ink-2">{{ __('On the committee?') }} <a href="{{ route('admin.login') }}" class="font-semibold text-ink hover:underline">{{ __('Committee sign-in') }}</a></p>
        </div>
    </div>
</x-layouts.auth>
