// Sideways lists (filter chips, a row of videos) as Swiper carousels, the usual modern way:
//   <div class="carousel" data-bleed="both|left"><div class="swiper" data-carousel data-gap="8">
//     <div class="swiper-wrapper"><a class="swiper-slide !w-auto">…</a> …
// - Lined up with the page: the first item starts at the container's left edge and the last one ends at
//   its right edge; on phones and tablets, while scrolling, items run on into the page margin up to the edge
//   of the main area (data-bleed: "both" sides, or only the "left" one when something sits to the right of
//   the row). On a computer the row stays inside the container, the next item peeking in at its edge.
// - Phones: no buttons; the row is swiped, and the next item peeks in at the edge to say there is more.
// - Computers (a mouse and a wide screen): ‹ › on the container's edges, each moving a page and stopping
//   with an item lined up on the left edge again; they show only when there is more that way.
// Swiper is loaded only on pages with a carousel. Words: <script type="application/json" id="carousel-words">.

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
    // A row of cards puts its buttons in its section's header (data-nav="id"), clear of the cards
    const host = (wrap.dataset.nav && document.getElementById(wrap.dataset.nav)) || wrap;
    let prev = host.querySelector(':scope > .carousel-prev');
    let next = host.querySelector(':scope > .carousel-next');
    if (!prev) {
        const w = words();
        prev = Object.assign(document.createElement('button'), { type: 'button', className: 'carousel-btn carousel-prev', innerHTML: svg('m15 18-6-6 6-6') });
        prev.setAttribute('aria-label', w.previous || 'Previous');
        next = Object.assign(document.createElement('button'), { type: 'button', className: 'carousel-btn carousel-next', innerHTML: svg('m9 18 6-6-6-6') });
        next.setAttribute('aria-label', w.next || 'Next');
        host.append(prev, next);
    }
    return { prev, next };
}

/**
 * The page margins this row may run into: measured where the row sits in the page, then the row is
 * widened over them (negative margins) and Swiper pads its first and last item by the same amounts,
 * so items line up with the container but scroll to the screen's edge.
 */
function bleed(wrap) {
    // Phones and tablets only: on a computer the row stays inside the container
    const sides = matchMedia('(max-width: 1023px)').matches ? (wrap.dataset.bleed || '') : '';
    wrap.style.marginLeft = '';
    wrap.style.marginRight = '';
    const r = wrap.getBoundingClientRect();
    // Up to the edges of the page's main area (in the member app that stops at its sidebar)
    const main = wrap.closest('main')?.getBoundingClientRect() ?? { left: 0, right: document.documentElement.clientWidth };
    const left = /\b(both|left)\b/.test(sides) ? Math.max(0, Math.round(r.left - main.left)) : 0;
    const right = /\b(both|right)\b/.test(sides) ? Math.max(0, Math.round(main.right - r.right)) : 0;
    wrap.style.marginLeft = left ? `-${left}px` : '';
    wrap.style.marginRight = right ? `-${right}px` : '';
    wrap.style.setProperty('--edge-l', `${left}px`);
    wrap.style.setProperty('--edge-r', `${right}px`);
    return { left, right };
}

