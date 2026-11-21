<ol class="{{ $class ?? '' }} relative border-l-2 border-rule ml-2 space-y-8">
    @foreach ([
        ['2014', 'bg-sindoor', __('The club begins'), __('Twelve members in a rented room on Gede Uttarpara.')],
        ['2015', 'bg-marigold', __('First Durga Puja'), __('A small pandal on the para ground, where it still stands.')],
        ['2022', 'bg-ink', __('A registered society'), __('The accounts are audited every year and presented at the AGM.')],
        ['2023', 'bg-turmeric', __('Maitree Serving begins'), __('The bhog serving team starts serving at the para’s family occasions.')],
        ['2024', 'bg-tulsi', __('Maitree Nursery'), __('A plant nursery beside the club gate.')],
    ] as [$year, $dot, $title, $text])
        <li class="pl-7 relative">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full {{ $dot }} ring-4 ring-paper" aria-hidden="true"></span>
            <p class="font-display text-[1.5rem] leading-none text-sindoor num">{{ digits($year) }}</p>
            <p class="mt-1.5 font-semibold">{{ $title }}</p>
            <p class="text-[15px] text-ink-2">{{ $text }}</p>
        </li>
    @endforeach
</ol>
