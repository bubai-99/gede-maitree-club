<x-layouts.auth :title="__('Choose a new password')" portal="member">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Choose a new password') }}</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" type="email" :label="__('Email')" :value="$email" autocomplete="email" required />
        <x-field name="password" type="password" :label="__('New password')" autocomplete="new-password" required
            :hint="__('At least 8 characters. A phrase is easier to remember.')" />
        <x-field name="password_confirmation" type="password" :label="__('New password again')" autocomplete="new-password" required />
        <button type="submit" class="btn btn-primary w-full h-12">{{ __('Save the new password') }}</button>
    </form>
</x-layouts.auth>
