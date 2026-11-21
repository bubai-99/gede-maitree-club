<x-layouts.admin :title="__('New catering enquiry')">
    <div class="max-w-[640px]">
        <a href="{{ route('admin.catering.index') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Catering orders') }}</a>
        <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('New catering enquiry') }}</h1>
        @if ($enquiry)
            <p class="mt-1 text-ink-2">{{ __('From the website form sent :when. It is marked as handled once it is on the board.', ['when' => local_date($enquiry->created_at, 'j M, g:i a')]) }}</p>
        @endif

        <form method="POST" action="{{ route('admin.catering.store') }}" class="mt-6 space-y-4">
            @csrf
            @if ($enquiry)<input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">@endif
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="customer_name" :label="__('Customer')" :value="$prefill['customer_name']" required maxlength="120" />
                <x-field name="phone" type="tel" inputmode="tel" :label="__('Mobile')" :value="$prefill['phone']" required />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="f-occasion" class="label">{{ __('Occasion') }}</label>
                    <select id="f-occasion" name="occasion" class="field">
                        @foreach ($occasions as $key => $option)
                            <option value="{{ $key }}" @selected(old('occasion', $prefill['occasion']) === $key)>{{ __($option['label']) }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="occasion" />
                </div>
                <x-field name="event_date" type="date" :label="__('Date')" :value="$prefill['event_date']" :min="now()->toDateString()" required />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="guests" type="number" min="1" inputmode="numeric" :label="__('Guests')" :value="$prefill['guests']" required />
                <div>
                    <label for="f-package_id" class="label">{{ __('Package') }}</label>
                    <select id="f-package_id" name="package_id" class="field">
                        <option value="">{{ __('Not decided yet') }}</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected((int) old('package_id', $prefill['package_id']) === $package->id)>{{ $package->tr('name') }} · {{ rupees($package->rate) }} / {{ __('guest') }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="package_id" />
                </div>
            </div>
            <x-field name="venue" :label="__('Venue')" :value="$prefill['venue']" maxlength="255" />
            <label class="flex items-center gap-2.5 text-[15px]"><input type="checkbox" name="is_member" value="1" class="w-4 h-4 accent-sindoor" @checked(old('is_member', $prefill['is_member']))> {{ __('Gede Maitree Club member (:percent% off)', ['percent' => digits((int) config('club.member_discount_percent'))]) }}</label>
            <button class="btn btn-primary h-12 px-6"><x-icon name="plus" /><span>{{ __('Add enquiry') }}</span></button>
        </form>
    </div>
</x-layouts.admin>
