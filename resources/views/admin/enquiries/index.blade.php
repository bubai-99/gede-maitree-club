<x-layouts.admin :title="__('Enquiries')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Enquiries') }}</h1>
    <p class="mt-1 text-ink-2">{{ __('Everything sent from the website forms.') }}</p>

    <form method="GET" class="mt-5 flex flex-wrap items-end gap-3">
        <div>
            <label for="f-kind" class="label">{{ __('Form') }}</label>
            <select id="f-kind" name="kind" class="field h-10">
                <option value="">{{ __('All') }}</option>
                @foreach (\App\Enums\EnquiryKind::cases() as $option)
                    <option value="{{ $option->value }}" @selected($kind === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-show" class="label">{{ __('Show') }}</label>
            <select id="f-show" name="show" class="field h-10">
                @foreach (['open' => __('Waiting for a reply'), 'handled' => __('Handled'), 'all' => __('All')] as $value => $label)
                    <option value="{{ $value }}" @selected($show === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-line h-10">{{ __('Filter') }}</button>
    </form>

    <div class="mt-6 overflow-x-auto rounded-xl border border-rule bg-card">
        <table class="khata-table">
            <thead><tr><th scope="col">{{ __('Received') }}</th><th scope="col">{{ __('Form') }}</th><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Phone') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead>
            <tbody>
                @forelse ($enquiries as $enquiry)
                    <tr>
                        <td class="num whitespace-nowrap">{{ local_date($enquiry->created_at, 'j M, g:i a') }}</td>
                        <td>{{ $enquiry->kind->label() }}</td>
                        <td><a href="{{ route('admin.enquiries.show', $enquiry) }}" class="font-semibold hover:text-sindoor">{{ $enquiry->name }}</a></td>
                        <td class="num"><a href="tel:+91{{ $enquiry->phone }}" class="hover:underline">{{ $enquiry->phone }}</a></td>
                        <td>@if ($enquiry->handled_at)<span class="chip bg-tulsi-wash text-tulsi">{{ __('Handled') }}</span>@else<span class="chip bg-marigold-wash text-turmeric">{{ __('Waiting for a reply') }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-ink-2">{{ __('Nothing here yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $enquiries->links() }}</div>
</x-layouts.admin>
