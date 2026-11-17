<x-layouts.auth :title="__('Committee sign-in')" portal="admin" photo="pujaCrowd">
    <h1 class="font-display text-[2.3rem] leading-tight">{{ __('Committee sign-in') }}</h1>
    <p class="mt-2 text-[15px] text-ink-2">{{ __('For office bearers and in-charges. Members use the member app.') }}</p>

    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 space-y-5">
        @csrf
        <x-field name="login" :label="__('Mobile number or email')" autocomplete="username" placeholder="98300 12345" required autofocus />
        <x-password-field :label="__('Password')" required />
        <div class="flex items-center justify-between gap-3 text-[15px]">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="w-5 h-5 accent-[#2b2f36]"> {{ __('Keep me signed in') }}</label>
            <a href="{{ route('password.request') }}" class="font-semibold text-sindoor hover:underline">{{ __('Forgot password?') }}</a>
        </div>
        <button type="submit" class="btn btn-primary w-full h-12 text-base">{{ __('Continue') }}</button>
    </form>

    <p class="mt-8 text-[15px] text-ink-2"><a href="{{ route('member.login') }}" class="font-semibold text-ink hover:underline">{{ __('Member sign-in') }}</a></p>
</x-layouts.auth>
