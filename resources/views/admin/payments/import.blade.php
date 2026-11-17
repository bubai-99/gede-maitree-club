<x-layouts.admin :title="__('Import bank statement')">
    <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-1 h-9 text-sm font-semibold text-ink-2 hover:text-ink -ml-1"><x-icon name="arrow-left" class="w-4 h-4" />{{ __('Online payments') }}</a>
    <h1 class="mt-2 font-display text-[2rem] leading-tight">{{ __('Import bank statement') }}</h1>
    <p class="mt-1 text-ink-2 max-w-2xl">{{ __('Download the statement from the bank’s website as CSV and upload it here. Each line’s description is searched for a 12-digit UTR; when it matches a UPI payment waiting for you with the same amount, you can confirm them all in one go.') }}</p>

    <div class="mt-6 grid lg:grid-cols-12 gap-6 items-start">
        <form method="POST" action="{{ route('admin.payments.import.store') }}" enctype="multipart/form-data" class="lg:col-span-7 paper-card p-5 sm:p-6 space-y-4">
            @csrf
            <div>
                <label for="statement" class="label">{{ __('Statement file (CSV, up to 2 MB)') }}</label>
                <input id="statement" name="statement" type="file" accept=".csv,text/csv,text/plain" class="field pt-2" required @error('statement') aria-invalid="true" aria-describedby="statement-error" @enderror>
                <x-field-error name="statement" />
            </div>
            <button class="btn btn-ink"><x-icon name="upload" /><span>{{ __('Upload and check') }}</span></button>
            <p class="text-sm text-ink-3">{{ __('Nothing is confirmed until you check the matches on the next page.') }}</p>
        </form>
        <div class="lg:col-span-5 paper-card p-5 text-[15px] text-ink-2">
            <p class="font-semibold text-ink">{{ __('What the file needs') }}</p>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                <li>{{ __('The first row has the column names.') }}</li>
                <li>{{ __('A column with the money received (credit), in rupees.') }}</li>
                <li>{{ __('A column with the description or narration, where the bank shows the UPI reference.') }}</li>
                <li>{{ __('A date column is optional.') }}</li>
            </ul>
            <p class="mt-3">{{ __('UPI payments waiting now') }}: <strong class="num">{{ digits($waiting) }}</strong></p>
        </div>
    </div>
</x-layouts.admin>
