<x-layouts.auth :title="__('Forgot password?')" portal="member">
    <a href="{{ route('member.login') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-ink-2 hover:text-ink -ml-1 mb-6"><x-icon name="chevron-left" class="w-4 h-4" />{{ __('Back to sign-in') }}</a>
    <h1 class="font-display text-[2.3rem] leading-tight">{{ __('Reset your password') }}</h1>
    <p class="mt-2 text-[15px] text-ink-2">{{ __('Enter the email on your account and we will send a link to choose a new password.') }}</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <x-field name="email" type="email" :label="__('Email')" autocomplete="email" required autofocus />
        <button type="submit" class="btn btn-primary w-full h-12 text-base">{{ __('Send the link') }}</button>
    </form>

    <p class="mt-8 text-[15px] text-ink-2">{{ __('No email on your account? Sign in with a one-time code, or ask the secretary at the club room.') }}</p>
    <p class="mt-2 text-[15px] flex flex-wrap gap-x-4 gap-y-1">
        <a href="{{ route('member.login') }}" class="font-semibold text-sindoor hover:underline">{{ __('Member sign-in') }}</a>
        <a href="{{ route('admin.login') }}" class="font-semibold text-ink hover:underline">{{ __('Committee sign-in') }}</a>
    </p>
</x-layouts.auth>
