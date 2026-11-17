@php
    $content = app(\App\Support\PageContent::class);
    $whatsapp = $site->club()['whatsapp'];
    $plantData = $plants->map(fn ($p) => ['slug' => $p->slug, 'name' => $p->tr('name'), 'price' => $p->price, 'stock' => (int) $p->stock])->values();
@endphp
<x-layouts.site seo="nursery" :title="$content->text('nursery', 'title')">
    <div class="book-nursery"
        x-data="{ plants: {{ Js::from($plantData) }}, basket: {}, q: '',
            add(slug) { const p = this.plants.find(p => p.slug === slug); this.basket[slug] = Math.min(p ? p.stock : 99, (this.basket[slug] || 0) + 1) },
            remove(slug) { if (this.basket[slug] > 1) this.basket[slug]--; else delete this.basket[slug] },
            qty(slug) { return this.basket[slug] || 0 },
            get lines() { return Object.entries(this.basket).map(([slug, qty]) => ({ ...this.plants.find(p => p.slug === slug), qty })) },
            get count() { return this.lines.reduce((t, l) => t + l.qty, 0) },
            get total() { return this.lines.reduce((t, l) => t + l.price * l.qty, 0) },
            get message() { return {{ Js::from(__('Hello, I would like these plants:')) }} + '\n' + this.lines.map(l => '• ' + l.name + ' × ' + l.qty).join('\n') + '\n' + {{ Js::from(__('Total')) }} + ': ₹' + this.total },
            matches(name) { return name.toLowerCase().includes(this.q.toLowerCase()) } }">
        <x-page-banner page="nursery" :crumb="__('Nursery')" kicker-class="text-[#a8dcb0]">
            <ul class="mt-7 flex flex-wrap gap-x-6 gap-y-2 text-[15px] text-white/85">
                <li class="flex items-center gap-2"><x-icon name="clock" class="w-4 h-4 text-marigold" /><span>{{ __('7–10 am & 5–8 pm') }}</span></li>
                <li class="flex items-center gap-2"><x-icon name="badge-percent" class="w-4 h-4 text-marigold" /><span>{{ __('Members :percent% off', ['percent' => digits((int) config('club.member_discount_percent'))]) }}</span></li>
                <li class="flex items-center gap-2"><x-icon name="map-pin" class="w-4 h-4 text-marigold" /><span>{{ __('Collect at the club gate') }}</span></li>
            </ul>
        </x-page-banner>

        <div class="mx-auto max-w-[1160px] px-5 py-10 pb-36">
            <ol class="grid sm:grid-cols-3 gap-4 mb-10">
                @foreach ([['sprout', __('1. Pick plants'), __('Add them below; the total updates as you go.')], ['message-circle', __('2. Send on WhatsApp'), __('Sutapa-di sets them aside and replies.')], ['store', __('3. Collect & pay'), __('Cash or UPI at the gate, with a receipt.')]] as [$icon, $title, $text])
                    <li class="flex gap-3 items-start card p-5"><span class="w-9 h-9 rounded-full bg-tulsi text-white grid place-items-center shrink-0"><x-icon :name="$icon" class="w-[18px] h-[18px]" /></span><div><p class="font-semibold">{{ $title }}</p><p class="text-sm text-ink-2">{{ $text }}</p></div></li>
                @endforeach
            </ol>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <nav class="flex gap-2 overflow-x-auto no-scrollbar pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 flex-1" aria-label="{{ __('Plant types') }}">
                    <a href="{{ route('nursery') }}" class="chip h-9 px-4 text-[15px] border border-rule bg-card hover:border-ink whitespace-nowrap aria-[current=page]:bg-tulsi aria-[current=page]:text-white aria-[current=page]:border-tulsi" @if (! $category) aria-current="page" @endif>{{ __('All') }}</a>
                    @foreach (\App\Enums\PlantCategory::cases() as $option)
                        <a href="{{ route('nursery', ['category' => $option->value]) }}" class="chip h-9 px-4 text-[15px] border border-rule bg-card hover:border-ink whitespace-nowrap aria-[current=page]:bg-tulsi aria-[current=page]:text-white aria-[current=page]:border-tulsi" @if ($category === $option) aria-current="page" @endif>{{ $option->label() }}</a>
                    @endforeach
                </nav>
                <label class="relative sm:w-64 shrink-0"><span class="sr-only">{{ __('Search plants') }}</span><x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-3" /><input type="search" x-model="q" class="field h-10 pl-9" placeholder="{{ __('Search plants') }}"></label>
            </div>

            <ul class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 sm:gap-x-6 gap-y-8">
                @forelse ($plants as $plant)
                    @php
                        $key = $plant->samplePhotoKey();
                        $url = $key ? null : $plant->photoUrl(500);
                        $out = ! $plant->inStock();
                    @endphp
                    <li class="group flex flex-col" x-show="matches({{ Js::from($plant->tr('name').' '.$plant->name) }})">
                        <div class="relative block aspect-square rounded-2xl overflow-hidden bg-tulsi-wash">
                            @if ($key)
                                <x-photo :key="$key" :width="500" sizes="(min-width: 1024px) 260px, 50vw" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04] {{ $out ? 'grayscale opacity-70' : '' }}" />
                            @elseif ($url)
                                <img src="{{ $url }}" alt="{{ $plant->tr('name') }}" class="absolute inset-0 w-full h-full object-cover {{ $out ? 'grayscale opacity-70' : '' }}" loading="lazy">
                            @else
                                <span class="absolute inset-0 grid place-items-center text-tulsi"><x-icon name="sprout" class="w-10 h-10" /></span>
                            @endif
                            <span class="absolute left-2.5 top-2.5 flex gap-1.5">
                                @if ($plant->is_new)<span class="chip h-6 px-2 text-[12px] bg-marigold text-ink">{{ __('New') }}</span>@endif
                                @if ($out)
                                    <span class="chip h-6 px-2 text-[12px] bg-ink text-white">{{ __('Out of stock') }}</span>
                                @elseif ($plant->stock <= 10)
                                    <span class="chip h-6 px-2 text-[12px] bg-white text-sindoor-deep">{{ __('Only :count left', ['count' => digits($plant->stock)]) }}</span>
                                @endif
                            </span>
                            @if ($key)
                                <span class="absolute right-2 bottom-2 rounded bg-black/45 px-1.5 text-[10.5px] text-white/90 opacity-0 group-hover:opacity-100 transition-opacity"><x-photo-credit :key="$key" /></span>
                            @endif
                        </div>
                        <div class="mt-3 flex-1">
                            <p class="font-semibold leading-snug">{{ $plant->tr('name') }}</p>
                            <p class="text-[13.5px] text-ink-3 mt-0.5">{{ $plant->tr('pot') }}</p>
                        </div>
                        <div class="mt-2.5 flex items-center justify-between gap-2">
                            <span class="font-semibold text-[17px] num">{{ rupees($plant->price) }}</span>
                            @if ($out)
                                <span class="text-[13px] text-ink-3">{{ __('Back soon') }}</span>
                            @else
                                <button type="button" x-show="! qty('{{ $plant->slug }}')" class="btn btn-sm border border-tulsi/40 text-tulsi hover:bg-tulsi-wash" @click="add('{{ $plant->slug }}')" aria-label="{{ __('Add :name', ['name' => $plant->tr('name')]) }}"><x-icon name="plus" />{{ __('Add') }}</button>
                                <div x-show="qty('{{ $plant->slug }}')" x-cloak class="flex items-center rounded-lg bg-tulsi text-white" role="group" aria-label="{{ $plant->tr('name') }}">
                                    <button type="button" class="w-9 h-9 grid place-items-center" @click="remove('{{ $plant->slug }}')" aria-label="{{ __('Remove one') }}"><x-icon name="minus" class="w-4 h-4" /></button>
                                    <span class="w-6 text-center font-semibold num" aria-live="polite" x-text="qty('{{ $plant->slug }}')"></span>
                                    <button type="button" class="w-9 h-9 grid place-items-center disabled:opacity-40" @click="add('{{ $plant->slug }}')" :disabled="qty('{{ $plant->slug }}') >= {{ (int) $plant->stock }}" aria-label="{{ __('Add one') }}"><x-icon name="plus" class="w-4 h-4" /></button>
                                </div>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="col-span-2 lg:col-span-3 py-10 text-center text-ink-3">{{ __('No plants in this group right now.') }}</li>
                @endforelse
            </ul>

            <div class="mt-14 grid md:grid-cols-3 gap-5 text-[15px]">
                @foreach ([1 => 'snowflake', 2 => 'droplets', 3 => 'gift'] as $n => $icon)
                    <div class="rounded-2xl bg-tulsi-wash/60 p-6"><p class="font-semibold flex items-center gap-2"><x-icon :name="$icon" class="w-4 h-4 text-tulsi" /><span>{{ $content->text('nursery', "card{$n}_title") }}</span></p><p class="mt-1.5 text-ink-2">{{ $content->text('nursery', "card{$n}_text") }}</p></div>
                @endforeach
            </div>
        </div>

        {{-- Basket: order on WhatsApp --}}
        <div class="fixed inset-x-0 bottom-0 z-30 transition-transform duration-300 ease-[cubic-bezier(.16,1,.3,1)]" x-show="lines.length" x-cloak x-transition:enter-start="translate-y-full" x-transition:leave-end="translate-y-full" role="region" aria-label="{{ __('Your plants') }}">
            <div class="mx-auto max-w-[1160px] px-5 pb-4">
                <div class="bg-tulsi text-white rounded-lg p-4 flex items-center gap-4 shadow-[0_12px_32px_-12px_rgba(20,50,28,.6)]">
                    <div class="min-w-0">
                        <p class="font-semibold num" x-text="{{ Js::from(__('Plants in your list')) }} + ': ' + count"></p>
                        <p class="text-sm text-white/80 truncate" x-text="lines.map(l => l.name + ' × ' + l.qty).join(', ')"></p>
                    </div>
                    <p class="ml-auto font-display text-2xl num">₹<span x-text="total"></span></p>
                    <a :href="'https://wa.me/{{ $whatsapp }}?text=' + encodeURIComponent(message)" target="_blank" rel="noopener" class="btn bg-white text-tulsi hover:bg-tulsi-wash shrink-0"><x-icon name="message-circle" /><span class="hidden sm:inline">{{ __('Order on WhatsApp') }}</span><span class="sm:hidden">{{ __('Order') }}</span></a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.site>
