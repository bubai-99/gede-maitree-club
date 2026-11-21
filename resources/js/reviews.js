// Reviews band (components/reviews.blade.php): the review in focus is a Swiper slider (swipe, arrows,
// dots, a slow autoplay that stops on hover, focus or for reduced motion) and the cards below are a
// linked Swiper row: tap one to show it, and the one in focus is outlined.
import Swiper from 'swiper';
import { A11y, Autoplay, Navigation, Pagination, Thumbs } from 'swiper/modules';
import 'swiper/css';

export function reviewsSlider(root) {
    const main = root.querySelector('[data-reviews-main]');
    if (!main) return;
    const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const t = (en, bn) => (document.documentElement.lang === 'bn' ? bn : en);

    const thumbsEl = root.querySelector('[data-reviews-thumbs]');
    const thumbs = thumbsEl ? new Swiper(thumbsEl, {
        modules: [A11y],
        slidesPerView: 'auto',
        spaceBetween: 12,
        watchSlidesProgress: true,
        slideToClickedSlide: true,
        a11y: { enabled: true, slideRole: 'button', itemRoleDescriptionMessage: t('review', 'রিভিউ') },
    }) : null;

    const slider = new Swiper(main, {
        modules: [A11y, Autoplay, Navigation, Pagination, Thumbs],
        speed: still ? 0 : 450,
        rewind: true,
        autoHeight: true,
        spaceBetween: 24,
        navigation: { prevEl: root.querySelector('[data-reviews-prev]'), nextEl: root.querySelector('[data-reviews-next]') },
        pagination: { el: root.querySelector('[data-reviews-dots]'), clickable: true, bulletClass: 'reviews-dot', bulletActiveClass: 'is-active' },
        thumbs: thumbs ? { swiper: thumbs, autoScrollOffset: 1 } : undefined,
        autoplay: still ? false : { delay: 7000, disableOnInteraction: true, pauseOnMouseEnter: true },
        a11y: {
            enabled: true,
            prevSlideMessage: t('Previous review', 'আগের রিভিউ'),
            nextSlideMessage: t('Next review', 'পরের রিভিউ'),
            paginationBulletMessage: t('Show review {{index}}', '{{index}} নম্বর রিভিউ দেখুন'),
            itemRoleDescriptionMessage: t('review', 'রিভিউ'),
        },
    });

    // Someone reading with the keyboard: stop moving while focus is inside.
    root.addEventListener('focusin', () => slider.autoplay?.stop());

    return slider;
}
