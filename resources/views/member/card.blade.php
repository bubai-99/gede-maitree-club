@php
    $club = $site->club();
    $paidUp = $family->dues()->where('status', \App\Enums\DueStatus::Due->value)->count() <= 5;
    $secretaryText = rawurlencode(__('I have lost a member card. Family :no.', ['no' => $family->family_no]));
@endphp
<x-layouts.member :title="__('Member cards')">
    <div x-data="{ who: 0, view: 'card', back: false }">
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-0">
                <h1 class="font-display text-[1.7rem] lg:text-[2.3rem] leading-tight">{{ __('Member cards') }}</h1>
                <p class="text-[15px] text-ink-2 mt-1">{{ __('Everyone in your family has their own card and gate QR.') }}</p>
            </div>
            <div class="ml-auto flex flex-wrap gap-2" data-no-print>
                <button type="button" class="btn btn-line btn-sm" @click="window.print()"><x-icon name="printer" />{{ __('Print all cards') }}</button>
            </div>
        </div>

        <div class="carousel mt-5"><div class="swiper" data-carousel data-gap="8"><div class="swiper-wrapper" role="tablist" aria-label="{{ __('Family member') }}" data-no-print>
            @foreach ($members as $i => $member)
                <button type="button" role="tab" :aria-selected="(who === {{ $i }}).toString()" @click="who = {{ $i }}; back = false"
                    class="swiper-slide !w-auto shrink-0 flex items-center gap-2 h-11 pl-1.5 pr-4 rounded-full whitespace-nowrap border border-rule bg-card text-[15px] font-semibold aria-selected:bg-ink aria-selected:text-paper aria-selected:border-ink">
                    <span class="w-8 h-8 text-sm rounded-full bg-paper-2 text-ink grid place-items-center font-display" aria-hidden="true">{{ mb_substr((string) $member->tr('name'), 0, 1) }}</span>{{ \Illuminate\Support\Str::before((string) $member->tr('name'), ' ') ?: $member->tr('name') }}
                </button>
            @endforeach
        </div></div></div>

        @foreach ($members as $i => $member)
            <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,480px)_1fr] items-start print:!grid" x-show="who === {{ $i }}" @if ($i) x-cloak @endif>
                <div class="min-w-0">
                    <div class="grid grid-cols-2 p-1 rounded-[7px] bg-paper-2" role="tablist" aria-label="{{ __('Card view') }}" data-no-print>
                        <button type="button" role="tab" :aria-selected="(view === 'card').toString()" @click="view = 'card'" class="h-10 rounded-[5px] text-[15px] font-semibold text-ink-2 aria-selected:bg-white aria-selected:text-ink aria-selected:shadow-[0_1px_2px_rgba(0,0,0,.12)]">{{ __('Card') }}</button>
                        <button type="button" role="tab" :aria-selected="(view === 'gate').toString()" @click="view = 'gate'" class="h-10 rounded-[5px] text-[15px] font-semibold text-ink-2 aria-selected:bg-white aria-selected:text-ink aria-selected:shadow-[0_1px_2px_rgba(0,0,0,.12)]">{{ __('Gate QR') }}</button>
                    </div>
                    <div class="mt-4">
                        <div x-show="view === 'card'">
                            <button type="button" class="idc-wrap idc-flip block w-full text-left" :class="{ 'is-back': back }" @click="back = ! back" aria-label="{{ __('Flip card') }}">
                                <div class="idc-flip-inner">
                                    {{-- Front --}}
                                    <div class="idc">
                                        <div class="idc-band">
                                            <span class="idc-seal"><x-seal :size="40" :estd="false" color="#fff" aria-hidden="true" /></span>
                                            <span class="idc-club"><span class="font-display">{{ local_text($club['name'], $club['name_bn']) }}</span><span class="idc-sub">{{ __('MEMBER') }}</span></span>
                                            <span class="idc-kind">{{ $member->is_head ? __('HEAD') : __('FAMILY') }}</span>
                                        </div>
                                        <div class="idc-body">
                                            {{-- Behind the details: a faint alpana with the club seal at its heart, and a festive wave at the foot --}}
                                            <svg class="idc-art" viewBox="0 0 200 200" aria-hidden="true">
                                                <g fill="none" stroke="currentColor" stroke-width=".8">
                                                    <circle cx="100" cy="100" r="96"/><circle cx="100" cy="100" r="88" stroke-dasharray="2 4"/><circle cx="100" cy="100" r="58"/>
                                                    @for ($i = 0; $i < 16; $i++)
                                                        <path d="M100 4 C 110 22, 110 34, 100 42 C 90 34, 90 22, 100 4 Z" transform="rotate({{ $i * 22.5 }} 100 100)"/>
                                                        <circle cx="100" cy="50" r="2.2" transform="rotate({{ $i * 22.5 + 11.25 }} 100 100)"/>
                                                    @endfor
                                                </g>
                                            </svg>
                                            <span class="idc-art-seal" aria-hidden="true"><x-seal :size="96" :rotate="0" :estd="false" color="currentColor" /></span>
                                            <svg class="idc-wave" viewBox="0 0 400 24" preserveAspectRatio="none" aria-hidden="true"><path d="M0 14 C 40 4, 80 24, 120 14 S 200 4, 240 14 S 320 24, 360 14 S 400 8, 400 8 V24 H0Z" fill="currentColor"/></svg>
                                            <div class="idc-photo overflow-hidden"><x-id-photo :member="$member" /></div>
                                            <div class="idc-info">
                                                <p class="idc-name">{{ $member->tr('name') }}</p>
                                                <p class="idc-rel">{{ local_text($member->relation, $member->relation_bn) }}</p>
                                                <dl class="idc-grid">
                                                    <div><dt>{{ __('Card no.') }}</dt><dd class="num">{{ $member->member_no ?? '—' }}</dd></div>
                                                    <div><dt>{{ __('Blood') }}</dt><dd>{{ $member->blood_group ?? '—' }}</dd></div>
                                                    <div><dt>{{ __('Family') }}</dt><dd class="num">{{ $family->family_no }}</dd></div>
                                                    <div><dt>{{ __('Since') }}</dt><dd class="num">{{ $family->joined_on ? digits($family->joined_on->year) : '—' }}</dd></div>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Back: gate QR --}}
                                    <div class="idc idc-back">
                                        <div class="idc-back-body">
                                            <svg class="idc-art idc-art-back" viewBox="0 0 200 200" aria-hidden="true">
                                                <g fill="none" stroke="currentColor" stroke-width=".8">
                                                    <circle cx="100" cy="100" r="96"/><circle cx="100" cy="100" r="88" stroke-dasharray="2 4"/><circle cx="100" cy="100" r="58"/>
                                                    @for ($i = 0; $i < 16; $i++)
                                                        <path d="M100 4 C 110 22, 110 34, 100 42 C 90 34, 90 22, 100 4 Z" transform="rotate({{ $i * 22.5 }} 100 100)"/>
                                                    @endfor
                                                </g>
                                            </svg>
                                            <div class="idc-qr bg-white">
                                                @if ($codes[$member->id])
                                                    {{-- SVG drawn on the server by bacon/bacon-qr-code from a code the app makes; no user text. --}}
                                                    {!! $codes[$member->id] !!}
                                                @endif
                                            </div>
                                            <div class="idc-terms">
                                                <p class="idc-terms-h">{{ __('Show this card at the gate') }}</p>
                                                <p>{{ __('For club events and member prices at the nursery and on catering. Not transferable.') }}</p>
                                                <p class="idc-found">{{ __('Regd.') }} <span class="num">{{ $club['reg_no'] }}</span> · {{ __('If found, please return to: :address', ['address' => local_text($club['address'], $club['address_bn'] ?? null)]) }} · <span class="num">{{ $club['phone'] }}</span></p>
                                                @if ($secretary)
                                                    <div class="idc-sign">
                                                        <span class="idc-signature">{{ $secretary->tr('name') }}</span>
                                                        <span class="idc-sign-l">{{ __('Secretary') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="idc-strip num">{{ $member->member_no }}</div>
                                    </div>
                                </div>
                            </button>
                            <button type="button" class="btn btn-ghost w-full mt-2" @click="back = ! back" data-no-print><x-icon name="repeat" /><span x-text="back ? {{ Js::from(__('Show front')) }} : {{ Js::from(__('Show back')) }}">{{ __('Show back') }}</span></button>
                        </div>
                        <div x-show="view === 'gate'" x-cloak>
                            <div class="rounded-lg border-2 border-ink bg-white p-5 text-center">
                                <div class="w-56 h-56 sm:w-64 sm:h-64 mx-auto [&>svg]:w-full [&>svg]:h-full" role="img" aria-label="{{ __('Gate QR for :name', ['name' => $member->tr('name')]) }}">
                                    @if ($codes[$member->id])
                                        {!! $codes[$member->id] !!}
                                    @endif
                                </div>
                                <p class="mt-4 poster text-[1.7rem]">{{ $member->tr('name') }}</p>
                                <p class="text-[15px] num mt-1 text-ink-2">{{ $member->member_no ?? '—' }} · {{ local_text($member->relation, $member->relation_bn) }}</p>
                            </div>
                            <p class="mt-4 text-[15px] text-ink-2 flex gap-2"><x-icon name="sun" class="w-5 h-5 shrink-0" /><span>{{ __('Turn your screen brightness up at the gate.') }}</span></p>
                        </div>
                    </div>
                </div>
                <div class="min-w-0 space-y-4" data-no-print>
                    <div class="rounded-lg border border-rule bg-card">
                        <dl class="divide-y divide-rule text-[15px]">
                            <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Member ID') }}</dt><dd class="font-semibold num">{{ $member->member_no ?? '—' }}</dd></div>
                            <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Status') }}</dt><dd>
                                @if ($paidUp)
                                    <span class="chip bg-tulsi-wash text-tulsi"><x-icon name="check" />{{ __('Active') }}</span>
                                @else
                                    <span class="chip border border-ink/40 text-ink"><x-icon name="clock" />{{ __('Inactive: dues') }}</span>
                                @endif
                            </dd></div>
                            <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Family') }}</dt><dd class="num">{{ $family->family_no }}</dd></div>
                            <div class="flex justify-between gap-3 px-4 py-3"><dt class="text-ink-3">{{ __('Blood group') }}</dt><dd class="num">{{ $member->blood_group ?? '—' }}</dd></div>
                        </dl>
                    </div>
                    <button type="button" class="btn btn-line h-12 w-full" @click="window.print()"><x-icon name="printer" />{{ __('Print') }}</button>
                    <p class="text-[13px] text-ink-2">{{ __('Print gives the cards on paper; choose “Save as PDF” to keep a file. A laminated card is also handed out at the club room.') }}</p>
                    <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
                        <a href="https://wa.me/{{ $club['whatsapp'] }}?text={{ $secretaryText }}" target="_blank" rel="noopener" class="text-sindoor hover:underline">{{ __('Report lost card') }}</a>
                        <a href="{{ route('member.profile') }}" class="text-sindoor hover:underline">{{ __('Edit details') }}</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <p class="mt-6 text-[14px] text-ink-3">{{ __('Lost a card? Tell the secretary; the old QR is cancelled and a new card is issued.') }}</p>
</x-layouts.member>
