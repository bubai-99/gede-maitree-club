// Sideways lists (filter chips, a row of videos) as Swiper carousels, Google style:
//   <div class="carousel"><div class="swiper" data-carousel data-gap="8"><div class="swiper-wrapper">
//     <a class="swiper-slide !w-auto">…</a> …
// The row uses its full width; ‹ › sit over its ends and show only when there is more that way. Items are
// whole or out of sight: one that doesn't fit fully between the buttons is hidden (so a button always sits
// over clear space, never over half a chip). › moves the next hidden item to just past ‹, and ‹ goes back a
// page the same way; a finger or a trackpad drags the row freely. A row of cards (data-cards) lets the arrows
// sit on a card's edge instead, like YouTube's shelves. The selected chip starts in view.
// Swiper is loaded only on pages with a carousel. Words: <script type="application/json" id="carousel-words">.

const BTN = 36; // the room a showing button (32px) takes at an end of the row

const words = () => JSON.parse(document.getElementById('carousel-words')?.textContent || '{}');
const svg = (d) => `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${d}"/></svg>`;
const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

let SwiperLib = null;
async function lib() {
    if (!SwiperLib) {
        const [{ default: Swiper }, { A11y, FreeMode, Mousewheel }] = await Promise.all([import('swiper'), import('swiper/modules'), import('swiper/css')]);
        SwiperLib = { Swiper, modules: [A11y, FreeMode, Mousewheel] };
    }
    return SwiperLib;
}

function buttons(wrap) {
    let prev = wrap.querySelector(':scope > .carousel-prev');
    let next = wrap.querySelector(':scope > .carousel-next');
    if (!prev) {
        const w = words();
        prev = Object.assign(document.createElement('button'), { type: 'button', className: 'carousel-btn carousel-prev', innerHTML: svg('m15 18-6-6 6-6') });
        prev.setAttribute('aria-label', w.previous || 'Previous');
        next = Object.assign(document.createElement('button'), { type: 'button', className: 'carousel-btn carousel-next', innerHTML: svg('m9 18 6-6-6-6') });
        next.setAttribute('aria-label', w.next || 'Next');
        wrap.append(prev, next);
    }
    return { prev, next };
}

async function setup(wrap) {
    const el = wrap.querySelector(':scope > [data-carousel]');
    if (!el || el.swiper) return;
    const { Swiper, modules } = await lib();
    if (el.swiper) return;
    const { prev, next } = buttons(wrap);
    const speed = reduced() ? 0 : 320;

    const swiper = new Swiper(el, {
        modules,
        slidesPerView: 'auto',
        spaceBetween: Number(el.dataset.gap || 8),
        freeMode: { enabled: true, momentumBounce: false },
        watchOverflow: true,
        grabCursor: true,
        mousewheel: { forceToAxis: true },
        a11y: { enabled: true },
    });

    // Where the row is now: which ends have more, and which items fit whole between the showing buttons
    const update = () => {
        if (swiper.destroyed) return;
        const locked = swiper.isLocked;
        const atStart = locked || swiper.translate >= swiper.minTranslate() - 1;
        const atEnd = locked || swiper.translate <= swiper.maxTranslate() + 1;
        prev.hidden = atStart;
        next.hidden = atEnd;
        const left = atStart ? 0 : BTN;
        const right = swiper.width - (atEnd ? 0 : BTN);
        for (const slide of swiper.slides) {
            const x = slide.offsetLeft + swiper.translate;
            // A card too wide to fit between the buttons may sit under them (Google does the same with big cards)
            const fits = (x >= left - 1 && x + slide.offsetWidth <= right + 1)
                || (big(slide) && x >= -1 && x + slide.offsetWidth <= swiper.width + 1);
            slide.classList.toggle('is-hidden', !locked && !fits);
        }
        wrap.classList.toggle('is-locked', locked);
    };
    // A card wider than the room between the buttons lines up with the row's edge, under ‹
    // Rows of cards (data-cards: the gallery's videos) let the arrows sit on a card's edge, like YouTube's shelves
    const cards = el.hasAttribute('data-cards');
    const big = (slide) => cards || slide.offsetWidth > swiper.width - 2 * BTN;
    const lead = (slide) => (big(slide) ? 0 : BTN);
    const moveTo = (translate) => {
        const t = Math.max(swiper.maxTranslate(), Math.min(swiper.minTranslate(), translate));
        swiper.translateTo(t, speed, true, true);
        // translateTo doesn't fire setTranslate at the end for every browser; settle the state after
        setTimeout(update, speed + 20);
    };

    next.addEventListener('click', () => {
        const first = swiper.slides.find((s) => s.offsetLeft + swiper.translate + s.offsetWidth > swiper.width - (big(s) ? 0 : BTN) + 1);
        if (first) moveTo(-(first.offsetLeft - lead(first)));
    });
    prev.addEventListener('click', () => {
        // Big cards: one card back
        const shown = swiper.slides.findIndex((s) => s.offsetLeft + swiper.translate >= -1);
        if (shown > 0 && big(swiper.slides[shown - 1])) {
            const back = swiper.slides[shown - 1];
            moveTo(shown - 1 === 0 ? 0 : -(back.offsetLeft - lead(back)));
            return;
        }
        const page = swiper.width - 2 * BTN;
        const from = -swiper.translate - page; // the row's offset a page back
        const first = swiper.slides.find((s) => s.offsetLeft - BTN >= from - 1);
        const target = first ? first.offsetLeft - BTN : 0;
        moveTo(target <= BTN ? 0 : -target);
    });

    swiper.on('setTranslate', update);
    swiper.on('transitionEnd', update);
    swiper.on('resize', update);
    swiper.on('lock', update);
    swiper.on('unlock', update);
    wrap.classList.add('is-ready');
    update();

    // The selected chip in view
    const current = swiper.slides.find((s) => s.matches('[aria-current="page"], [aria-pressed="true"], [aria-selected="true"]'));
    if (current && current.offsetLeft + current.offsetWidth > swiper.width) {
        swiper.setTranslate(Math.max(swiper.maxTranslate(), -(current.offsetLeft - BTN)));
        update();
    }
}

export function destroyCarousels(root = document) {
    root.querySelectorAll('[data-carousel]').forEach((el) => el.swiper?.destroy(true, true));
    root.querySelectorAll('.carousel').forEach((w) => w.classList.remove('is-ready', 'is-locked'));
}

export function carousels() {
    const all = () => document.querySelectorAll('.carousel').forEach((w) => setup(w));
    document.addEventListener('instant:swapped', all);
    if (!document.querySelector('.carousel')) return;
    all();
    // A chip Alpine marks as chosen (the nursery's filter) after a tap: keep its row's state current
    document.addEventListener('click', () => requestAnimationFrame(() => document.querySelectorAll('[data-carousel]').forEach((el) => el.swiper?.update())));
}
