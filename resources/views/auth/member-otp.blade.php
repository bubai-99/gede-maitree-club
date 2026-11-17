<x-layouts.auth :title="__('Enter the code')" portal="member">
    <a href="{{ route('member.login') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-ink-2 hover:text-ink -ml-1 mb-6"><x-icon name="chevron-left" class="w-4 h-4" />{{ __('Change number') }}</a>
    <h1 class="font-display text-[2.3rem] leading-tight">{{ __('Check your messages') }}</h1>
    <p class="mt-2 text-[15px] text-ink-2">{{ __('We sent a 6-digit code to the number ending :digits.', ['digits' => digits(substr($phone, -4))]) }}</p>

    <form method="POST" action="{{ route('member.otp.verify') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="f-code" class="label">{{ __('6-digit code') }}</label>
            <input id="f-code" name="code" class="field h-14 text-center text-2xl font-semibold num tracking-[0.5em] border-2 focus:border-ink" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus @error('code') aria-invalid="true" @enderror />
            <x-field-error name="code" />
        </div>
        <button type="submit" class="btn btn-primary w-full h-12 text-base">{{ __('Verify') }}</button>
    </form>

    <form method="POST" action="{{ route('member.otp.send') }}" class="mt-5">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone }}">
        <p class="text-[15px] text-ink-2">{{ __('Didn’t get it?') }} <button type="submit" class="font-semibold text-sindoor hover:underline">{{ __('Resend code') }}</button></p>
    </form>
</x-layouts.auth>
