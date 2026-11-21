@php
    use App\Support\Photo;

    $content = app(\App\Support\PageContent::class);
    $whatsapp = $site->club()['whatsapp'];
    $plantData = $plants->map(fn ($p) => [
        'slug' => $p->slug,
        'name' => $p->tr('name'),
        'search' => mb_strtolower($p->tr('name').' '.$p->name.' '.$p->name_bn),
        'category' => $p->category,
        'categoryName' => $p->categoryName(),
        'pot' => (string) $p->tr('pot'),
        'price' => $p->price,
        'member' => min($p->member_price, $p->price),
        'stock' => (int) $p->stock,
        'isNew' => (bool) $p->is_new,
        'about' => (string) $p->tr('about'),
        'tip' => (string) $p->tr('tip'),
        'light' => $p->lightLabel(),
        'water' => $p->waterLabel(),
        'photo' => $p->photoUrl(900),
        'thumb' => $p->photoUrl(200),
        'photos' => $p->photos(),
        // The care guide and videos are drawn here (RichText removes any HTML typed in) and shown in the panel
        'care' => (string) \App\Support\RichText::render($p->tr('details')),
        'videos' => collect($p->embeds())->map(fn ($v) => view('partials.video-embed', ['embed' => $v['embed'], 'caption' => $v['caption']])->render())->join(''),
    ])->values();
    $nurseryData = [
        'plants' => $plantData,
        'category' => $category ?? '',
        'whatsapp' => $whatsapp,
        'text' => ['hello' => __('Hello, I would like these plants:'), 'total' => __('Total'), 'name' => __('Name'), 'member' => __('Member no.'), 'pickup' => __('Pickup'), 'memberPrice' => __('Member price')],
    ];