async function setup(wrap) {
    const el = wrap.querySelector(':scope > [data-carousel]');
    if (!el || el.swiper) return;
    const { Swiper, modules } = await lib();
    if (el.swiper) return;
    const { prev, next } = buttons(wrap);
    const speed = reduced() ? 0 : 350;
    let edge = bleed(wrap);

    // Cards (data-cards): set columns that fill the container exactly on a computer (3, then 4 from 1280px),
    // a card and a peek of the next on a phone; ‹ › move a whole set. Chips: their own widths, dragged freely.
    const cards = el.hasAttribute('data-cards');
    const gap = Number(el.dataset.gap || 8);
    const swiper = new Swiper(el, {
        modules,
        slidesPerView: cards ? 1.18 : 'auto',
        spaceBetween: cards ? 12 : gap,
        slidesOffsetBefore: edge.left,
        slidesOffsetAfter: edge.right,
        ...(cards
            ? { breakpoints: { 640: { slidesPerView: 2.15, spaceBetween: gap }, 1024: { slidesPerView: 3, slidesPerGroup: 3, spaceBetween: gap }, 1280: { slidesPerView: 4, slidesPerGroup: 4, spaceBetween: gap } } }
            : { freeMode: { enabled: true, momentumBounce: false } }),
        watchOverflow: true,
        grabCursor: true,
        mousewheel: { forceToAxis: true },
        a11y: { enabled: true },
    });

    // Chip rows on a computer, when they don't all fit: ‹ › together at the row's right end, in their own room
    // (not over a chip), and only whole chips show. Phones: swiped, the next chip peeking in, no buttons.
    const desktop = matchMedia('(hover: hover) and (min-width: 1024px)');
    let laying = false;
    const update = () => {
        if (swiper.destroyed) return;
        const navHere = !cards && desktop.matches && !swiper.isLocked;
        if (navHere !== wrap.classList.contains('has-nav') && !laying) {
            laying = true;
            wrap.classList.toggle('has-nav', navHere);
            swiper.update();
            laying = false;
        }
        const atStart = swiper.isLocked || swiper.translate >= swiper.minTranslate() - 1;
        const atEnd = swiper.isLocked || swiper.translate <= swiper.maxTranslate() + 1;
        prev.hidden = atStart;
        next.hidden = atEnd;
        wrap.classList.toggle('is-locked', swiper.isLocked);
        if (wrap.classList.contains('has-nav')) {
            // Both buttons stay, greyed at an end; chips cut by the row's edge are hidden
            prev.disabled = atStart; next.disabled = atEnd; prev.hidden = false; next.hidden = false;
            const r = el.getBoundingClientRect();
            for (const sl of swiper.slides) { const b = sl.getBoundingClientRect(); sl.classList.toggle('is-hidden', b.left < r.left - 1 || b.right > r.right + 1); }
        } else {
            for (const sl of swiper.slides) sl.classList.remove('is-hidden');
        }
        // Buttons in a header: both always there, greyed when there is nothing that way; gone when every card fits
        if (prev.parentElement?.classList.contains('carousel-nav')) {
            prev.parentElement.hidden = swiper.isLocked;
            prev.disabled = prev.hidden; next.disabled = next.hidden;
            prev.hidden = false; next.hidden = false;
        }
    };
    const moveTo = (t) => {
        swiper.translateTo(Math.max(swiper.maxTranslate(), Math.min(swiper.minTranslate(), t)), speed, true, true);
        setTimeout(update, speed + 20);
    };
    // The container's edges on screen (the row itself runs on past them)
    const box = () => { const r = el.getBoundingClientRect(); return { left: r.left + edge.left, right: r.right - edge.right, width: r.width - edge.left - edge.right }; };

    next.addEventListener('click', () => {
        if (cards) { swiper.slideNext(speed); return; }
        const b = box();
        // The first item that doesn't fully show comes to the container's left edge
        const first = swiper.slides.find((s) => s.getBoundingClientRect().right > b.right + 1);
        if (first) moveTo(swiper.translate - (first.getBoundingClientRect().left - b.left));
    });
    prev.addEventListener('click', () => {
        if (cards) { swiper.slidePrev(speed); return; }
        const b = box();
        // A page back, stopping with a whole item on the left edge
        const first = swiper.slides.find((s) => s.getBoundingClientRect().left + b.width >= b.left - 1);
        const shift = first ? b.left - (first.getBoundingClientRect().left + b.width) : 0;
        moveTo(swiper.translate + b.width + shift);
    });

    desktop.addEventListener('change', update);
    swiper.on('setTranslate', update);
    swiper.on('slideChange', update);
    swiper.on('breakpoint', () => requestAnimationFrame(update));
    swiper.on('transitionEnd', update);
    swiper.on('lock', update);
    swiper.on('unlock', update);
    // A new window size moves the container's edges: measure again
    let timer = 0;
    addEventListener('resize', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (swiper.destroyed) return;
            edge = bleed(wrap);
            swiper.params.slidesOffsetBefore = edge.left;
            swiper.params.slidesOffsetAfter = edge.right;
            swiper.update();
            update();
        }, 120);
    }, { passive: true });
    wrap.classList.add('is-ready');
    update();

    // The selected chip in view
    const current = swiper.slides.find((s) => s.matches('[aria-current="page"], [aria-pressed="true"], [aria-selected="true"]'));
    if (current && current.getBoundingClientRect().right > box().right) {
        swiper.setTranslate(Math.max(swiper.maxTranslate(), swiper.translate - (current.getBoundingClientRect().left - box().left)));
        update();
    }
}

export function destroyCarousels(root = document) {
    root.querySelectorAll('[data-carousel]').forEach((el) => el.swiper?.destroy(true, true));
    root.querySelectorAll('.carousel').forEach((w) => { w.classList.remove('is-ready', 'is-locked'); w.style.marginLeft = ''; w.style.marginRight = ''; });
}

export function carousels() {
    const all = () => document.querySelectorAll('.carousel').forEach((w) => setup(w));
    document.addEventListener('instant:swapped', all);
    if (!document.querySelector('.carousel')) return;
    all();
    // A chip Alpine marks as chosen (the nursery's filter) after a tap: keep its row's state current
    document.addEventListener('click', () => requestAnimationFrame(() => document.querySelectorAll('[data-carousel]').forEach((el) => el.swiper?.update())));
}
