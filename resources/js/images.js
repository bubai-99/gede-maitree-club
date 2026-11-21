// Photos in the page content start hidden behind a soft shimmer (app.css) and fade in once loaded.
// Works for photos added later too (a details panel, basket rows) and for a photo whose src changes.
const loaded = (img) => img.classList.add('is-loaded');

function watch(img) {
    if (!(img instanceof HTMLImageElement) || img.dataset.watched) return;
    img.dataset.watched = '1';
    if (img.complete && img.naturalWidth > 0) { loaded(img); return; }
    img.addEventListener('load', () => loaded(img));
    img.addEventListener('error', () => loaded(img)); // a broken photo shows its alt text, not a shimmer forever
}

export function imageLoader() {
    document.querySelectorAll('img').forEach(watch);
    new MutationObserver((changes) => {
        for (const c of changes) {
            if (c.type === 'attributes' && c.target instanceof HTMLImageElement) {
                // A new picture in the same <img> (a gallery): shimmer again until it has loaded
                c.target.classList.remove('is-loaded');
                if (c.target.complete && c.target.naturalWidth > 0) loaded(c.target);
                else { delete c.target.dataset.watched; watch(c.target); }
                continue;
            }
            c.addedNodes.forEach((n) => {
                if (n instanceof HTMLImageElement) watch(n);
                else if (n instanceof Element) n.querySelectorAll('img').forEach(watch);
            });
        }
    }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['src'] });
}
