<x-layouts.admin :title="__('Remind on WhatsApp')">
    <a href="{{ route('admin.dues.index') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Dues & reminders') }}</a>
    <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Remind on WhatsApp') }}</h1>
    <p class="mt-1 text-ink-2">{{ __('Tap each family to open WhatsApp with the message filled in, then press send. Nothing is sent automatically.') }}</p>

    <ul class="mt-6 space-y-3 max-w-3xl">
        @foreach ($reminders as $reminder)
            <li class="paper-card p-4 flex flex-wrap items-center gap-3">
                <span class="min-w-0 flex-1">
                    <span class="block font-semibold">{{ $reminder['family']->tr('head_name') }} <span class="text-[13px] text-ink-3 num font-normal">{{ $reminder['family']->family_no }}</span></span>
                    <span class="block text-[13px] text-ink-2">{{ __(':count months due', ['count' => digits($reminder['months'])]) }} · {{ rupees($reminder['total']) }}</span>
                </span>
                @if ($reminder['link'])
                    <a href="{{ $reminder['link'] }}" target="_blank" rel="noopener" class="btn btn-ink btn-sm"><x-icon name="message-circle" /><span>{{ __('Open WhatsApp') }}</span></a>
                @else
                    <span class="text-sm text-sindoor-deep">{{ __('No mobile number on file') }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</x-layouts.admin>
