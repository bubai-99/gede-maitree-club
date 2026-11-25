import { drawIcons } from './icons';
import { destroyCarousels } from './carousel';
// Filters without a page reload, on any page. A page marks the part that changes with
// <div id="…" data-instant>; inside it, links to the same page (filter chips, pagination, an album and its
// next photo) and GET forms (a year, a search) load in the background and replace just that part.
// The address bar follows, so Back/Forward, reloading and sharing a link all still work, and without
// scripts the links and forms work as before. A link or form can opt out with data-instant-skip.

const busyClass = 'is-loading';
let controller = null;
const prefetched = new Map(); // url → { at, html } fetched when the pointer reached a link
const FRESH = 30000;
let moved = false; // this page has changed the address at least once

const samePage = (url) => url.origin === location.origin && url.pathname === location.pathname;

const load = (url, signal) => fetch(url, { headers: { Accept: 'text/html', 'X-Instant': '1' }, signal, credentials: 'same-origin' })
    .then((res) => (!res.ok || res.redirected || !res.headers.get('content-type')?.includes('text/html') ? null : res.text()));

/** A link the pointer has reached: fetch its page now, so the click shows it at once. */
function prefetch(url) {
    const hit = prefetched.get(url);
    if (hit && Date.now() - hit.at < FRESH) return;
    const entry = { at: Date.now(), html: load(url).catch(() => null) };
    prefetched.set(url, entry);
}

/** The link a pointer or finger is on, if it filters this page's instant area. */
function instantLink(target) {
    const a = target?.closest?.('a[href]');
    const region = a?.closest('[data-instant]');
    if (!a || !region || !region.id || a.target || a.hasAttribute('download') || a.hasAttribute('data-lightbox') || a.closest('[data-instant-skip]')) return null;
    const url = new URL(a.href, location.href);
    if (!samePage(url) || (url.search === location.search && url.hash)) return null;
    return { a, region, url };
}

async function swap(region, url, { push = true } = {}) {
    controller?.abort();
    controller = new AbortController();
    // Dim only when it takes a while: a quick swap shows no flash at all
    const slow = setTimeout(() => region.classList.add(busyClass), 250);
    region.setAttribute('aria-busy', 'true');
    try {
        const hit = prefetched.get(url);
        const html = await (hit && Date.now() - hit.at < FRESH ? hit.html : load(url, controller.signal));
        prefetched.delete(url);
        // Signed out, an error page or a redirect somewhere else: let the browser handle it normally
        if (html === null) { location.href = url; return; }
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const fresh = doc.getElementById(region.id);
        if (!fresh) { location.href = url; return; }

        // Change only what differs (photos and the banner that stay the same are left alone, so nothing
        // reloads or flickers); Alpine keeps the state of what stays
        // Icons drawn first, so they match the ones on the page and aren't swapped (a flicker)
        drawIcons(fresh);
        // Carousels (carousel.js) are set up again after the swap
        destroyCarousels(region);
        if (window.Alpine?.morph) {
            window.Alpine.morph(region, fresh, { updating(el, to, childrenOnly) { if (el === region) childrenOnly(); } });
        }
        else region.replaceChildren(...fresh.childNodes);
        if (doc.title) document.title = doc.title;
        if (push) { history.pushState({ instant: region.id }, '', url); moved = true; }

        // A link to a part of the page (#vol-form): go there
        const hash = new URL(url, location.href).hash;
        const target = hash ? document.getElementById(decodeURIComponent(hash.slice(1))) : null;
        if (target) {
            target.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            region.dispatchEvent(new CustomEvent('instant:swapped', { bubbles: true }));
            return;
        }
        // Bring the results into view when the user had scrolled past their top; keep the place otherwise
        const header = document.querySelector('.site-header, .app-shell header')?.offsetHeight ?? 0;
        const anchor = region.querySelector('[data-instant-top]') ?? region;
        const top = anchor.getBoundingClientRect().top + scrollY - header - 8;
        if (scrollY > top) scrollTo({ top, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });

        region.dispatchEvent(new CustomEvent('instant:swapped', { bubbles: true }));
    } catch (e) {
        if (e.name !== 'AbortError') location.href = url;
    } finally {
        clearTimeout(slow);
        region.classList.remove(busyClass);
        region.removeAttribute('aria-busy');
    }
}

export function instantFilters() {
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const hit = instantLink(e.target);
        if (!hit) return;
        e.preventDefault();
        swap(hit.region, hit.url.href);
    });
    // Fetch a filter's results as the pointer reaches it (or a finger touches it), before the click
    for (const type of ['pointerover', 'touchstart', 'focusin']) {
        document.addEventListener(type, (e) => { const hit = instantLink(e.target); if (hit) prefetch(hit.url.href); }, { passive: true });
    }

    // GET forms: submitted (a search) or changed (a year select)
    const submitForm = (form) => {
        const region = form.closest('[data-instant]');
        if (!region || !region.id || form.closest('[data-instant-skip]') || (form.getAttribute('method') || 'get').toLowerCase() !== 'get') return false;
        const url = new URL(form.getAttribute('action') || location.href, location.href);
        if (!samePage(url)) return false;
        url.search = new URLSearchParams([...new FormData(form)].filter(([, v]) => v !== '')).toString();
        swap(region, url.href);
        return true;
    };
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        if (submitForm(e.target)) e.preventDefault();
    });
    document.addEventListener('change', (e) => {
        const el = e.target;
        if (el instanceof HTMLSelectElement && el.hasAttribute('data-instant-submit') && el.form) submitForm(el.form);
    });

    // Back and Forward: show the page the address now names
    addEventListener('popstate', () => {
        if (!moved) return; // e.g. a #section link on a page that hasn't filtered
        const region = document.querySelector('[data-instant][id]');
        if (region) swap(region, location.href, { push: false });
    });
}
