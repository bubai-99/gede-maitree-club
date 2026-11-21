import Alpine from 'alpinejs';
import { drawIcons } from './icons';
import { razorpayCheckout } from './pay';
import { forgetThisDevice } from './forget';

window.Alpine = Alpine;

Alpine.data('razorpayCheckout', razorpayCheckout);

document.addEventListener('alpine:initialized', () => drawIcons());

Alpine.start();
drawIcons();

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

// Home page gallery strip (Swiper is only loaded on pages that have one).
const strip = document.querySelector('[data-gallery-strip]');
if (strip) import('./gallery-strip').then(({ galleryStrip }) => galleryStrip(strip));
