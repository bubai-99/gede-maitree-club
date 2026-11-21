{{-- Cash or UPI (with the UTR) for catering money. Needs x-data="{ mode: 'cash' }" on the form. --}}
@php($upi = $upi ?? true)
<div>
    <label for="f-mode-{{ $upi ? 'in' : 'out' }}" class="label">{{ __('Paid by') }}</label>
    <select id="f-mode-{{ $upi ? 'in' : 'out' }}" name="mode" class="field" x-model="mode">
        <option value="cash">{{ \App\Enums\PaymentMode::Cash->label() }}</option>
        <option value="upi">{{ \App\Enums\PaymentMode::Upi->label() }}</option>
    </select>
</div>
@if ($upi)
    <div class="col-span-2" x-show="mode === 'upi'" x-cloak>
        <x-field name="upi_ref" :label="__('UTR (for UPI)')" inputmode="numeric" maxlength="40" />
    </div>
@endif
