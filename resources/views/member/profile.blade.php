@php
    $me = $members->first(fn ($m) => $m->name === $user->name) ?? $members->first();
@endphp
<x-layouts.member :title="__('Family & profile')">
    <h1 class="font-display text-[1.7rem] lg:text-[2.3rem] leading-tight">{{ __('Family & profile') }}</h1>

    <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.75fr)] items-start">
        <div class="min-w-0 space-y-6">
            {{-- Who you are --}}
            <div class="rounded-lg border border-rule bg-card p-4 sm:p-5 flex items-center gap-4">
                <span class="w-16 h-16 rounded-full bg-sindoor text-white grid place-items-center font-display text-2xl shrink-0" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                <div class="min-w-0">
                    <p class="font-display text-[1.5rem] leading-tight">{{ $user->name }}</p>
                    <p class="text-[14px] text-ink-2 num">{{ $family->family_no }}@if ($family->joined_on) · {{ __('member since :year', ['year' => digits($family->joined_on->format('Y'))]) }}@endif</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="chip h-6 text-[12.5px] bg-tulsi-wash text-tulsi"><x-icon name="circle-check" />{{ $family->status->label() }}</span>
                        @if ($me?->blood_group)
                            <span class="chip h-6 text-[12.5px] bg-sindoor-wash text-sindoor-deep"><x-icon name="droplet" />{{ __('Blood group') }} · {{ $me->blood_group }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Family on this membership --}}
            <section aria-labelledby="people-h">
                <div class="flex items-baseline justify-between mb-2">
                    <h2 id="people-h" class="text-[13px] font-bold uppercase tracking-wide text-ink-2">{{ __('Family on this membership') }}</h2>
                    <span class="text-[13px] text-ink-3">{{ trans_choice(':count person|:count people', $members->count(), ['count' => digits($members->count())]) }}</span>
                </div>
                <ul class="rounded-lg border border-rule bg-card divide-y divide-rule">
                    @foreach ($members as $i => $member)
                        <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                            <span class="w-12 h-16 rounded-md overflow-hidden border border-rule shrink-0 bg-paper-2"><x-id-photo :member="$member" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold truncate">{{ $member->tr('name') }}</span>
                                <span class="block text-[13.5px] text-ink-2 num">{{ local_text($member->relation, $member->relation_bn) }}@if ($member->age() !== null) · {{ __(':age years', ['age' => digits($member->age())]) }}@endif @if ($member->blood_group)· {{ $member->blood_group }}@endif</span>
                            </span>
                            <span class="flex items-center gap-1.5 shrink-0">
                                <form method="POST" action="{{ route('member.photos.store', $member) }}" enctype="multipart/form-data">
                                    @csrf
                                    <label class="btn btn-line btn-sm cursor-pointer">
                                        <x-icon name="camera" class="w-4 h-4" /><span>{{ $member->hasPhoto() ? __('Change photo') : __('Add photo') }}</span>
                                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="this.form.submit()" aria-label="{{ __('Photo of :name for the ID card', ['name' => $member->tr('name')]) }}">
                                    </label>
                                </form>
                                @if ($member->hasPhoto())
                                    <form method="POST" action="{{ route('member.photos.destroy', $member) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-ghost btn-sm px-2 text-ink-3" aria-label="{{ __('Remove the photo of :name', ['name' => $member->tr('name')]) }}"><x-icon name="trash-2" class="w-4 h-4" /></button>
                                    </form>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
                @error('photo')<p class="mt-3 text-[14px] text-sindoor-deep" role="alert">{{ $message }}</p>@enderror
                <p class="mt-3 text-[14px] text-ink-3">{{ __('A clear face photo, like a passport photo, goes on each person’s ID card. To add someone or change details, ask the secretary at the club room.') }}</p>
            </section>

            {{-- Your details --}}
            <section aria-labelledby="details-h">
                <h2 id="details-h" class="mb-2 text-[13px] font-bold uppercase tracking-wide text-ink-2">{{ __('Your details') }}</h2>
                <dl class="rounded-lg border border-rule bg-card divide-y divide-rule text-[15px]">
                    <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Mobile') }}</dt><dd class="num">{{ $user->phone ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Email') }}</dt><dd class="truncate">{{ $user->email ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Head of family') }}</dt><dd>{{ $family->tr('head_name') }}</dd></div>
                    <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Address') }}</dt><dd class="text-right">{{ $family->address }}</dd></div>
                    <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Lane / area') }}</dt><dd>{{ $family->tr('area') }}</dd></div>
                    <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Member since') }}</dt><dd class="num">{{ local_date($family->joined_on, 'F Y') }}</dd></div>
                </dl>
            </section>
        </div>

        <div class="min-w-0 space-y-4">
            <a href="{{ route('member.card') }}" class="flex items-center gap-3 rounded-lg border border-rule bg-card p-4 hover:border-ink/30">
                <x-icon name="id-card" class="w-6 h-6 text-sindoor" /><span class="flex-1 font-semibold">{{ __('Member cards') }}</span><x-icon name="chevron-right" class="w-4 h-4 text-ink-3" />
            </a>
            <div class="rounded-lg border border-rule bg-card p-4">
                <p class="flex items-center gap-3 font-semibold"><x-icon name="settings" class="w-6 h-6 text-ink-2" />{{ __('Language and sign-in') }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-lang-switch long />
                    <form method="POST" action="{{ route('member.logout') }}">@csrf<button class="btn btn-line btn-sm"><x-icon name="log-out" /><span>{{ __('Sign out') }}</span></button></form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.member>
