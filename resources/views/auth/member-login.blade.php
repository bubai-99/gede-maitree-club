<x-layouts.auth :title="__('Member sign-in')" portal="member">
    <div x-data="{ tab: '{{ $errors->has('phone') ? 'code' : 'password' }}' }">
        <h1 class="font-display text-[2rem] leading-tight">{{ __('Member sign-in') }}</h1>

        <div class="mt-6 grid grid-cols-2 gap-1 p-1 rounded-lg bg-paper-2 text-[14px] font-semibold" role="tablist" aria-label="{{ __('How to sign in') }}">
            <button type="button" role="tab" id="tab-password" aria-controls="panel-password" :aria-selected="(tab === 'password').toString()" @click="tab = 'password'"
                class="h-10 rounded-md text-ink-2 aria-selected:bg-white aria-selected:text-ink aria-selected:shadow-sm">{{ __('Password') }}</button>
            <button type="button" role="tab" id="tab-code" aria-controls="panel-code" :aria-selected="(tab === 'code').toString()" @click="tab = 'code'"
                class="h-10 rounded-md text-ink-2 aria-selected:bg-white aria-selected:text-ink aria-selected:shadow-sm">{{ __('One-time code') }}</button>
        </div>

        <form method="POST" action="{{ route('member.login.store') }}" class="mt-6 space-y-4" id="panel-password" role="tabpanel" aria-labelledby="tab-password" x-show="tab === 'password'">
            @csrf
            <x-field name="login" :label="__('Mobile number or email')" autocomplete="username" required autofocus inputmode="email" />
            <x-field name="password" type="password" :label="__('Password')" autocomplete="current-password" required />
            <div class="flex items-center justify-between gap-3 text-[14px]">
                <label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="w-4 h-4 accent-sindoor"> {{ __('Keep me signed in') }}</label>
                <a href="{{ route('password.request') }}" class="font-semibold text-sindoor hover:underline">{{ __('Forgot password?') }}</a>
            </div>
            <button type="submit" class="btn btn-primary w-full h-12">{{ __('Sign in') }}</button>
        </form>

        <form method="POST" action="{{ route('member.otp.send') }}" class="mt-6 space-y-4" id="panel-code" role="tabpanel" aria-labelledby="tab-code" x-show="tab === 'code'" x-cloak>
            @csrf
            <x-field name="phone" type="tel" :label="__('Mobile number')" autocomplete="tel" inputmode="tel" required
                :hint="__('We’ll text a 6-digit code to this number. No password needed.')" />
            <button type="submit" class="btn btn-primary w-full h-12">{{ __('Send code') }}</button>
        </form>

        <div class="mt-8 pt-6 border-t border-rule space-y-2 text-[14px] text-ink-2">
            @if (Route::has('join'))
                <p>{{ __('Not a member yet?') }} <a href="{{ route('join') }}" class="font-semibold text-sindoor hover:underline">{{ __('Apply to join') }}</a></p>
            @endif
            <p>{{ __('On the committee?') }} <a href="{{ route('admin.login') }}" class="font-semibold text-sindoor hover:underline">{{ __('Committee sign-in') }}</a></p>
        </div>
    </div>
</x-layouts.auth>
