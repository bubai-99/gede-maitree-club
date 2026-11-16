@php
    $content = app(\App\Support\PageContent::class);
    $whatsapp = $site->club()['whatsapp'];
    $plantData = $plants->map(fn ($p) => ['slug' => $p->slug, 'name' => $p->tr('name'), 'price' => $p->price])->values();
@endphp
<x-layouts.site seo="nursery" :title="$content->text('nursery', 'title')">
    <div class="book-nursery"><x-page-banner page="nursery" /></div>

    <div class="mx-auto max-w-[1160px] px-5 py-14 book-nursery"
        x-data="{ plants: {{ Js::from($plantData) }}, basket: {}, q: '',
            add(slug) { this.basket[slug] = (this.basket[slug] || 0) + 1 },
            remove(slug) { if (this.basket[slug] > 1) this.basket[slug]--; else delete this.basket[slug] },
            get lines() { return Object.entries(this.basket).map(([slug, qty]) => ({ ...this.plants.find(p => p.slug === slug), qty })) },
            get total() { return this.lines.reduce((t, l) => t + l.price * l.qty, 0) },
            get message() { return {{ Js::from(__('Hello, I would like these plants:')) }} + '\n' + this.lines.map(l => '• ' + l.name + ' × ' + l.qty).join('\n') + '\n' + {{ Js::from(__('Total')) }} + ': ₹' + this.total },
            matches(name) { return name.toLowerCase().includes(this.q.toLowerCase()) } }">
        <ol class="grid md:grid-cols-3 gap-4">
            @foreach ([[__('Pick plants'), __('Add them below; the total updates as you go.')], [__('Send on WhatsApp'), __('Sutapa-di sets them aside and replies.')], [__('Collect & pay'), __('Cash or UPI at the gate, with a receipt.')]] as $i => [$title, $text])
                <li class="card p-5 flex gap-4"><span class="w-9 h-9 rounded-full bg-tulsi text-white grid place-items-center font-semibold num shrink-0">{{ digits($i + 1) }}</span><div><p class="font-semibold">{{ $title }}</p><p class="text-ink-2 text-[15px]">{{ $text }}</p></div></li>
            @endforeach
        </ol>

        <div class="mt-12 flex flex-wrap items-center justify-between gap-4">
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Plant types') }}">
                <a href="{{ route('nursery') }}" class="chip border border-rule hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white" @if (! $category) aria-current="page" @endif>{{ __('All') }}</a>
                @foreach (\App\Enums\PlantCategory::cases() as $option)
                    <a href="{{ route('nursery', ['category' => $option->value]) }}" class="chip border border-rule hover:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-white" @if ($category === $option) aria-current="page" @endif>{{ $option->label() }}</a>
                @endforeach
            </nav>
            <div class="relative w-full sm:w-72">
                <label for="plant-search" class="sr-only">{{ __('Search plants') }}</label>
                <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-3" />
                <input id="plant-search" type="search" x-model="q" class="field pl-9" placeholder="{{ __('Search plants') }}">
            </div>
        </div>

        <ul class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($plants as $plant)
                <li class="card overflow-hidden flex flex-col" x-show="matches({{ Js::from($plant->tr('name').' '.$plant->name) }})">
                    <div class="relative aspect-[4/3] bg-paper-3">
                        @if ($plant->samplePhotoKey())
                            <x-photo :key="$plant->samplePhotoKey()" :width="640" sizes="(min-width: 1024px) 33vw, 100vw" />
                        @elseif ($plant->photoUrl())
                            <img src="{{ $plant->photoUrl() }}" alt="{{ $plant->tr('name') }}" class="w-full h-full object-cover" loading="lazy">
                        @endif
                        @if ($plant->is_new)
                            <span class="absolute top-3 left-3 chip bg-white text-tulsi">{{ __('New') }}</span>
                        @endif
                    </div>
                    <div class="p-5 flex-1 flex flex-col">
                        <p class="text-[13px] font-semibold text-tulsi uppercase tracking-wide">{{ $plant->category->label() }}</p>
                        <h2 class="mt-1 font-display text-[1.4rem] leading-snug">{{ $plant->tr('name') }}</h2>
                        <p class="text-[14px] text-ink-3">{{ $plant->tr('pot') }}</p>
                        <p class="mt-2 text-[15px] text-ink-2 flex-1">{{ $plant->tr('about') }}</p>
                        <p class="mt-3 flex flex-wrap gap-2 text-[13px]">
                            @if ($plant->light)<span class="chip h-6 bg-paper-2 text-ink-2"><x-icon name="sun" />{{ $plant->light->label() }}</span>@endif
                            @if ($plant->water)<span class="chip h-6 bg-paper-2 text-ink-2"><x-icon name="droplet" />{{ $plant->water->label() }}</span>@endif
                        </p>
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <p><span class="font-display text-[1.6rem] num">{{ rupees($plant->price) }}</span> <span class="block text-[12.5px] text-ink-3 num">{{ __('Members :price', ['price' => rupees($plant->member_price)]) }}</span></p>
                            @if ($plant->inStock())
                                <button type="button" class="btn btn-line btn-sm" @click="add('{{ $plant->slug }}')"><x-icon name="plus" /><span>{{ __('Add') }}</span></button>
                            @else
                                <span class="chip bg-paper-2 text-ink-3">{{ __('Back soon') }}</span>
                            @endif
                        </div>
                        @if ($plant->samplePhotoKey())
                            <p class="mt-3 text-[11.5px] text-ink-3"><x-photo-credit :key="$plant->samplePhotoKey()" /></p>
                        @endif
                    </div>
                </li>
            @empty
                <li class="text-ink-2">{{ __('No plants in this group right now.') }}</li>
            @endforelse
        </ul>

        <div class="mt-14 grid md:grid-cols-3 gap-5">
            @foreach ([1, 2, 3] as $n)
                <div class="card p-6"><h2 class="font-display text-[1.4rem]">{{ $content->text('nursery', "card{$n}_title") }}</h2><p class="mt-2 text-ink-2">{{ $content->text('nursery', "card{$n}_text") }}</p></div>
            @endforeach
        </div>

        {{-- Basket: order on WhatsApp --}}
        <div class="fixed bottom-4 inset-x-4 sm:left-auto sm:right-6 sm:w-[380px] z-30 card p-5 shadow-2xl" x-show="lines.length" x-cloak x-transition role="region" aria-label="{{ __('Your plants') }}">
            <p class="font-semibold">{{ __('Your plants') }}</p>
            <ul class="mt-2 space-y-1.5 text-[15px] max-h-48 overflow-y-auto">
                <template x-for="line in lines" :key="line.slug">
                    <li class="flex items-center justify-between gap-2">
                        <span x-text="line.name + ' × ' + line.qty"></span>
                        <span class="flex items-center gap-1">
                            <button type="button" class="w-7 h-7 rounded border border-rule" @click="remove(line.slug)" aria-label="{{ __('Remove one') }}">−</button>
                            <button type="button" class="w-7 h-7 rounded border border-rule" @click="add(line.slug)" aria-label="{{ __('Add one') }}">+</button>
                        </span>
                    </li>
                </template>
            </ul>
            <p class="mt-3 flex justify-between font-semibold"><span>{{ __('Total') }}</span><span class="num">₹<span x-text="total"></span></span></p>
            <a :href="'https://wa.me/{{ $whatsapp }}?text=' + encodeURIComponent(message)" target="_blank" rel="noopener" class="mt-3 btn btn-primary w-full"><x-icon name="message-circle" /><span>{{ __('Order on WhatsApp') }}</span></a>
            <p class="mt-2 text-[12.5px] text-ink-3">{{ __('Pick up at the club gate, 7–10 am and 4–7 pm. Pay at the gate in cash or UPI.') }}</p>
        </div>
    </div>
</x-layouts.site>
