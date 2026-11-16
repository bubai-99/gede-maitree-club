{{-- One-off messages after a form is sent (stored in the session as a translation key). --}}
@if (session('status'))
    <div role="status" {{ $attributes->merge(['class' => 'mx-auto max-w-[1160px] px-5 mt-4']) }}>
        <p class="flex items-start gap-3 rounded-xl border border-tulsi/30 bg-tulsi-wash px-4 py-3 text-[15px] text-tulsi">
            <x-icon name="circle-check" class="w-5 h-5 mt-0.5" />
            <span>{{ __(session('status')) }}</span>
        </p>
    </div>
@endif
