@php($club = $site->club())
<x-layouts.member :title="__('Member cards')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Member cards') }}</h1>
    <p class="text-ink-2">{{ __('A card for each person. Tap a card to see the gate QR on the back.') }}</p>

    <ul class="mt-6 grid md:grid-cols-2 gap-6">
        @foreach ($members as $member)
            <li x-data="{ back: false }">
                <button type="button" class="idc-wrap idc-flip block w-full text-left" :class="{ 'is-back': back }" @click="back = ! back"
                    :aria-label="back ? {{ Js::from(__('Show the front of :name’s card', ['name' => $member->tr('name')])) }} : {{ Js::from(__('Show the gate QR for :name', ['name' => $member->tr('name')])) }}">
                    <div class="idc-flip-inner">
                        {{-- Front --}}
                        <div class="idc" aria-hidden="false">
                            <div class="idc-band">
                                <span class="idc-seal"><x-seal :size="40" :estd="false" color="#fff" aria-hidden="true" /></span>
                                <span class="idc-club"><span class="font-display">{{ local_text($club['name'], $club['name_bn']) }}</span><span class="idc-sub">{{ __('MEMBER') }}</span></span>
                                <span class="idc-kind">{{ $member->is_head ? __('HEAD') : __('FAMILY') }}</span>
                            </div>
                            <div class="idc-body">
                                <div class="idc-photo"><span class="idc-initials">{{ mb_substr((string) $member->tr('name'), 0, 1) }}</span></div>
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
                                <div class="idc-qr bg-white">
                                    @if ($codes[$member->id])
                                        {{-- SVG drawn on the server by bacon/bacon-qr-code from a code the app makes; no user text. --}}
                                        {!! $codes[$member->id] !!}
                                    @endif
                                </div>
                                <div class="idc-terms">
                                    <p class="idc-terms-h">{{ __('Gate pass') }}</p>
                                    <p>{{ __('Show this QR at the gate. The card is not transferable.') }}</p>
                                    <p class="idc-found">{{ __('Found this card? Please return it to the club room.') }} {{ $club['phone'] }}</p>
                                </div>
                            </div>
                            <div class="idc-strip num">{{ $member->member_no }}</div>
                        </div>
                    </div>
                </button>
            </li>
        @endforeach
    </ul>
    <p class="mt-6 text-[14px] text-ink-3">{{ __('Lost a card? Tell the secretary; the old QR is cancelled and a new card is issued.') }}</p>
</x-layouts.member>
