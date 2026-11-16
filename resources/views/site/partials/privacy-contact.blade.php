<div class="mt-10 card p-6 flex flex-wrap items-center gap-4">
    <x-icon name="shield-check" class="w-7 h-7 text-tulsi" />
    <p class="flex-1 min-w-[220px]"><span class="block font-semibold">{{ __('Questions, or want your details deleted?') }}</span><span class="block text-ink-2">{{ __('Contact the secretary. We reply within 30 days.') }}</span></p>
    <a href="tel:{{ preg_replace('/\s+/', '', $club['phone']) }}" class="btn btn-line"><x-icon name="phone" /><span class="num">{{ $club['phone'] }}</span></a>
</div>
