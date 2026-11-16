<x-layouts.auth :title="__('Forgot password?')" portal="member">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Forgot password?') }}</h1>
    <p class="mt-2 text-ink-2">{{ __('Enter the email on your account and we will send a link to choose a new password.') }}</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <x-field name="email" type="email" :label="__('Email')" autocomplete="email" required autofocus />
        <button type="submit" class="btn btn-primary w-full h-12">{{ __('Send the link') }}</button>
    </form>

    <div class="mt-8 pt-6 border-t border-rule space-y-2 text-[14px] text-ink-2">
        <p>{{ __('No email on your account? Sign in with a one-time code, or ask the secretary at the club room.') }}</p>
        <p class="flex flex-wrap gap-x-4 gap-y-1">
            <a href="{{ route('member.login') }}" class="font-semibold text-sindoor hover:underline">{{ __('Member sign-in') }}</a>
            <a href="{{ route('admin.login') }}" class="font-semibold text-sindoor hover:underline">{{ __('Committee sign-in') }}</a>
        </p>
    </div>
</x-layouts.auth>
