<x-layouts.admin :title="__('Today')">
    <p class="text-ink-2">{{ local_date(now(), 'l, j F Y') }}</p>
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Namaskar') }}, {{ \Illuminate\Support\Str::before($user->name, ' ') }}</h1>

    <dl class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([
            [__('Active families'), \App\Support\Bangla::number($activeFamilies), 'users'],
            [__('Families with dues'), \App\Support\Bangla::number($familiesWithDues), 'bell-ring'],
            [__('Dues outstanding'), rupees($outstanding), 'indian-rupee'],
            [__('Collected this month'), rupees($collectedThisMonth), 'hand-coins'],
        ] as [$label, $value, $icon])
            <div class="card p-5">
                <dt class="flex items-center gap-2 text-[13px] font-semibold text-ink-3"><x-icon :name="$icon" class="w-4 h-4" />{{ $label }}</dt>
                <dd class="mt-2 font-display text-[1.9rem] num">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-8 grid lg:grid-cols-2 gap-6">
        <section class="card p-6" aria-labelledby="enq-h">
            <div class="flex items-center justify-between gap-3">
                <h2 id="enq-h" class="font-display text-[1.5rem]">{{ __('Website enquiries') }}</h2>
                <span class="chip bg-sindoor text-white num">{{ digits($openEnquiries) }}</span>
            </div>
            <ul class="mt-3 divide-y divide-rule">
                @forelse ($latestEnquiries as $enquiry)
                    <li class="py-2.5 flex justify-between gap-3"><a href="{{ route('admin.enquiries.show', $enquiry) }}" class="font-semibold hover:text-sindoor">{{ $enquiry->name }}</a><span class="text-[13px] text-ink-3">{{ $enquiry->kind->label() }}</span></li>
                @empty
                    <li class="py-2.5 text-ink-2">{{ __('All caught up.') }}</li>
                @endforelse
            </ul>
            <a href="{{ route('admin.enquiries.index') }}" class="mt-3 inline-flex items-center gap-1 text-[14px] font-semibold text-sindoor hover:underline">{{ __('All enquiries') }}<x-icon name="arrow-right" class="w-4 h-4" /></a>
        </section>

        <section class="card p-6" aria-labelledby="ev-h">
            <h2 id="ev-h" class="font-display text-[1.5rem]">{{ __('Coming up') }}</h2>
            <ul class="mt-3 divide-y divide-rule">
                @forelse ($upcoming as $event)
                    <li class="py-2.5 flex justify-between gap-3"><span class="font-semibold">{{ $event->tr('title') }}</span><span class="text-[13px] text-ink-3 num">{{ local_date($event->starts_at, 'j M') }}</span></li>
                @empty
                    <li class="py-2.5 text-ink-2">{{ __('No events are planned yet. Check back soon.') }}</li>
                @endforelse
            </ul>
            @if ($drafts)
                <p class="mt-3 text-[14px] text-turmeric">{{ trans_choice(':count draft|:count drafts', $drafts, ['count' => digits($drafts)]) }}</p>
            @endif
        </section>

        @if ($lowStock->isNotEmpty())
            <section class="card p-6" aria-labelledby="stock-h">
                <h2 id="stock-h" class="font-display text-[1.5rem]">{{ __('Plants running low') }}</h2>
                <ul class="mt-3 divide-y divide-rule">
                    @foreach ($lowStock as $plant)
                        <li class="py-2.5 flex justify-between gap-3"><span>{{ $plant->tr('name') }}</span><span class="num text-sindoor-deep">{{ digits($plant->stock) }}</span></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.admin>
