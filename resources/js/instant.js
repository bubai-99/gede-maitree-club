// Filters without a page reload, on any page. A page marks the part that changes with
// <div id="…" data-instant>; inside it, links to the same page (filter chips, pagination, an album and its
// next photo) and GET forms (a year, a search) load in the background and replace just that part.
// The address bar follows, so Back/Forward, reloading and sharing a link all still work, and without
// scripts the links and forms work as before. A link or form can opt out with data-instant-skip.

const busyClass = 'is-loading';
let controller = null;
let moved = false; // this page has changed the address at least once

const samePage = (url) => url.origin === location.origin && url.pathname === location.pathname;

async function swap(region, url, { push = true } = {}) {
    controller?.abort();
    controller = new AbortController();
    region.classList.add(busyClass);
    region.setAttribute('aria-busy', 'true');
    try {
        const res = await fetch(url, { headers: { Accept: 'text/html', 'X-Instant': '1' }, signal: controller.signal, credentials: 'same-origin' });
        // Signed out, an error page or a redirect somewhere else: let the browser handle it normally
        if (!res.ok || res.redirected || !res.headers.get('content-type')?.includes('text/html')) { location.href = url; return; }
        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const fresh = doc.getElementById(region.id);
        if (!fresh) { location.href = url; return; }

        region.replaceChildren(...fresh.childNodes);
        if (doc.title) document.title = doc.title;
        if (push) { history.pushState({ instant: region.id }, '', url); moved = true; }

        // Bring the results into view when the user had scrolled past their top; keep the place otherwise
        const header = document.querySelector('.site-header, .app-shell header')?.offsetHeight ?? 0;
        const anchor = region.querySelector('[data-instant-top]') ?? region;
        const top = anchor.getBoundingClientRect().top + scrollY - header - 8;
        if (scrollY > top) scrollTo({ top, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });

        region.dispatchEvent(new CustomEvent('instant:swapped', { bubbles: true }));
    } catch (e) {
        if (e.name !== 'AbortError') location.href = url;
    } finally {
        region.classList.remove(busyClass);
        region.removeAttribute('aria-busy');
    }
}

export function instantFilters() {
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest('a[href]');
        const region = a?.closest('[data-instant]');
        if (!a || !region || !region.id || a.target || a.hasAttribute('download') || a.hasAttribute('data-lightbox') || a.closest('[data-instant-skip]')) return;
        const url = new URL(a.href, location.href);
        if (!samePage(url) || (url.search === location.search && url.hash)) return;
        e.preventDefault();
        swap(region, url.href);
    });

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
