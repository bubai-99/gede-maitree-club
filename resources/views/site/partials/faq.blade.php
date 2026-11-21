@php
    $faq = [
        [__('Who can become a member?'), __('Anyone living in or around Gede Uttarpara. Each member fills the form, and people in the same household can join on one form. The subscription covers the whole household.'), null],
        [__('Can non-members come to the pujas?'), __('Of course. Pujas and the blood donation camp are open to everyone. Only bhog, the picnic and sports day need a member pass.'), null],
        [__('How do I book the serving team?'), __('Pick a package on the catering page and ask for a quote, or come to the club. We serve, we don’t cook. Members pay 10% less.'), null],
        [__('How do I pay my subscription?'), __('By UPI in the member app, or in cash at the club. You get a receipt on WhatsApp for every payment.'), null],
    ];
    if (request()->routeIs('contact') && Route::has('chanda')) {
        $faq[] = [__('Can I give puja chanda without being a member?'), __('Yes. Give what you can; there is no fixed amount. You get a receipt for every rupee, and the full accounts go up on this website after the puja.'), route('chanda')];
    }
@endphp
{{-- One question open at a time is not forced; each answer slides open (grid rows 0fr → 1fr). --}}
@php($faqId = 'faq-'.substr(md5(request()->path()), 0, 6))
@foreach ($faq as $i => [$question, $answer, $link])
    <div class="border-b border-rule" x-data="{ open: {{ $i === 0 ? 'true' : 'false' }} }">
        <h3>
            <button type="button" id="{{ $faqId }}-q{{ $i }}" aria-controls="{{ $faqId }}-a{{ $i }}" :aria-expanded="open.toString()" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" @click="open = ! open"
                    class="w-full flex items-center justify-between gap-4 py-5 text-left font-semibold text-[17px] hover:text-sindoor-deep transition-colors">
                <span>{{ $question }}</span>
                <span class="faq-icon shrink-0 inline-flex" data-open="{{ $i === 0 ? 'true' : 'false' }}" :data-open="open.toString()"><x-icon name="plus" class="w-5 h-5" /></span>
            </button>
        </h3>
        <div id="{{ $faqId }}-a{{ $i }}" role="region" aria-labelledby="{{ $faqId }}-q{{ $i }}" class="faq-panel"
             data-open="{{ $i === 0 ? 'true' : 'false' }}" :data-open="open.toString()" x-effect="$el.inert = ! open" @if ($i !== 0) inert @endif>
            <div class="overflow-hidden">
                <p class="pb-5 -mt-1 text-ink-2 max-w-[62ch]"><span>{{ $answer }}</span>@if ($link) <a href="{{ $link }}" class="font-semibold text-sindoor hover:underline">{{ __('See the chanda page') }}</a>@endif</p>
            </div>
        </div>
    </div>
@endforeach
@if (Route::has('contact') && ! request()->routeIs('contact'))
    <a href="{{ route('contact') }}" class="mt-4 inline-flex items-center gap-1.5 text-[15px] font-semibold text-sindoor hover:underline"><span>{{ __('More questions and contact') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
@endif
