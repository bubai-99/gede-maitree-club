{{-- One-off messages after a form is sent (stored in the session as a translation key, with optional :placeholders). --}}
@php
    $replace = is_array(session('status_replace')) ? session('status_replace') : [];
@endphp
@if (session('status'))
    <div role="status" {{ $attributes->merge(['class' => 'mx-auto max-w-[1160px] px-5 mt-4']) }} data-no-print>
        <p class="flex items-start gap-3 rounded-xl border border-tulsi/30 bg-tulsi-wash px-4 py-3 text-[15px] text-tulsi">
            <x-icon name="circle-check" class="w-5 h-5 mt-0.5" />
            <span>{{ __(session('status'), $replace) }}</span>
        </p>
    </div>
@endif
@if (session('error'))
    <div role="alert" {{ $attributes->merge(['class' => 'mx-auto max-w-[1160px] px-5 mt-4']) }} data-no-print>
        <p class="flex items-start gap-3 rounded-xl border border-sindoor/30 bg-sindoor-wash px-4 py-3 text-[15px] text-sindoor-deep">
            <x-icon name="circle-alert" class="w-5 h-5 mt-0.5" />
            <span>{{ __(session('error'), $replace) }}</span>
        </p>
    </div>
@endif
{{-- Form problems: one summary above the page, a link to each field (resources/js/forms.js marks the fields). --}}
@if ($errors->any())
    @php
        $problems = collect($errors->getMessages())->map(fn ($messages) => $messages[0]);
    @endphp
    <div {{ $attributes->merge(['class' => 'mx-auto max-w-[1160px] px-5 mt-4']) }} data-no-print>
        <div class="rounded-xl border border-sindoor/30 bg-sindoor-wash px-4 py-3.5 text-[15px] text-sindoor-deep" role="alert" tabindex="-1" data-error-summary>
            <p class="flex items-center gap-2.5 font-semibold"><x-icon name="circle-alert" class="w-5 h-5 shrink-0" />{{ trans_choice('Please check this and try again:|Please check these :count things and try again:', $problems->count(), ['count' => digits($problems->count())]) }}</p>
            <ul class="mt-2 ml-7 list-disc space-y-1">
                @foreach ($problems as $field => $message)
                    <li><a href="#" data-error-field="{{ $field }}" class="underline underline-offset-2 hover:no-underline">{{ $message }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
{{-- The browser's own "please fill in this field" messages, in the page's language --}}
@if (app()->getLocale() === 'bn')
    @php
        $formMessages = [
        'valueMissing' => __('Please fill in this field.'),
        'selectMissing' => __('Please choose one.'),
        'checkMissing' => __('Please tick this box.'),
        'email' => __('Enter a real email address, like name@example.com.'),
        'url' => __('Enter a full link, starting with https://.'),
        'tooShort' => __('Please write at least :min characters.'),
        'tooLong' => __('Please write at most :max characters.'),
        'rangeUnderflow' => __('Must be at least :min.'),
        'rangeOverflow' => __('Must be at most :max.'),
        'pattern' => __('This is not in the right format.'),
        'number' => __('Enter a number.'),
    ];
    @endphp
    <script type="application/json" id="form-messages">{!! json_encode($formMessages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
