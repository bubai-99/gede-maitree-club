<x-layouts.auth :title="__('Enter the code')" portal="member">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Enter the code') }}</h1>
    <p class="mt-2 text-ink-2">{{ __('We sent a 6-digit code to the number ending :digits.', ['digits' => digits(substr($phone, -4))]) }}</p>

    <form method="POST" action="{{ route('member.otp.verify') }}" class="mt-6 space-y-4">
        @csrf
        <x-field name="code" :label="__('6-digit code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus />
        <button type="submit" class="btn btn-primary w-full h-12">{{ __('Sign in') }}</button>
    </form>

    <form method="POST" action="{{ route('member.otp.send') }}" class="mt-4">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone }}">
        <p class="text-[14px] text-ink-2">{{ __('Didn’t get it?') }} <button type="submit" class="font-semibold text-sindoor hover:underline">{{ __('Resend code') }}</button></p>
    </form>

    <p class="mt-6 text-[14px]"><a href="{{ route('member.login') }}" class="font-semibold text-ink-2 hover:text-ink">← {{ __('Back') }}</a></p>
</x-layouts.auth>
