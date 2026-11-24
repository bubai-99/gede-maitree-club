import Alpine from 'alpinejs';
import { drawIcons } from './icons';
import { razorpayCheckout } from './pay';
import { nursery } from './nursery';
import { siteHeader, noticeBell } from './site-header';
import { setupInstall } from './install';
import { forgetThisDevice } from './forget';
import { enhanceForms } from './forms';
import { imageLoader } from './images';

window.Alpine = Alpine;

Alpine.data('razorpayCheckout', razorpayCheckout);
Alpine.data('nursery', nursery);
Alpine.data('siteHeader', siteHeader);
Alpine.data('noticeBell', noticeBell);
setupInstall(Alpine);

document.addEventListener('alpine:initialized', () => drawIcons());

Alpine.start();
drawIcons();

// Icons in content Alpine adds later (x-if, x-for: a details panel, basket rows) are drawn as they appear.
new MutationObserver((changes) => {
    for (const change of changes) {
        for (const node of change.addedNodes) {
            if (node instanceof Element && (node.matches('i[data-lucide]') || node.querySelector('i[data-lucide]'))) drawIcons(node.parentElement ?? node);
        }
    }
}).observe(document.body, { childList: true, subtree: true });
enhanceForms();
imageLoader();

// Installable apps (member app, committee console): register the service worker named by the layout.
const serviceWorker = document.body?.dataset.sw;

if (serviceWorker && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register(serviceWorker).catch(() => {}));
}

// Signing out, or a sign-in page (the session may have ended without a sign-out): forget the pages
// kept for offline use and the scanner's pass lists, so the next person on this phone cannot see them.
document.addEventListener('submit', (event) => {
    const form = event.target;
    // getAttribute, not form.action: CMS forms have buttons named "action", which shadow it.
    if (form instanceof HTMLFormElement && (form.getAttribute('action') ?? '').endsWith('/logout')) forgetThisDevice();
});

if (document.body?.dataset.signedOut !== undefined) forgetThisDevice();

// Reviews band: Swiper slider (loaded only where there are reviews).
const reviewBands = document.querySelectorAll('[data-reviews]');
if (reviewBands.length) import('./reviews').then(({ reviewsSlider }) => reviewBands.forEach((el) => reviewsSlider(el)));

// Home page gallery strip (Swiper is only loaded on pages that have one).
const strip = document.querySelector('[data-gallery-strip]');
if (strip) import('./gallery-strip').then(({ galleryStrip }) => galleryStrip(strip));

// Panjika (the astronomy library is only loaded where a panjika is shown). Data: App\Support\PanjikaData.
const panjikaData = () => JSON.parse(document.getElementById('panjika-data')?.textContent || '{}');
const panjikaPage = document.querySelector('[data-panjika-page]');
const panjikaBoxes = document.querySelectorAll('[data-panjika-today]');
const panjikaConsole = document.querySelector('[data-panjika-console]');
if (panjikaPage || panjikaBoxes.length || panjikaConsole) {
    import('./panjika/view').then((pj) => {
        const data = panjikaData();
        if (panjikaPage) pj.panjikaPage(panjikaPage, data);
        panjikaBoxes.forEach((el) => pj.todayBox(el, data));
        if (panjikaConsole) pj.consolePanjika(panjikaConsole, data);
    });
}
