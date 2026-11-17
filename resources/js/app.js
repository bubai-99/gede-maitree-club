import Alpine from 'alpinejs';
import { drawIcons } from './icons';
import { razorpayCheckout } from './pay';

window.Alpine = Alpine;

Alpine.data('razorpayCheckout', razorpayCheckout);

document.addEventListener('alpine:initialized', () => drawIcons());

Alpine.start();
drawIcons();

// Installable apps (member app, committee console): register the service worker named by the layout.
const serviceWorker = document.body?.dataset.sw;

if (serviceWorker && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register(serviceWorker).catch(() => {}));

    // Signing out: forget the pages kept for offline use, so the next person on this phone cannot see them.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form instanceof HTMLFormElement && form.action.endsWith('/logout')) {
            navigator.serviceWorker.controller?.postMessage('clear-cache');
        }
    });
}
