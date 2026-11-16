<x-layouts.member :title="__('Family & profile')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Family & profile') }}</h1>

    <section class="mt-6 card p-6" aria-labelledby="family-h">
        <h2 id="family-h" class="font-display text-[1.5rem]">{{ __('Your family') }}</h2>
        <dl class="mt-3 grid sm:grid-cols-[10rem_1fr] gap-y-2 text-[15px]">
            <dt class="text-ink-2">{{ __('Family number') }}</dt><dd class="num">{{ $family->family_no }}</dd>
            <dt class="text-ink-2">{{ __('Head of family') }}</dt><dd>{{ $family->tr('head_name') }}</dd>
            <dt class="text-ink-2">{{ __('Address') }}</dt><dd>{{ $family->address }}</dd>
            <dt class="text-ink-2">{{ __('Lane / area') }}</dt><dd>{{ $family->tr('area') }}</dd>
            <dt class="text-ink-2">{{ __('Member since') }}</dt><dd class="num">{{ local_date($family->joined_on, 'F Y') }}</dd>
            <dt class="text-ink-2">{{ __('Status') }}</dt><dd>{{ $family->status->label() }}</dd>
        </dl>
    </section>

    <section class="mt-6" aria-labelledby="people-h">
        <h2 id="people-h" class="font-display text-[1.5rem]">{{ __('People on the membership') }}</h2>
        <ul class="mt-3 grid sm:grid-cols-2 gap-4">
            @foreach ($members as $member)
                <li class="card p-5 flex items-center gap-4">
                    <span class="w-12 h-12 rounded-full bg-ink text-white grid place-items-center font-display text-lg shrink-0" aria-hidden="true">{{ mb_substr((string) $member->tr('name'), 0, 1) }}</span>
                    <span class="min-w-0">
                        <span class="block font-semibold">{{ $member->tr('name') }}</span>
                        <span class="block text-[14px] text-ink-2">{{ local_text($member->relation, $member->relation_bn) }}@if ($member->age() !== null) · {{ __(':age years', ['age' => digits($member->age())]) }}@endif</span>
                        @if ($member->blood_group)
                            <span class="mt-1 inline-flex chip h-6 bg-sindoor-wash text-sindoor-deep"><x-icon name="droplet" />{{ $member->blood_group }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
        <p class="mt-4 text-[14px] text-ink-3">{{ __('To add someone or change details, ask the secretary at the club room.') }}</p>
    </section>

    <section class="mt-6 card p-6" aria-labelledby="account-h">
        <h2 id="account-h" class="font-display text-[1.5rem]">{{ __('Your sign-in') }}</h2>
        <dl class="mt-3 grid sm:grid-cols-[10rem_1fr] gap-y-2 text-[15px]">
            <dt class="text-ink-2">{{ __('Name') }}</dt><dd>{{ $user->name }}</dd>
            <dt class="text-ink-2">{{ __('Mobile') }}</dt><dd class="num">{{ $user->phone ?? '—' }}</dd>
            <dt class="text-ink-2">{{ __('Email') }}</dt><dd>{{ $user->email }}</dd>
        </dl>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-lang-switch long />
            <form method="POST" action="{{ route('member.logout') }}">@csrf<button class="btn btn-line btn-sm"><x-icon name="log-out" /><span>{{ __('Sign out') }}</span></button></form>
        </div>
    </section>
</x-layouts.member>
