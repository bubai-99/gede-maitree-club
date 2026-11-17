<x-layouts.admin :title="__('Chanda receipts')">
    <a href="{{ route('admin.chanda.show', $event) }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-ink-2 hover:text-ink"><x-icon name="arrow-left" class="w-4 h-4" />{{ $event->tr('title') }}</a>
    <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
        <h1 class="font-display text-[2rem] leading-tight">{{ __('Chanda receipts') }}</h1>
        @if ($manager)
            <a href="{{ route('admin.chanda.export', $event) }}" class="btn btn-line btn-sm"><x-icon name="file-spreadsheet" /><span>{{ __('Download CSV') }}</span></a>
        @endif
    </div>

    @if ($series)
        @if ($series['missing'] === [])
            <p class="mt-4 rounded-lg bg-tulsi-wash text-tulsi px-4 py-3 text-[15px] flex items-center gap-2"><x-icon name="circle-check" class="w-5 h-5" />
                @if ($series['last'] > 0)
                    {{ __('Series complete: every number from :first to :last is here (cancelled ones included).', ['first' => \App\Support\ReceiptNumbers::formatChanda($series['year'], 1), 'last' => $series['latest']]) }}
                @else
                    {{ __('No chanda receipts this year yet.') }}
                @endif
            </p>
        @else
            <p class="mt-4 rounded-lg bg-sindoor-wash text-sindoor-deep px-4 py-3 text-[15px]">{{ __('Numbers missing from the series: :numbers', ['numbers' => implode(', ', $series['missing'])]) }}</p>
        @endif
    @endif

    <section class="mt-5 paper-card overflow-hidden">
        @include('admin.chanda.partials.receipt-rows', ['receipts' => $receipts])
    </section>
    <div class="mt-6">{{ $receipts->links() }}</div>
</x-layouts.admin>