@endphp
<x-layouts.site seo="nursery" :title="$content->text('nursery', 'title')">
    <script type="application/json" id="nursery-data">{!! json_encode($nurseryData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
    <div class="book-nursery" x-data="nursery" @keydown.escape.window="open ? hide() : (cartOpen && closeCart())">
        <x-page-banner page="nursery" :crumb="__('Nursery')" kicker-class="text-[#a8dcb0]">
            <ul class="mt-7 flex flex-wrap gap-x-6 gap-y-2 text-[15px] text-white/85">
                <li class="flex items-center gap-2"><x-icon name="clock" class="w-4 h-4 text-marigold" /><span>{{ $content->text('nursery', 'hours') }}</span></li>
                <li class="flex items-center gap-2"><x-icon name="badge-percent" class="w-4 h-4 text-marigold" /><span>{{ __('Members :percent% off', ['percent' => digits(app(\App\Support\SiteSettings::class)->memberDiscount())]) }}</span></li>
                <li class="flex items-center gap-2"><x-icon name="map-pin" class="w-4 h-4 text-marigold" /><span>{{ $content->text('nursery', 'pickup') }}</span></li>
            </ul>
        </x-page-banner>

        {{-- How buying works: a full-width band right under the banner; three steps joined by a dashed path (across on wide screens, down on phones) --}}
        <section class="bg-tulsi-wash border-b border-tulsi/15" aria-labelledby="how-buy">
            <div class="mx-auto max-w-[1160px] px-5 py-5 sm:py-6 lg:grid lg:grid-cols-[150px_1fr] lg:gap-8 lg:items-center">
                <h2 id="how-buy" class="font-bold text-tulsi {{ app()->getLocale() === 'bn' ? 'text-[13px] lg:text-[1.15rem]' : 'text-[11.5px] uppercase tracking-[0.14em] lg:text-[13px]' }} lg:border-r lg:border-tulsi/20 lg:pr-6 lg:py-2">{{ __('How to buy') }}</h2>
                <ol class="nursery-steps mt-3 lg:mt-0 grid sm:grid-cols-3 gap-4 sm:gap-6">
                    @foreach (['sprout', 'message-circle', 'store'] as $n => $icon)
                        @php
                            $title = $content->text('nursery', 'step'.($n + 1).'_title');
                            $text = $content->text('nursery', 'step'.($n + 1).'_text');
                        @endphp
                        <li class="nursery-step relative flex sm:flex-col gap-3 sm:gap-2.5">
                            <span class="relative z-[1] shrink-0 w-8 h-8 rounded-full bg-tulsi text-white grid place-items-center font-display text-[1rem] shadow-[0_0_0_4px_var(--color-tulsi-wash)]" aria-hidden="true">{{ digits($n + 1) }}</span>
                            <div class="min-w-0 pt-0.5 sm:pt-0">
                                <p class="flex items-center gap-1.5 font-semibold text-[15.5px] leading-tight text-ink"><x-icon :name="$icon" class="w-4 h-4 text-tulsi shrink-0" />{{ $title }}</p>
                                <p class="mt-0.5 text-[14px] text-ink-2 leading-snug">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <div class="mx-auto max-w-[1160px] px-5 pt-8 pb-16">
            {{-- The plant list, with its filter bar pinned under the site header while you scroll the list
                 (it stops at the end of the list). Instant filter and search: resources/js/nursery.js --}}
            <div>
                <div x-ref="listTop" aria-hidden="true"></div>
                <div class="sticky z-20 mx-[calc(50%-50vw)] py-3 bg-paper/95 backdrop-blur-sm transition-shadow duration-200"
                     :class="stuck && 'shadow-[0_8px_18px_-14px_rgba(43,47,54,.45)]'" :style="'top: ' + headerH + 'px'" style="top: 64px">
                    <div class="mx-auto max-w-[1160px] px-5">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="flex gap-2 overflow-x-auto no-scrollbar py-0.5 -ml-5 pl-5 sm:ml-0 sm:pl-0 flex-1 min-w-0" role="group" aria-label="{{ __('Plant types') }}">
                                <a href="{{ route('nursery') }}" @click.prevent="cat = ''" :aria-pressed="(cat === '').toString()" aria-pressed="{{ $category ? 'false' : 'true' }}" class="chip h-9 px-4 text-[15px] border border-rule bg-card hover:border-ink whitespace-nowrap aria-[pressed=true]:bg-tulsi aria-[pressed=true]:text-white aria-[pressed=true]:border-tulsi">{{ __('All') }} <span class="opacity-60 num" x-text="num(count(''))">{{ digits($plants->count()) }}</span></a>
                                @foreach (app(\App\Support\PlantLists::class)->options('category') as $option => $optionName)
                                    @php
                                        $n = $plants->where('category', $option)->count();
                                    @endphp
                                    @if ($n)
                                        <a href="{{ route('nursery', ['category' => $option]) }}" @click.prevent="cat = '{{ $option }}'" :aria-pressed="(cat === '{{ $option }}').toString()" aria-pressed="{{ $category === $option ? 'true' : 'false' }}" class="chip h-9 px-4 text-[15px] border border-rule bg-card hover:border-ink whitespace-nowrap aria-[pressed=true]:bg-tulsi aria-[pressed=true]:text-white aria-[pressed=true]:border-tulsi">{{ $optionName }} <span class="opacity-60 num">{{ digits($n) }}</span></a>
                                    @endif
                                @endforeach
                            </div>
                            {{-- Search: a box on wide screens, a button that opens one on phones --}}
                            <label class="relative hidden sm:block sm:w-64 shrink-0"><span class="sr-only">{{ __('Search plants') }}</span><x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-3" /><input type="search" x-model.debounce.150ms="q" class="field own-clear h-10 pl-9 pr-9" placeholder="{{ __('Search plants') }}"><button type="button" x-show="q" x-cloak @click="q = ''" class="absolute right-1.5 top-1/2 -translate-y-1/2 w-7 h-7 grid place-items-center rounded text-ink-3 hover:text-ink" aria-label="{{ __('Clear search') }}"><x-icon name="x" class="w-4 h-4" /></button></label>
                            <button type="button" class="sm:hidden relative shrink-0 w-10 h-10 rounded-full border border-rule bg-card grid place-items-center" @click="toggleSearch()" :aria-expanded="searchOpen.toString()" aria-controls="nursery-search-m" aria-label="{{ __('Search plants') }}">
                                <x-icon name="search" class="w-[18px] h-[18px]" /><span x-show="q && ! searchOpen" x-cloak class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-tulsi"></span>
                            </button>
                        </div>
                        <div id="nursery-search-m" class="sm:hidden mt-2.5 relative" x-show="searchOpen" x-cloak>
                            <label class="sr-only" for="f-nursery-q">{{ __('Search plants') }}</label>
                            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-3" />
                            <input id="f-nursery-q" x-ref="mobileSearch" type="search" x-model.debounce.150ms="q" class="field own-clear h-10 pl-9 pr-9" placeholder="{{ __('Search plants') }}">
                            <button type="button" @click="q = ''; toggleSearch()" class="absolute right-1.5 top-1/2 -translate-y-1/2 w-7 h-7 grid place-items-center rounded text-ink-3 hover:text-ink" aria-label="{{ __('Close search') }}"><x-icon name="x" class="w-4 h-4" /></button>
                        </div>
                    </div>
                </div>
            <p class="mt-3 text-[14px] text-ink-3" aria-live="polite"><span x-text="{{ Js::from(__(':count plants')) }}.replace(':count', num(shown))">{{ __(':count plants', ['count' => digits($plants->count())]) }}</span></p>

            <ul class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 sm:gap-x-6 gap-y-8">
                @foreach ($plants as $plant)
                    @php
                        $key = $plant->samplePhotoKey();
                        $url = $key ? null : $plant->photoUrl(500);
                        $out = ! $plant->inStock();
                        $slug = $plant->slug;
                    @endphp
                    <li class="group flex flex-col" x-show="visible('{{ $slug }}')" @if ($category && $plant->category !== $category) style="display: none" @endif>
                        <button type="button" @click="show('{{ $slug }}')" class="relative block aspect-square rounded-2xl overflow-hidden bg-tulsi-wash text-left focus-visible:outline-2 focus-visible:outline-tulsi focus-visible:outline-offset-2" aria-label="{{ __(':name: details', ['name' => $plant->tr('name')]) }}">
                            @if ($key)
                                <x-photo :key="$key" :width="500" sizes="(min-width: 1024px) 260px, 50vw" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04] {{ $out ? 'grayscale opacity-70' : '' }}" />
                            @elseif ($url)
                                <img src="{{ $url }}" alt="" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04] {{ $out ? 'grayscale opacity-70' : '' }}" loading="lazy">
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
                            <span class="absolute right-2.5 bottom-2.5 chip h-7 px-2.5 text-[12.5px] bg-white/95 text-ink opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 transition-all"><x-icon name="eye" class="w-3.5 h-3.5" />{{ __('Details') }}</span>
                            @if ($key)
                                <span class="absolute left-2 bottom-2 rounded bg-black/45 px-1.5 text-[10.5px] text-white/90 opacity-0 group-hover:opacity-100 transition-opacity"><x-photo-credit :key="$key" plain /></span>
                            @endif
                        </button>
                        <div class="mt-3 flex-1">
                            <button type="button" @click="show('{{ $slug }}')" class="text-left font-semibold leading-snug hover:text-tulsi">{{ $plant->tr('name') }}</button>
                            <p class="text-[13.5px] text-ink-3 mt-0.5">{{ $plant->tr('pot') }}</p>
                        </div>
                        <div class="mt-2.5 flex items-center justify-between gap-2">
                            <span class="font-semibold text-[17px] num">{{ rupees($plant->price) }}</span>
                            @if ($out)
                                <span class="text-[13px] text-ink-3">{{ __('Back soon') }}</span>
                            @else
                                <button type="button" x-show="! qty('{{ $slug }}')" class="btn btn-sm border border-tulsi/40 text-tulsi hover:bg-tulsi-wash" @click="add('{{ $slug }}')" aria-label="{{ __('Add :name', ['name' => $plant->tr('name')]) }}"><x-icon name="plus" />{{ __('Add') }}</button>
                                <div x-show="qty('{{ $slug }}')" x-cloak class="flex items-center rounded-lg bg-tulsi text-white" role="group" aria-label="{{ $plant->tr('name') }}">
                                    <button type="button" class="w-9 h-9 grid place-items-center" @click="less('{{ $slug }}')" aria-label="{{ __('Remove one') }}"><x-icon name="minus" class="w-4 h-4" /></button>
                                    <span class="w-6 text-center font-semibold num" aria-live="polite" x-text="num(qty('{{ $slug }}'))"></span>
                                    <button type="button" class="w-9 h-9 grid place-items-center disabled:opacity-40" @click="add('{{ $slug }}')" :disabled="qty('{{ $slug }}') >= {{ (int) $plant->stock }}" aria-label="{{ __('Add one') }}"><x-icon name="plus" class="w-4 h-4" /></button>
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div x-show="shown === 0" x-cloak class="py-14 text-center">
                <p class="text-ink-2">{{ __('No plant matches. Ask at the gate; we may have it in the back.') }}</p>
                <button type="button" @click="q = ''; cat = ''" class="mt-4 btn btn-line btn-sm">{{ __('Show all plants') }}</button>
            </div>
            </div>

            <div class="mt-14 grid md:grid-cols-3 gap-5 text-[15px]">
                @foreach ([1 => 'snowflake', 2 => 'droplets', 3 => 'gift'] as $n => $icon)
                    <div class="rounded-2xl bg-tulsi-wash/60 p-6"><p class="font-semibold flex items-center gap-2"><x-icon :name="$icon" class="w-4 h-4 text-tulsi" /><span>{{ $content->text('nursery', "card{$n}_title") }}</span></p><p class="mt-1.5 text-ink-2">{{ $content->text('nursery', "card{$n}_text") }}</p></div>
                @endforeach
            </div>
        </div>

        <x-reviews kind="nursery" />

        {{-- Basket tab: on the right edge, halfway down the screen; count and total; opens the sidebar --}}
        <button type="button" x-show="items" x-cloak @click="openCart()"
                x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-x-full" x-transition:leave="transition duration-200" x-transition:leave-end="opacity-0 translate-x-full"
                class="basket-fab fixed z-30 right-0 top-1/2 -translate-y-1/2 flex flex-col items-center gap-1.5 rounded-l-2xl bg-tulsi text-white px-2.5 py-3.5 w-[4.25rem] shadow-[0_14px_34px_-12px_rgba(20,50,28,.7)] hover:bg-[#1f5029] hover:pr-3.5 transition-[padding,background-color]"
                :aria-label="{{ Js::from(__('Basket: :count plants, :total')) }}.replace(':count', num(items)).replace(':total', rupees(total))">
            <span class="relative"><x-icon name="shopping-basket" class="w-6 h-6" /><span class="absolute -top-2 -right-2.5 min-w-5 h-5 px-1 rounded-full bg-marigold text-ink text-[12px] font-bold grid place-items-center num" x-text="num(items)"></span></span>
            <span class="text-[12.5px] font-semibold leading-tight num" x-text="rupees(total)"></span>
        </button>

        {{-- Basket sidebar --}}
        <div x-show="cartOpen" x-cloak class="fixed inset-0 z-[80]" role="dialog" aria-modal="true" aria-labelledby="basket-h" @keydown.tab="trap($event, $refs.cart)">
            <div class="absolute inset-0 bg-black/45" x-show="cartOpen" x-transition.opacity @click="closeCart()"></div>
            <aside x-ref="cart" class="absolute right-0 top-0 h-full w-full max-w-[420px] bg-paper shadow-2xl flex flex-col"
                   x-show="cartOpen" x-transition:enter="transition duration-300 ease-[cubic-bezier(.16,1,.3,1)]" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full">
                <header class="flex items-center gap-3 px-5 h-16 border-b border-rule shrink-0">
                    <x-icon name="shopping-basket" class="w-5 h-5 text-tulsi" />
                    <h2 id="basket-h" class="font-display text-[1.4rem]">{{ __('Your plants') }}</h2>
                    <span class="chip h-6 px-2 text-[12.5px] bg-tulsi-wash text-tulsi num" x-text="num(items)"></span>
                    <button type="button" x-ref="cartClose" @click="closeCart()" class="ml-auto w-10 h-10 rounded-full hover:bg-paper-2 grid place-items-center" aria-label="{{ __('Close') }}"><x-icon name="x" class="w-5 h-5" /></button>
                </header>
                <form class="flex-1 min-h-0 flex flex-col" @submit.prevent="sendOrder()">
                <div class="flex-1 overflow-y-auto px-5">
                    <p x-show="! lines.length" class="py-16 text-center text-ink-3">{{ __('Your basket is empty. Tap Add on any plant.') }}</p>
                    <ul class="divide-y divide-rule">
                        <template x-for="line in lines" :key="line.slug">
                            <li class="py-4 flex gap-3">
                                <button type="button" @click="closeCart(); show(line.slug)" class="w-16 h-16 rounded-xl overflow-hidden bg-tulsi-wash shrink-0"><img :src="line.thumb" alt="" class="w-full h-full object-cover" x-show="line.thumb"></button>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start gap-2">
                                        <p class="font-semibold leading-snug flex-1" x-text="line.name"></p>
                                        <button type="button" @click="drop(line.slug)" class="text-ink-3 hover:text-sindoor-deep -mt-1 w-8 h-8 grid place-items-center rounded" :aria-label="{{ Js::from(__('Remove :name')) }}.replace(':name', line.name)"><x-icon name="trash-2" class="w-4 h-4" /></button>
                                    </div>
                                    <p class="text-[13px] text-ink-3" x-text="line.pot"></p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <div class="flex items-center rounded-lg border border-rule h-9" role="group" :aria-label="line.name">
                                            <button type="button" class="w-9 h-full grid place-items-center" @click="less(line.slug)" aria-label="{{ __('Remove one') }}"><x-icon name="minus" class="w-3.5 h-3.5" /></button>
                                            <span class="w-7 text-center font-semibold num" x-text="num(line.qty)"></span>
                                            <button type="button" class="w-9 h-full grid place-items-center disabled:opacity-35" @click="add(line.slug)" :disabled="line.qty >= line.stock" aria-label="{{ __('Add one') }}"><x-icon name="plus" class="w-3.5 h-3.5" /></button>
                                        </div>
                                        <span class="font-semibold num" x-text="rupees(line.price * line.qty)"></span>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>

                    {{-- Who is ordering: goes into the WhatsApp message (WhatsApp already shows their number);
                         the name and member number are remembered on this phone --}}
                    <fieldset x-show="lines.length" class="border-t border-rule pt-4 pb-4">
                        <legend class="float-left w-full mb-2.5 text-[14px] font-semibold">{{ __('Your details') }}</legend>
                        <div class="clear-left grid grid-cols-2 gap-x-3 gap-y-2.5 [&_.label]:text-[12.5px] [&_.label]:mb-1 [&_.field]:h-10 [&_.field]:text-[14.5px]">
                            <div class="col-span-2">
                                <label for="buyer-name" class="label">{{ __('Your name') }}</label>
                                <input id="buyer-name" name="name" class="field" x-model.trim="buyer.name" autocomplete="name" maxlength="80" required>
                            </div>
                            <div>
                                <label for="buyer-member" class="label">{{ __('Member no.') }} <span class="font-normal text-ink-3">({{ __('if any') }})</span></label>
                                <input id="buyer-member" x-ref="memberNo" name="member_no" class="field uppercase placeholder:normal-case" x-model.trim="buyer.memberNo" placeholder="MC-0101-1" maxlength="20" autocomplete="off">
                            </div>
                            <div>
                                <label for="buyer-pickup" class="label">{{ __('Pickup day') }}</label>
                                <input id="buyer-pickup" name="pickup" type="date" class="field" x-model="buyer.pickup" :min="today">
                            </div>
                        </div>
                        <p class="mt-2 text-[12px] text-ink-3">{{ __('WhatsApp sends your number with the message, so we can reply.') }}</p>
                    </fieldset>
                </div>
                <footer class="border-t border-rule px-5 pt-3 pb-3 bg-card shrink-0" x-show="lines.length">
                    {{-- The price: club members (with a member number) pay less; the club checks the number at the gate --}}
                    <div class="flex items-baseline gap-2">
                        <span class="text-ink-2" x-text="buyer.memberNo ? {{ Js::from(__('Member price')) }} : {{ Js::from(__('Total')) }}"></span>
                        <s x-show="buyer.memberNo" class="ml-auto text-[14px] text-ink-3 num" x-text="rupees(total)"></s>
                        <span class="font-display text-[1.5rem] leading-none num" :class="buyer.memberNo ? 'text-tulsi' : 'ml-auto'" x-text="rupees(buyer.memberNo ? memberTotal : total)"></span>
                    </div>
                    <p x-show="! buyer.memberNo && memberTotal < total" class="mt-1 text-[12.5px] text-ink-2">
                        {{ __('Club member?') }} <span class="text-tulsi font-semibold">{{ __('Pay') }} <span class="num" x-text="rupees(memberTotal)"></span></span>.
                        <button type="button" class="underline underline-offset-2 text-tulsi" @click="$refs.memberNo.focus()">{{ __('Add your member no.') }}</button>
                    </p>
                    <p x-show="buyer.memberNo" class="mt-1 text-[12.5px] text-tulsi">{{ __('You save') }} <span class="num" x-text="rupees(total - memberTotal)"></span> · {{ __('we check the member no. at the gate') }}</p>
                    <button type="submit" class="mt-3 btn w-full h-11 bg-tulsi text-white hover:bg-[#1f5029]"><x-icon name="message-circle" />{{ __('Order on WhatsApp') }}</button>
                    <p class="mt-2 flex items-center gap-2 text-[12px] text-ink-3">
                        <span class="flex-1">{{ __('Nothing is paid now. Collect at the club gate and pay in cash or UPI.') }}</span>
                        <button type="button" @click="closeCart()" class="shrink-0 font-semibold text-ink-2 hover:text-ink underline-offset-2 hover:underline">{{ __('Keep browsing') }}</button>
                    </p>
                </footer>
                </form>
            </aside>
        </div>

        {{-- Plant details: a large panel (photos on the left, details on the right) on wide screens,
             full screen on phones. resources/js/nursery.js --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-[85] flex items-end lg:items-center justify-center lg:p-6" role="dialog" aria-modal="true" :aria-label="current?.name" @keydown.tab="trap($event, $refs.panel)">
            <div class="absolute inset-0 bg-black/60" x-show="open" x-transition.opacity @click="hide()"></div>
            <template x-if="current">
                <div x-ref="panel" class="relative w-full lg:max-w-[1120px] h-[100dvh] lg:h-[min(88dvh,800px)] bg-paper lg:rounded-3xl shadow-2xl max-lg:overflow-y-auto max-lg:overscroll-contain lg:overflow-hidden flex flex-col lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]"
                     x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-6 lg:translate-y-0 lg:scale-95">
                    <button type="button" x-ref="panelClose" @click="hide()" class="fixed lg:absolute right-3 top-[max(.75rem,env(safe-area-inset-top))] lg:right-5 lg:top-5 z-30 w-10 h-10 rounded-full bg-white/95 text-ink grid place-items-center shadow lg:shadow-none lg:border lg:border-rule lg:bg-card hover:bg-white lg:hover:bg-paper-2" aria-label="{{ __('Close') }}"><x-icon name="x" class="w-5 h-5" /></button>

                    {{-- Photos: swipe, arrows or the thumbnails over the photo --}}
                    <div class="relative bg-tulsi-wash flex flex-col min-h-0 lg:h-full shrink-0 max-lg:h-[min(92vw,460px)]">
                        <div class="relative flex-1 min-h-0">
                            <ul x-ref="gallery" @scroll.debounce.60ms="photoIndex = Math.round($el.scrollLeft / $el.clientWidth)" class="absolute inset-0 flex overflow-x-auto snap-x snap-mandatory no-scrollbar" aria-label="{{ __('Photos') }}">
                                <template x-for="(ph, i) in current.photos" :key="i">
                                    <li class="relative shrink-0 w-full h-full snap-center"><img :src="ph.url" :alt="current.name + ' · ' + (i + 1)" class="absolute inset-0 w-full h-full object-cover" :loading="i ? 'lazy' : 'eager'"></li>
                                </template>
                            </ul>
                            <span class="absolute left-3 top-[calc(max(.75rem,env(safe-area-inset-top))+6px)] lg:top-[26px] z-10 flex gap-1.5"><span class="chip h-7 px-2.5 text-[12px] bg-marigold text-ink" x-show="current.isNew">{{ __('New') }}</span><span class="chip h-7 px-2.5 bg-black/55 text-white text-[12.5px] num" x-show="current.photos.length > 1" x-text="num(photoIndex + 1) + ' / ' + num(current.photos.length)"></span></span>
                            <template x-if="current.photos.length > 1">
                                <div>
                                    <button type="button" @click="goPhoto(photoIndex - 1)" :disabled="photoIndex === 0" class="absolute left-3 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white/90 grid place-items-center shadow disabled:opacity-0 transition-opacity" aria-label="{{ __('Previous photo') }}"><x-icon name="chevron-left" class="w-5 h-5" /></button>
                                    <button type="button" @click="goPhoto(photoIndex + 1)" :disabled="photoIndex >= current.photos.length - 1" class="absolute right-3 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white/90 grid place-items-center shadow disabled:opacity-0 transition-opacity" aria-label="{{ __('Next photo') }}"><x-icon name="chevron-right" class="w-5 h-5" /></button>
                                    {{-- Thumbnails over the photo, centred along its bottom --}}
                                    <ul class="absolute left-1/2 -translate-x-1/2 bottom-2.5 lg:bottom-3 z-10 max-w-[calc(100%-1.5rem)] flex gap-1.5 p-1 sm:p-1.5 rounded-xl bg-black/35 backdrop-blur-sm overflow-x-auto no-scrollbar" aria-label="{{ __('More photos') }}">
                                        <template x-for="(ph, i) in current.photos" :key="'t' + i">
                                            <li class="shrink-0"><button type="button" @click="goPhoto(i)" class="block w-10 h-10 sm:w-14 sm:h-14 rounded-lg overflow-hidden border-2 transition" :class="photoIndex === i ? 'border-white' : 'border-transparent opacity-70 hover:opacity-100'" :aria-label="{{ Js::from(__('Photo :n')) }}.replace(':n', num(i + 1))" :aria-current="photoIndex === i ? 'true' : null"><img :src="ph.thumb" alt="" class="w-full h-full object-cover"></button></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Details: price first, then care and videos; the quantity and basket stay at the bottom.
                         Phones scroll the whole panel (photo too); wide screens scroll only this column. --}}
                    <div class="lg:flex-1 lg:min-h-0 flex flex-col max-lg:grow">
                    <div class="grow lg:flex-1 lg:min-h-0 lg:overflow-y-auto lg:overscroll-contain">
                        <div class="p-6 sm:p-8">
                            <p class="eyebrow text-tulsi lg:pr-12" x-text="current.categoryName"></p>
                            <h2 class="mt-2 pr-10 font-display text-[2rem] sm:text-[2.3rem] leading-tight" x-text="current.name"></h2>
                            <p class="mt-3 text-[16.5px] text-ink-2 leading-relaxed" x-show="current.about" x-text="current.about"></p>

                            <ul class="mt-5 grid grid-cols-3 gap-2 text-center">
                                <li class="rounded-xl bg-paper-2 px-2 py-3" x-show="current.light"><x-icon name="sun" class="w-5 h-5 mx-auto text-tulsi" /><span class="block mt-1.5 text-[13px] font-semibold leading-tight" x-text="current.light"></span></li>
                                <li class="rounded-xl bg-paper-2 px-2 py-3" x-show="current.water"><x-icon name="droplets" class="w-5 h-5 mx-auto text-tulsi" /><span class="block mt-1.5 text-[13px] font-semibold leading-tight" x-text="current.water"></span></li>
                                <li class="rounded-xl bg-paper-2 px-2 py-3" x-show="current.pot"><x-icon name="ruler" class="w-5 h-5 mx-auto text-tulsi" /><span class="block mt-1.5 text-[13px] font-semibold leading-tight" x-text="current.pot"></span></li>
                            </ul>

                            {{-- Price and basket --}}
                            <div class="mt-6 rounded-2xl border border-rule bg-card p-4 sm:p-5">
                                <div class="flex items-end justify-between gap-3">
                                    <div>
                                        <p class="font-display text-[2rem] leading-none num" x-text="rupees(current.price)"></p>
                                        <p class="mt-1.5 flex items-center gap-1.5 text-[13.5px] text-tulsi" x-show="current.member < current.price"><x-icon name="badge-percent" class="w-4 h-4 shrink-0" /><span><span class="font-semibold" x-text="{{ Js::from(__('Club members pay :price')) }}.replace(':price', rupees(current.member))"></span> <span class="text-ink-3" x-text="'· ' + {{ Js::from(__('save :amount')) }}.replace(':amount', rupees(current.price - current.member))"></span></span></p>
                                    </div>
                                    <p class="text-[13.5px] font-semibold" :class="current.stock === 0 ? 'text-ink-3' : (current.stock <= 10 ? 'text-sindoor-deep' : 'text-tulsi')"
                                       x-text="current.stock === 0 ? {{ Js::from(__('Out of stock')) }} : (current.stock <= 10 ? {{ Js::from(__('Only :count left')) }}.replace(':count', num(current.stock)) : {{ Js::from(__('In stock')) }})"></p>
                                </div>
                                <p class="mt-3 text-[13px] text-ink-3">{{ __('Pick up at the club gate. Pay at the gate in cash or UPI.') }}</p>
                            </div>

                            <p class="mt-5 text-[14.5px] text-ink-2 flex gap-2 rounded-xl bg-marigold-wash/60 p-3.5" x-show="current.tip"><x-icon name="lightbulb" class="w-[18px] h-[18px] text-turmeric shrink-0 mt-0.5" /><span x-text="current.tip"></span></p>

                            {{-- How to care for it --}}
                            <section class="mt-8" x-show="current.care" aria-labelledby="care-h">
                                <h3 id="care-h" class="flex items-center gap-2 font-display text-[1.5rem]"><x-icon name="sprout" class="w-5 h-5 text-tulsi" />{{ __('How to care for it') }}</h3>
                                <div class="mt-3 news-prose plant-care" x-html="current.care"></div>
                            </section>

                            {{-- Videos --}}
                            <section class="mt-8" x-show="current.videos" aria-labelledby="videos-h">
                                <h3 id="videos-h" class="flex items-center gap-2 font-display text-[1.5rem]"><x-icon name="play" class="w-5 h-5 text-tulsi" />{{ __('Videos') }}</h3>
                                <div class="mt-1" x-html="current.videos"></div>
                            </section>
                        </div>
                    </div>
                    {{-- Quantity and basket: always in view at the bottom --}}
                    <div class="sticky bottom-0 z-20 shrink-0 border-t border-rule bg-card/95 backdrop-blur-sm px-5 sm:px-8 py-3 pb-[max(.75rem,env(safe-area-inset-bottom))]">
                        <template x-if="current.stock > 0">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center rounded-xl border border-rule h-12" role="group" aria-label="{{ __('Quantity') }}">
                                    <button type="button" class="w-11 h-full grid place-items-center disabled:opacity-35" @click="qtyInPanel = Math.max(1, qtyInPanel - 1)" :disabled="qtyInPanel <= 1" aria-label="{{ __('Remove one') }}"><x-icon name="minus" class="w-4 h-4" /></button>
                                    <span class="w-8 text-center font-semibold num" aria-live="polite" x-text="num(qtyInPanel)"></span>
                                    <button type="button" class="w-11 h-full grid place-items-center disabled:opacity-35" @click="qtyInPanel = Math.min(current.stock, qtyInPanel + 1)" :disabled="qtyInPanel >= current.stock" aria-label="{{ __('Add one') }}"><x-icon name="plus" class="w-4 h-4" /></button>
                                </div>
                                <button type="button" @click="putInBasket()" class="btn flex-1 min-w-0 h-12 px-3 gap-2 bg-tulsi text-white hover:bg-[#1f5029]"><x-icon name="shopping-basket" /><span class="whitespace-nowrap" x-text="qty(current.slug) ? {{ Js::from(__('Update basket')) }} : {{ Js::from(__('Add to basket')) }}"></span><span class="w-px h-5 bg-white/35 mx-0.5" aria-hidden="true"></span><span class="whitespace-nowrap num font-semibold" x-text="rupees(qtyInPanel * current.price)"></span></button>
                            </div>
                        </template>
                        <template x-if="current.stock === 0">
                            <a :href="'https://wa.me/{{ $whatsapp }}?text=' + encodeURIComponent({{ Js::from(__('Please tell me when :plant is back at the nursery.')) }}.replace(':plant', current.name))" target="_blank" rel="noopener" class="btn btn-line h-12 w-full"><x-icon name="bell" />{{ __('Tell me when it is back') }}</a>
                        </template>
                    </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.site>
