<x-layouts.admin :title="__('Enquiries')">
    <div class="max-w-[760px]">
        <a href="{{ route('admin.enquiries.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Enquiries') }}</a>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ $enquiry->name }}</h1>
        <p class="text-ink-2">{{ $enquiry->kind->label() }} · <span class="num">{{ local_date($enquiry->created_at, 'j F Y, g:i a') }}</span></p>

        <dl class="mt-6 card p-6 grid sm:grid-cols-[12rem_1fr] gap-x-6 gap-y-3">
            @if ($enquiry->phone)
                <dt class="font-semibold">{{ __('Phone') }}</dt>
                <dd class="num"><a href="tel:+91{{ $enquiry->phone }}" class="hover:underline">{{ $enquiry->phone }}</a> · <a href="https://wa.me/91{{ $enquiry->phone }}" target="_blank" rel="noopener" class="text-sindoor hover:underline">WhatsApp</a></dd>
            @endif
            @if ($enquiry->email)
                <dt class="font-semibold">{{ __('Email') }}</dt><dd>{{ $enquiry->email }}</dd>
            @endif
            <dt class="font-semibold">{{ __('Language') }}</dt><dd>{{ $enquiry->locale === 'bn' ? 'বাংলা' : 'English' }}</dd>
            @foreach ($enquiry->payload ?? [] as $field => $value)
                <dt class="font-semibold">{{ \Illuminate\Support\Str::headline($field) }}</dt>
                {{-- Line breaks kept inside a value (a typed message), not around it --}}
                <dd class="break-words">
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <span class="block">{{ is_array($item) ? implode(' · ', array_filter(array_map('strval', $item))) : $item }}</span>
                        @endforeach
                    @elseif (is_bool($value))
                        {{ $value ? __('Yes') : __('No') }}
                    @else
                        <span class="whitespace-pre-line">{{ $value }}</span>
                    @endif
                </dd>
            @endforeach
        </dl>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            @if ($enquiry->handled_at)
                <p class="text-tulsi">{{ __('Handled :when', ['when' => local_date($enquiry->handled_at, 'j M, g:i a')]) }}@if ($enquiry->handler) · {{ $enquiry->handler->name }}@endif</p>
            @endif
            @can('update', $enquiry)
                <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="handled" value="{{ $enquiry->handled_at ? 0 : 1 }}">
                    <button class="btn {{ $enquiry->handled_at ? 'btn-line' : 'btn-primary' }}">{{ $enquiry->handled_at ? __('Open again') : __('Mark as handled') }}</button>
                </form>
            @endcan
        </div>
    </div>
</x-layouts.admin>
