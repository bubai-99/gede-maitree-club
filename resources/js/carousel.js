// Sideways lists (filter chips, a row of videos) as Swiper carousels:
//   <div class="carousel"><div class="swiper" data-carousel data-gap="8"><div class="swiper-wrapper">
//     <a class="swiper-slide !w-auto">…</a> …
// Items are whole or out of sight: one that doesn't fully fit is hidden until the row moves, so nothing
// shows cut in half. ‹ › (Swiper navigation, in room kept at each end) slide by the items in view and hide
// at an end; a finger or a trackpad can drag the row. The chip that is selected starts in view.
// Swiper is loaded only on pages with a carousel. Words: <script type="application/json" id="carousel-words">.

const words = () => JSON.parse(document.getElementById('carousel-words')?.textContent || '{}');
const svg = (d) => `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${d}"/></svg>`;

let SwiperLib = null;
async function lib() {
    if (!SwiperLib) {
        const [{ default: Swiper }, { A11y, Navigation, Mousewheel }] = await Promise.all([import('swiper'), import('swiper/modules'), import('swiper/css')]);
        SwiperLib = { Swiper, modules: [A11y, Navigation, Mousewheel] };
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

    const swiper = new Swiper(el, {
        modules,
        slidesPerView: 'auto',
        spaceBetween: Number(el.dataset.gap || 8),
        slidesPerGroupAuto: true,
        watchSlidesProgress: true,
        watchOverflow: true,
        grabCursor: true,
        mousewheel: { forceToAxis: true },
        navigation: { prevEl: prev, nextEl: next },
        a11y: { enabled: true, prevSlideMessage: prev.getAttribute('aria-label'), nextSlideMessage: next.getAttribute('aria-label') },
        on: {
            // Everything fits: no buttons, no room kept for them. Doesn't fit: room at each end for ‹ ›
            lock() { wrap.classList.add('is-locked'); wrap.classList.remove('has-overflow'); },
            unlock() { wrap.classList.remove('is-locked'); if (!wrap.classList.contains('has-overflow')) { wrap.classList.add('has-overflow'); requestAnimationFrame(() => { swiper.update(); fitEnd(swiper); }); } },
            resize(s) { fitEnd(s); },
        },
    });
    wrap.classList.toggle('is-locked', swiper.isLocked);
    wrap.classList.toggle('has-overflow', !swiper.isLocked);
    wrap.classList.add('is-ready');
    swiper.update();
    fitEnd(swiper);
    // The selected chip in view
    const current = [...el.querySelectorAll('.swiper-slide')].findIndex((s) => s.matches('[aria-current="page"], [aria-pressed="true"], [aria-selected="true"]'));
    if (current > 0) swiper.slideTo(current, 0);
}

/**
 * At the far end, line the row up so it starts with a whole item on the left (nothing left half shown and
 * hidden as a gap after ‹): the space that doesn't fill a whole item goes after the last one instead.
 */
function fitEnd(swiper) {
    if (!swiper || swiper.destroyed || swiper.isLocked) return;
    const width = swiper.width;
    const gap = Number(swiper.params.spaceBetween) || 0;
    let used = 0;
    const sizes = swiper.slidesSizesGrid || [];
    for (let i = sizes.length - 1; i >= 0; i--) {
        const next = used + sizes[i] + (used ? gap : 0);
        if (next > width + 0.5) break;
        used = next;
    }
    const after = Math.max(0, Math.floor(width - used));
    if (Math.abs((swiper.params.slidesOffsetAfter || 0) - after) > 1) {
        swiper.params.slidesOffsetAfter = after;
        swiper.update();
    }
}

export function destroyCarousels(root = document) {
    root.querySelectorAll('[data-carousel]').forEach((el) => el.swiper?.destroy(true, true));
    root.querySelectorAll('.carousel').forEach((w) => w.classList.remove('is-ready', 'is-locked', 'has-overflow'));
}

export function carousels() {
    const all = () => document.querySelectorAll('.carousel').forEach((w) => setup(w));
    if (!document.querySelector('.carousel')) {
        document.addEventListener('instant:swapped', all);
        return;
    }
    all();
    document.addEventListener('instant:swapped', all);
    // A chip Alpine marks as chosen (the nursery's filter) after a tap: keep its row's state current
    document.addEventListener('click', () => requestAnimationFrame(() => document.querySelectorAll('[data-carousel]').forEach((el) => el.swiper?.update())));
}
