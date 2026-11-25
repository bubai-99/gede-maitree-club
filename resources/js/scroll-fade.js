// Sideways-scrolling rows (filter chips, a video row) marked data-scroll-fade: a soft fade on the
// side that has more to scroll to, so a chip cut in half reads as "more this way". None at either end.

function update(el) {
    const max = el.scrollWidth - el.clientWidth;
    el.classList.toggle('fade-l', max > 1 && el.scrollLeft > 2);
    el.classList.toggle('fade-r', max > 1 && el.scrollLeft < max - 2);
}

function watch(el) {
    if (el.dataset.fadeWatched) { update(el); return; }
    el.dataset.fadeWatched = '1';
    el.addEventListener('scroll', () => update(el), { passive: true });
    new ResizeObserver(() => update(el)).observe(el);
    update(el);
}

export function scrollFades() {
    const all = () => document.querySelectorAll('[data-scroll-fade]').forEach(watch);
    all();
    addEventListener('load', all); // after fonts and photos have their final widths
    document.addEventListener('instant:swapped', all);
}
