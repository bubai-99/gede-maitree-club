import { drawIcons } from './icons';
// Every form, everywhere:
// - the error summary (components/flash.blade.php) links to each field with a problem and marks it,
//   including fields that have no message of their own (the join form's family rows, say);
// - fields are checked before sending, with messages under each field in the page's language
//   (instead of the browser's grey bubbles), and a red star marks required fields (app.css);
// - a form that posts can't be sent twice by a second tap on a slow connection.

const fieldName = (key) => key.split('.').map((part, i) => (i ? `[${part}]` : part)).join('');

function markErrors() {
    const summary = document.querySelector('[data-error-summary]');
    if (!summary) return;

    let first = null;
    summary.querySelectorAll('[data-error-field]').forEach((link) => {
        const name = fieldName(link.dataset.errorField);
        const field = document.querySelector(`[name="${CSS.escape(name)}"]:not([type=hidden]), [name="${CSS.escape(name)}[]"]:not([type=hidden])`);
        if (!field) { link.removeAttribute('href'); link.classList.remove('underline'); return; }
        if (!field.id) field.id = `f-${name.replace(/[^\w-]+/g, '-')}`;
        field.setAttribute('aria-invalid', 'true');
        link.href = `#${field.id}`;
        link.addEventListener('click', (e) => {
            e.preventDefault();
            field.closest('details')?.setAttribute('open', '');
            field.focus({ preventScroll: true });
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        first ??= field;
    });

    // Open a closed panel that holds the first problem, and start the reader at the summary.
    first?.closest('details')?.setAttribute('open', '');
    summary.focus({ preventScroll: true });
    summary.scrollIntoView({ block: 'center' });

    // Typing in a field the server marked clears the mark (and its message once it is fine).
    document.addEventListener('input', (e) => {
        const el = e.target;
        if (el instanceof HTMLElement && el.getAttribute('aria-invalid') === 'true' && !document.getElementById(`${el.id}-error`)?.hasAttribute('data-client')) el.removeAttribute('aria-invalid');
    });
}

/* ---------- Checking fields before a form is sent (instead of the browser's pop-up bubbles) ---------- */
let T = {};
const CONTROLS = 'input:not([type=hidden]):not([type=submit]):not([type=button]):not([type=reset]), select, textarea';

/**
 * What the field is called: Laravel's name for it (validation.attributes, the same one the server's
 * message uses), or else its label, its group's legend or its placeholder.
 */
function labelOf(el) {
    const key = (el.name || '').replace(/\[\]$/, '').replace(/\[(\w+)\]/g, '.$1');
    const names = T.attributes || {};
    const known = names[key] ?? names[key.replace(/\.\d+\./g, '.*.').replace(/\.\d+$/, '.*')];
    if (known) return known;
    const group = el.type === 'radio' ? el.closest('fieldset')?.querySelector('legend') : null;
    const label = group || (el.id && document.querySelector(`label[for="${CSS.escape(el.id)}"]`)) || el.closest('label');
    let text = (el.getAttribute('aria-label') || label?.textContent || el.placeholder || '').replace(/\s+/g, ' ').trim();
    if (!text) return T.thisField;
    if (document.documentElement.lang !== 'bn' && /^[A-Z][a-z]/.test(text)) text = text[0].toLowerCase() + text.slice(1);
    return text;
}
const upper = (s) => s.charAt(0).toUpperCase() + s.slice(1);
/** Fill a Laravel message: :attribute / :Attribute, :min, :max, :date. */
const fill = (tpl, el, extra = {}) => {
    const name = labelOf(el);
    return (tpl || '').replace(/:Attribute/g, upper(name)).replace(/:attribute/g, name)
        .replace(/:min/g, extra.min ?? '').replace(/:max/g, extra.max ?? '').replace(/:date/g, extra.date ?? '');
};
const niceDate = (v) => { const d = new Date(`${v}T00:00:00`); return Number.isNaN(d.getTime()) ? v : d.toLocaleDateString(document.documentElement.lang === 'bn' ? 'bn-IN' : 'en-IN', { day: 'numeric', month: 'long', year: 'numeric' }); };
const num = (v) => (document.documentElement.lang === 'bn' ? String(v).replace(/[0-9]/g, (d) => '০১২৩৪৫৬৭৮৯'[d]) : String(v));

function messageFor(el) {
    const v = el.validity;
    if (v.valid) return '';
    const isDate = ['date', 'month', 'datetime-local', 'time'].includes(el.type);
    if (v.valueMissing) {
        if (el.type === 'checkbox') return fill(T.tick, el);
        return fill(el.tagName === 'SELECT' || el.type === 'radio' || el.type === 'file' ? T.choose : T.required, el);
    }
    if (v.typeMismatch) return el.type === 'email' ? fill(T.email, el) : el.type === 'url' ? fill(T.url, el) : fill(T.format, el);
    if (v.badInput) return fill(isDate ? T.date : T.number, el);
    if (v.tooShort) return fill(T.tooShort, el, { min: num(el.minLength) });
    if (v.tooLong) return fill(T.tooLong, el, { max: num(el.maxLength) });
    if (v.rangeUnderflow) return isDate ? fill(T.dateMin, el, { date: niceDate(el.min) }) : fill(T.min, el, { min: num(el.min) });
    if (v.rangeOverflow) return isDate ? fill(T.dateMax, el, { date: niceDate(el.max) }) : fill(T.max, el, { max: num(el.max) });
    if (v.stepMismatch) return fill(T.whole, el);
    if (v.patternMismatch) return el.title || fill(T.format, el);
    return fill(T.format, el);
}

/** Where the message goes: under the field, under a radio group, under a tick box's label. */
function anchorOf(el) {
    if (el.type === 'radio') return el.closest('fieldset') || el.parentElement;
    if (el.type === 'checkbox') return el.closest('label') || el;
    return el.closest('.relative')?.parentElement === el.parentElement?.parentElement && el.closest('.relative') ? el.closest('.relative') : el;
}

function errorId(el) {
    if (!el.id) el.id = `f-${(el.name || 'field').replace(/[^\w-]+/g, '-')}-${Math.random().toString(36).slice(2, 7)}`;
    return `${el.id}-error`;
}

function show(el, message) {
    const id = errorId(el);
    const group = el.type === 'radio' && el.name ? [...(el.form || document).querySelectorAll(`input[type=radio][name="${CSS.escape(el.name)}"]`)] : [el];
    let p = document.getElementById(id);
    if (!p) {
        p = document.createElement('p');
        p.id = id;
        p.className = 'field-error text-sindoor-deep';
        p.dataset.client = '';
        anchorOf(el).insertAdjacentElement('afterend', p);
    }
    p.dataset.client = '';
    p.innerHTML = '<i data-lucide="circle-alert" aria-hidden="true"></i><span></span>';
    p.querySelector('span').textContent = message;
    drawIcons(p);
    group.forEach((g) => {
        g.setAttribute('aria-invalid', 'true');
        const ids = new Set((g.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        ids.add(id);
        g.setAttribute('aria-describedby', [...ids].join(' '));
    });
}

function clear(el) {
    const group = el.type === 'radio' && el.name ? [...(el.form || document).querySelectorAll(`input[type=radio][name="${CSS.escape(el.name)}"]`)] : [el];
    const id = el.id ? `${el.id}-error` : null;
    if (id) document.getElementById(id)?.remove();
    group.forEach((g) => {
        g.removeAttribute('aria-invalid');
        const ids = (g.getAttribute('aria-describedby') || '').split(/\s+/).filter((x) => x && x !== id);
        if (ids.length) g.setAttribute('aria-describedby', ids.join(' ')); else g.removeAttribute('aria-describedby');
    });
}

function checkField(el) {
    const message = messageFor(el);
    if (message) show(el, message); else clear(el);
    return !message;
}

/**
 * Check every field inside a form or a part of it (the join form's steps use this).
 * Shows each problem under its field and puts the cursor in the first one. True when all is well.
 */
export function checkFields(root) {
    let first = null;
    const seenRadio = new Set();
    root.querySelectorAll(CONTROLS).forEach((el) => {
        if (el.disabled || el.closest('[inert]') || el.closest('template')) return;
        if (el.type === 'radio') { if (seenRadio.has(el.name)) return; seenRadio.add(el.name); }
        if (!checkField(el)) first ??= el;
    });
    if (first) {
        // A problem in a closed panel or another step: open it first
        first.closest('details')?.setAttribute('open', '');
        first.dispatchEvent(new CustomEvent('field-invalid', { bubbles: true }));
        setTimeout(() => {
            first.focus({ preventScroll: true });
            first.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
        });
    }
    return !first;
}

function checkBeforeSending() {
    const node = document.getElementById('form-messages');
    T = JSON.parse(node?.textContent || '{}');
    if (!T.required) return;

    // Our own messages replace the browser's bubbles on every form.
    const quiet = (form) => { if (form instanceof HTMLFormElement && !form.hasAttribute('data-browser-check')) form.noValidate = true; };
    document.querySelectorAll('form').forEach(quiet);
    new MutationObserver((changes) => changes.forEach((c) => c.addedNodes.forEach((n) => {
        if (n instanceof HTMLFormElement) quiet(n);
        else if (n instanceof HTMLElement) n.querySelectorAll?.('form').forEach(quiet);
    }))).observe(document.body, { childList: true, subtree: true });

    // Capture: before Alpine's own submit handlers and the double-send guard.
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-browser-check')) return;
        form.dataset.checked = '1';
        if (!checkFields(form)) { e.preventDefault(); e.stopImmediatePropagation(); }
    }, true);

    // After a first try, fix messages as people type, and check a field when they leave it.
    const recheck = (e) => {
        const el = e.target;
        if (!(el instanceof HTMLElement) || !el.matches?.(CONTROLS)) return;
        if (el.getAttribute('aria-invalid') === 'true' || (e.type === 'focusout' && el.form?.dataset.checked && el.value !== '')) checkField(el);
    };
    document.addEventListener('input', recheck);
    document.addEventListener('change', recheck);
    document.addEventListener('focusout', recheck);
}

function noDoubleSubmit() {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || (form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        if (form.dataset.sending) { e.preventDefault(); return; }
        // Wait a tick: the clicked button's name and value must go with the form first.
        setTimeout(() => {
            if (e.defaultPrevented) return;
            form.dataset.sending = '1';
            form.querySelectorAll('button[type=submit], button:not([type])').forEach((b) => { b.setAttribute('aria-busy', 'true'); b.classList.add('opacity-60', 'pointer-events-none'); });
        });
    });
    // Coming back with the browser's back button: the form can be used again.
    window.addEventListener('pageshow', () => document.querySelectorAll('form[data-sending]').forEach((f) => {
        delete f.dataset.sending;
        f.querySelectorAll('[aria-busy=true]').forEach((b) => { b.removeAttribute('aria-busy'); b.classList.remove('opacity-60', 'pointer-events-none'); });
    }));
}

export function enhanceForms() {
    markErrors();
    checkBeforeSending();
    noDoubleSubmit();
    window.checkFields = checkFields;
}
