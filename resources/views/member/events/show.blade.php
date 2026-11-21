@php
    $active = $registration?->isActive() ?? false;
    $open = $event->acceptsRegistrations();
    $canCancel = $active && ! $event->isCancelled() && now()->lessThan($event->cutoff());
    $limit = $event->familyLimit();
    $editing = $errors->has('people') || request()->boolean('change');
@endphp
<x-layouts.member :title="$event->tr('title')">
    <a href="{{ route('member.events.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Events & passes') }}</a>

    <div class="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] items-start">
        <div class="min-w-0">
            <p class="eyebrow text-sindoor">{{ $event->kind->label() }}</p>
            <h1 class="mt-1 font-display text-[2.2rem] leading-tight">{{ $event->tr('title') }}</h1>

            @if ($event->isCancelled())
                <div class="mt-4 rounded-lg border-2 border-sindoor bg-sindoor-wash/50 p-4" role="status">
                    <p class="font-semibold text-sindoor-deep">{{ __('This event has been cancelled.') }}</p>
                    <p class="text-[15px] mt-1">{{ local_text($event->cancel_reason, $event->cancel_reason_bn) }}</p>
                </div>
            @endif

            <p class="mt-4 text-[17px] leading-relaxed text-ink-2 max-w-[62ch]">{{ $event->tr('about') }}</p>

            @if ($event->registration_open || $registration)
                <section class="mt-6 rounded-lg border border-rule bg-card p-4 sm:p-5" aria-labelledby="reg-h">
                    <h2 id="reg-h" class="font-display text-xl">{{ __('Register your family') }}</h2>

                    @if ($active && ! $editing)
                        <div class="mt-3">
                            @if ($registration->pass)
                                <a href="{{ route('member.passes.show', $registration->pass) }}" class="block" aria-label="{{ __('Open pass full screen') }}">
                                    @include('member.events.partials.pass-card', ['pass' => $registration->pass->setRelation('event', $event), 'family' => $family, 'big' => false])
                                </a>
                            @else
                                <div class="rounded-lg border-2 border-tulsi bg-tulsi-wash/50 p-4">
                                    <p class="font-semibold flex items-center gap-2"><x-icon name="circle-check" class="w-5 h-5 text-tulsi" />{{ __('You’re registered') }}</p>
                                    <p class="text-[15px] text-ink-2 mt-1">{{ __(':count people', ['count' => digits($registration->people)]) }} · {{ __('No pass needed') }}</p>
                                </div>
                            @endif
                            @if ($canCancel)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($open)
                                        <a href="{{ route('member.events.show', [$event, 'change' => 1]) }}#reg-h" class="btn btn-line btn-sm">{{ __('Change how many are coming') }}</a>
                                    @endif
                                    <form method="POST" action="{{ route('member.events.unregister', $event) }}" x-data @submit="if (! confirm({{ Js::from(__('Cancel this registration? You can register again until entries close.')) }})) $event.preventDefault()">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-ghost btn-sm text-sindoor-deep">{{ __('Cancel registration') }}</button>
                                    </form>
                                </div>
                                <p class="mt-2 text-[13px] text-ink-3">{{ __('You can change or cancel until :time.', ['time' => local_date($event->cutoff(), 'j F, g:i a')]) }}</p>
                            @endif
                        </div>
                    @elseif ($open)
                        <form method="POST" action="{{ route('member.events.register', $event) }}" class="mt-3 space-y-3">
                            @csrf
                            <p class="text-[15px] text-ink-2">{{ __('How many people from your family are coming? Up to :count.', ['count' => digits($limit)]) }}
                                {{ __('Entries close :time.', ['time' => local_date($event->cutoff(), 'j F, g:i a')]) }}</p>
                            @if ($seatsLeft !== null)
                                <p class="text-[14px] {{ $seatsLeft < 10 ? 'text-sindoor-deep font-semibold' : 'text-ink-3' }}">{{ __(':count places left', ['count' => digits($seatsLeft)]) }}</p>
                            @endif
                            <div class="flex items-end gap-3">
                                <x-field name="people" type="number" min="1" :max="$limit" inputmode="numeric" :label="__('People')" :value="$registration?->people ?? min(4, $limit)" required class="w-32" />
                                <button class="btn btn-primary h-12 px-6">{{ $active ? __('Save changes') : ($event->pass_required ? __('Register and get the pass') : __('Register')) }}</button>
                            </div>
                            @if ($event->members_only)
                                <p class="text-[13px] text-ink-3">{{ __('For member families with active membership.') }}</p>
                            @endif
                        </form>
                    @elseif (! $event->isCancelled())
                        <p class="mt-2 text-[15px] text-ink-2">{{ __('Registration for this event is closed.') }}</p>
                    @endif
                </section>
            @elseif ($event->pass_required)
                <a href="{{ route('member.card') }}" class="mt-5 card p-5 flex items-center gap-4 border-marigold">
                    <x-icon name="ticket" class="w-7 h-7 text-turmeric" />
                    <span><span class="block font-semibold">{{ __('Member pass needed') }}</span><span class="block text-[14px] text-ink-2">{{ __('Show your card at the gate') }}</span></span>
                </a>
            @endif

            @if ($event->schedule)
                <h2 class="mt-8 font-display text-[1.5rem]">{{ __('Programme') }}</h2>
                <ol class="mt-3 border-t border-rule max-w-[640px]">
                    @foreach ($event->schedule as $item)
                        <li class="leader py-3 border-b border-rule"><span>{{ local_text($item['what'] ?? '', $item['what_bn'] ?? null) }}</span><span class="font-semibold num">{{ local_text($item['time'] ?? '', $item['time_bn'] ?? null) }}</span></li>
                    @endforeach
                </ol>
            @endif
        </div>

        <aside class="min-w-0 space-y-3 lg:sticky lg:top-24">
            <dl class="rounded-lg border border-rule bg-card divide-y divide-rule text-[15px]">
                <div class="flex gap-3 px-4 py-3"><dt><x-icon name="calendar-days" class="w-5 h-5 text-ink-3" /><span class="sr-only">{{ __('Date') }}</span></dt><dd>{{ local_date($event->starts_at, 'l, j F Y') }}</dd></div>
                @if ($event->tr('time_label'))
                    <div class="flex gap-3 px-4 py-3"><dt><x-icon name="clock" class="w-5 h-5 text-ink-3" /><span class="sr-only">{{ __('Time') }}</span></dt><dd>{{ $event->tr('time_label') }}</dd></div>
                @endif
                <div class="flex gap-3 px-4 py-3"><dt><x-icon name="map-pin" class="w-5 h-5 text-ink-3" /><span class="sr-only">{{ __('Place') }}</span></dt><dd>{{ $event->tr('place') }}</dd></div>
                <div class="flex gap-3 px-4 py-3"><dt><x-icon name="ticket" class="w-5 h-5 text-ink-3" /><span class="sr-only">{{ __('Entry') }}</span></dt><dd>{{ $event->pass_required ? __('Family pass at the gate') : ($event->members_only ? __('Members only') : __('Open to all, no pass')) }}</dd></div>
            </dl>
        </aside>
    </div>
</x-layouts.member>
