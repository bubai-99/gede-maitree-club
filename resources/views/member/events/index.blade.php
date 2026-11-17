<x-layouts.member :title="__('Events & passes')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Events & passes') }}</h1>
    <p class="mt-1 text-ink-2">{{ __('Register your family, get passes and add events to your calendar.') }}</p>

    <nav class="mt-5 inline-flex rounded-full border border-rule bg-card p-1 text-[14.5px] font-semibold" aria-label="{{ __('Events view') }}">
        @foreach ([['upcoming', 'member.events.index', __('Upcoming')], ['passes', 'member.events.passes', __('My passes')], ['past', 'member.events.past', __('Past')]] as [$key, $route, $label])
            <a href="{{ route($route) }}" @if ($tab === $key) aria-current="page" @endif class="px-4 h-9 inline-flex items-center gap-1.5 rounded-full text-ink-2 aria-[current=page]:bg-ink aria-[current=page]:text-paper">
                {{ $label }}@if ($key === 'passes' && $passCount)<span class="num text-[12px] opacity-80">{{ digits($passCount) }}</span>@endif
            </a>
        @endforeach
    </nav>

    <div class="mt-5">
        @if ($tab === 'upcoming')
            <ul class="grid gap-3 md:grid-cols-2">
                @forelse ($upcoming as $event)
                    @php $registration = $registrations[$event->id] ?? null; @endphp
                    <li>
                        <a href="{{ route('member.events.show', $event) }}" class="flex items-center gap-4 rounded-lg border border-rule bg-card p-4 h-full hover:border-ink/30 group">
                            <span class="w-14 text-center leading-none text-sindoor shrink-0 border-r border-rule pr-3"><span class="block font-display text-[2rem] num">{{ local_date($event->starts_at, 'd') }}</span><span class="block text-[11.5px] font-semibold mt-1 uppercase">{{ local_date($event->starts_at, 'M') }}</span></span>
                            <span class="flex-1 min-w-0">
                                <span class="block font-semibold leading-tight group-hover:underline">{{ $event->tr('title') }}</span>
                                <span class="block text-sm text-ink-3 mt-0.5">{{ $event->tr('time_label') }} · {{ $event->tr('place') }}</span>
                                <span class="mt-2 block">
                                    @if ($event->isCancelled())
                                        <span class="chip bg-ink text-paper">{{ __('Cancelled') }}</span>
                                    @elseif ($registration)
                                        <span class="chip bg-tulsi-wash text-tulsi"><x-icon name="check" />{{ __('Registered') }}</span>
                                    @elseif ($event->pass_required)
                                        <span class="chip bg-marigold-wash text-turmeric"><x-icon name="ticket" />{{ __('Pass needed') }}</span>
                                    @elseif ($event->registration_open)
                                        <span class="chip bg-paper-2 text-ink-2">{{ __('Registration open') }}</span>
                                    @else
                                        <span class="chip bg-paper-2 text-ink-2">{{ __('Open to all') }}</span>
                                    @endif
                                </span>
                            </span>
                            <x-icon name="chevron-right" class="w-5 h-5 text-ink-3 shrink-0" />
                        </a>
                    </li>
                @empty
                    <li class="text-ink-2">{{ __('No events are planned yet. Check back soon.') }}</li>
                @endforelse
            </ul>
        @elseif ($tab === 'passes')
            @if ($passes->isNotEmpty())
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($passes as $pass)
                        <a href="{{ route('member.passes.show', $pass) }}" class="block" aria-label="{{ __('Open pass full screen') }}">
                            @include('member.events.partials.pass-card', ['pass' => $pass, 'family' => $family, 'big' => false])
                        </a>
                    @endforeach
                </div>
                <p class="mt-4 text-[15px] text-ink-2 flex gap-2"><x-icon name="wifi-off" class="w-5 h-5 shrink-0" />{{ __('Passes open without internet once loaded. Show the QR at the gate.') }}</p>
            @else
                <div class="rounded-lg border border-dashed border-rule bg-card p-8 text-center">
                    <x-icon name="ticket" class="w-10 h-10 text-ink-3 mx-auto" />
                    <p class="mt-3 font-semibold">{{ __('No passes yet') }}</p>
                    <p class="mt-1 text-ink-2">{{ __('Register for an event that needs a pass and your family pass appears here.') }}</p>
                    <a href="{{ route('member.events.index') }}" class="mt-4 btn btn-primary btn-sm">{{ __('See upcoming events') }}</a>
                </div>
            @endif
        @else
            <ul class="grid gap-3 md:grid-cols-2">
                @forelse ($past as $event)
                    <li class="rounded-lg border border-rule bg-card p-4">
                        <p class="text-sm text-ink-3 num">{{ local_date($event->starts_at, 'j M Y') }}</p>
                        <p class="font-display text-xl leading-tight mt-0.5">{{ $event->tr('title') }}</p>
                        <p class="text-[15px] text-ink-2 mt-1">{{ $event->tr('place') }}</p>
                        @if ($event->isCancelled())
                            <p class="mt-2"><span class="chip bg-ink text-paper">{{ __('Cancelled') }}</span></p>
                        @elseif (isset($registrations[$event->id]))
                            <p class="mt-2 text-sm text-tulsi font-semibold flex items-center gap-1.5"><x-icon name="check" class="w-4 h-4" />{{ __('Your family registered :count people', ['count' => digits($registrations[$event->id]->people)]) }}</p>
                        @endif
                        <div class="mt-3 flex gap-4 text-sm font-semibold"><a href="{{ route('gallery') }}" class="text-sindoor hover:underline">{{ __('Photos') }}</a></div>
                    </li>
                @empty
                    <li class="text-ink-2">{{ __('No past events yet.') }}</li>
                @endforelse
            </ul>
        @endif
    </div>
</x-layouts.member>
