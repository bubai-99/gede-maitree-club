@foreach ([
    [__('Who can become a member?'), __('Anyone living in or around Netaji Para. Each member fills the form, and people in the same household can join on one form. The subscription covers the whole household.')],
    [__('Can non-members come to the pujas?'), __('Of course. Pujas and the blood donation camp are open to everyone. Only bhog, the picnic and sports day need a member pass.')],
    [__('How do I book the serving team?'), __('Pick a package on the catering page and ask for a quote, or come to the club. We serve, we don’t cook. Members pay 10% less.')],
    [__('How do I pay my subscription?'), __('By UPI in the member app, or in cash at the club. You get a receipt on WhatsApp for every payment.')],
] as $i => [$question, $answer])
    <details class="group border-b gz-hair" @if ($i === 0) open @endif>
        <summary class="flex items-center justify-between gap-4 py-5 cursor-pointer list-none font-semibold text-[17px]"><span>{{ $question }}</span><x-icon name="plus" class="w-5 h-5 transition-transform group-open:rotate-45" /></summary>
        <p class="pb-5 -mt-1 text-ink-2 max-w-[62ch]">{{ $answer }}</p>
    </details>
@endforeach
@if (Route::has('contact') && ! request()->routeIs('contact'))
    <a href="{{ route('contact') }}" class="mt-4 inline-flex items-center gap-1.5 text-[15px] font-semibold text-sindoor hover:underline"><span>{{ __('More questions and contact') }}</span><x-icon name="arrow-right" class="w-4 h-4" /></a>
@endif
