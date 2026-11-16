<x-layouts.auth :title="__('Committee sign-in')" portal="admin" photo="pujaCrowd">
    <p class="text-[11.5px] font-semibold tracking-[0.14em] text-ink-2">{{ __('COMMITTEE CONSOLE') }}</p>
    <h1 class="mt-1 font-display text-[2rem] leading-tight">{{ __('Committee sign-in') }}</h1>

    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4">
        @csrf
        <x-field name="login" :label="__('Mobile number or email')" autocomplete="username" required autofocus />
        <x-field name="password" type="password" :label="__('Password')" autocomplete="current-password" required />
        <div class="flex items-center justify-between gap-3 text-[14px]">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="w-4 h-4 accent-sindoor"> {{ __('Keep me signed in') }}</label>
            <a href="{{ route('password.request') }}" class="font-semibold text-sindoor hover:underline">{{ __('Forgot password?') }}</a>
        </div>
        <button type="submit" class="btn btn-ink w-full h-12">{{ __('Sign in') }}</button>
    </form>

    <div class="mt-8 pt-6 border-t border-rule text-[14px] text-ink-2">
        <a href="{{ route('member.login') }}" class="font-semibold hover:text-ink hover:underline">{{ __('Member? Sign in to the member app →') }}</a>
    </div>
</x-layouts.auth>
