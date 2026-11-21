// Home page "From the gallery": a slow, continuous strip of photos.
//
// Swiper lays the photos out and handles dragging and swiping; a small animation loop moves the
// strip at an even pace. The photos are rendered twice (the second set hidden from screen
// readers), so when the first set has scrolled past the strip jumps back by exactly one set and
// the movement never ends. It stops while a pointer, a finger or keyboard focus is on it, and
// stands still for people who ask for less motion.
import Swiper from 'swiper';
import { A11y, FreeMode } from 'swiper/modules';
import 'swiper/css';

/** Pixels a second. */
const PACE = 38;

export function galleryStrip(el) {
    const swiper = new Swiper(el, {
        modules: [A11y, FreeMode],
        slidesPerView: 'auto',
        spaceBetween: 16,
        grabCursor: true,
        freeMode: { enabled: true, momentum: false },
        a11y: { enabled: true },
    });

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return swiper;

    const count = Number(el.dataset.galleryStrip) || swiper.slides.length / 2;
    const setWidth = () => (swiper.slidesGrid[count] ?? 0) - (swiper.slidesGrid[0] ?? 0);

    let held = false;
    let last = 0;
    const hold = () => { held = true; };
    const release = () => {
        if (el.matches(':hover') || el.contains(document.activeElement)) return;
        held = false;
    };

    el.addEventListener('mouseenter', hold);
    el.addEventListener('mouseleave', release);
    el.addEventListener('focusin', hold);
    el.addEventListener('focusout', () => setTimeout(release));
    swiper.on('touchStart', hold);
    swiper.on('touchEnd', () => setTimeout(release, 600));

    const tick = (now) => {
        const dt = last ? Math.min(now - last, 100) : 0;
        last = now;
        const width = setWidth();

        if (!held && width > 0) {
            let x = swiper.getTranslate() - (PACE * dt) / 1000;
            if (x <= -width) x += width;
            if (x > 0) x -= width;
            swiper.setTransition(0);
            swiper.setTranslate(x);
        }

        requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);

    return swiper;
}
