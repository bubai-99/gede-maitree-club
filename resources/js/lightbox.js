// Photos full screen over the page. Any link marked data-lightbox="group" opens its photo (data-src, or the
// link's href) instead of navigating, with the rest of the same group as previous/next: arrows, swipe,
// the keyboard (← → Esc) and a counter and caption. Without scripts the link works as before.
// The words come from the page: <script type="application/json" id="lightbox-words">.

let dialog = null;
let items = [];
let index = 0;
let opener = null;

const words = () => JSON.parse(document.getElementById('lightbox-words')?.textContent || '{}');
const digits = (s) => (document.documentElement.lang === 'bn' ? String(s).replace(/[0-9]/g, (d) => '০১২৩৪৫৬৭৮৯'[d]) : String(s));

function build() {
    const w = words();
    dialog = document.createElement('dialog');
    dialog.className = 'lightbox';
    dialog.setAttribute('aria-label', w.title || 'Photo');
    dialog.innerHTML = `
        <div class="lightbox-stage" data-stage>
            <img class="lightbox-img" alt="" data-img>
            <span class="lightbox-spinner" aria-hidden="true"></span>
        </div>
        <div class="lightbox-bar lightbox-top">
            <span class="lightbox-count num" data-count aria-live="polite"></span>
            <button type="button" class="lightbox-btn" data-close aria-label="${w.close || 'Close'}">${icon('x')}</button>
        </div>
        <button type="button" class="lightbox-btn lightbox-nav lightbox-prev" data-prev aria-label="${w.previous || 'Previous photo'}">${icon('chevron-left')}</button>
        <button type="button" class="lightbox-btn lightbox-nav lightbox-next" data-next aria-label="${w.next || 'Next photo'}">${icon('chevron-right')}</button>
        <p class="lightbox-caption" data-caption></p>`;
    document.body.append(dialog);

    dialog.querySelector('[data-close]').addEventListener('click', close);
    dialog.querySelector('[data-prev]').addEventListener('click', () => go(index - 1));
    dialog.querySelector('[data-next]').addEventListener('click', () => go(index + 1));
    // A click on the dark area around the photo closes; on the photo itself, nothing
    dialog.querySelector('[data-stage]').addEventListener('click', (e) => { if (e.target === e.currentTarget) close(); });
    dialog.addEventListener('close', () => { document.documentElement.style.overflow = ''; opener?.focus?.(); });
    dialog.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') { e.preventDefault(); go(index - 1); }
        if (e.key === 'ArrowRight') { e.preventDefault(); go(index + 1); }
    });

    // Swipe left/right to move, down to close
    let x0 = null; let y0 = null;
    const stage = dialog.querySelector('[data-stage]');
    stage.addEventListener('pointerdown', (e) => { x0 = e.clientX; y0 = e.clientY; });
    stage.addEventListener('pointerup', (e) => {
        if (x0 === null) return;
        const dx = e.clientX - x0; const dy = e.clientY - y0;
        x0 = y0 = null;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) go(index + (dx < 0 ? 1 : -1));
        else if (dy > 90 && Math.abs(dy) > Math.abs(dx)) close();
    });
}

// Icons as inline SVG (the dialog is made after the page's icons were drawn)
function icon(name) {
    const paths = {
        x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'chevron-left': '<path d="m15 18-6-6 6-6"/>',
        'chevron-right': '<path d="m9 18 6-6-6-6"/>',
    };
    return `<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name]}</svg>`;
}

function show() {
    const item = items[index];
    const img = dialog.querySelector('[data-img]');
    dialog.classList.add('is-loading');
    img.onload = () => dialog.classList.remove('is-loading');
    img.onerror = () => dialog.classList.remove('is-loading');
    img.src = item.src;
    img.alt = item.alt;
    dialog.querySelector('[data-caption]').textContent = item.caption;
    dialog.querySelector('[data-caption]').hidden = !item.caption;
    const many = items.length > 1;
    dialog.querySelector('[data-count]').textContent = many ? `${digits(index + 1)} / ${digits(items.length)}` : '';
    dialog.querySelector('[data-prev]').hidden = !many;
    dialog.querySelector('[data-next]').hidden = !many;
    // Have the neighbours ready, so moving on is instant
    for (const n of [index + 1, index - 1]) { const it = items[(n + items.length) % items.length]; if (it) new Image().src = it.src; }
}

function go(i) {
    if (items.length < 2) return;
    index = (i + items.length) % items.length;
    show();
}

function close() { dialog?.close(); }

function open(link) {
    const group = link.dataset.lightbox;
    // In page order, or by data-order where the photos are listed out of order (an album page)
    const links = [...document.querySelectorAll(`[data-lightbox="${CSS.escape(group)}"]`)]
        .map((a, i) => [a, a.dataset.order !== undefined ? Number(a.dataset.order) : i]).sort((x, y) => x[1] - y[1]).map(([a]) => a);
    items = links.map((a) => ({
        src: a.dataset.src || a.href,
        caption: a.dataset.caption || '',
        alt: a.querySelector('img')?.alt || a.dataset.caption || '',
    }));
    index = Math.max(0, links.indexOf(link));
    opener = link;
    if (!dialog) build();
    show();
    document.documentElement.style.overflow = 'hidden';
    dialog.showModal();
}

export function lightbox() {
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;
        const link = e.target.closest('a[data-lightbox]');
        if (!link) return;
        e.preventDefault();
        open(link);
    });
}
