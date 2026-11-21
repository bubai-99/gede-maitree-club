// Every form, everywhere:
// - the error summary (components/flash.blade.php) links to each field with a problem and marks it,
//   including fields that have no message of their own (the join form's family rows, say);
// - the browser's own "please fill in" messages speak Bengali on the Bengali site;
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

    // Typing in a marked field clears its red mark.
    document.addEventListener('input', (e) => {
        if (e.target instanceof HTMLElement && e.target.getAttribute('aria-invalid') === 'true') e.target.removeAttribute('aria-invalid');
    });
}

function localMessages() {
    const node = document.getElementById('form-messages');
    if (!node) return;
    const t = JSON.parse(node.textContent || '{}');
    const say = (s, el) => s.replace(':min', el.getAttribute('min') ?? el.getAttribute('minlength') ?? '').replace(':max', el.getAttribute('max') ?? el.getAttribute('maxlength') ?? '');

    document.addEventListener('invalid', (e) => {
        const el = e.target;
        if (!(el instanceof HTMLInputElement || el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement)) return;
        const v = el.validity;
        const msg = v.valueMissing ? (el instanceof HTMLSelectElement ? t.selectMissing : el.type === 'checkbox' || el.type === 'radio' ? t.checkMissing : t.valueMissing)
            : v.typeMismatch ? (el.type === 'email' ? t.email : el.type === 'url' ? t.url : t.pattern)
                : v.tooShort ? say(t.tooShort, el) : v.tooLong ? say(t.tooLong, el)
                    : v.rangeUnderflow ? say(t.rangeUnderflow, el) : v.rangeOverflow ? say(t.rangeOverflow, el)
                        : v.badInput ? t.number : v.patternMismatch ? t.pattern : '';
        el.setCustomValidity(msg || '');
    }, true);
    // The custom message must be cleared as soon as the value changes, or the field stays invalid.
    document.addEventListener('input', (e) => { if (typeof e.target.setCustomValidity === 'function') e.target.setCustomValidity(''); }, true);
    document.addEventListener('change', (e) => { if (typeof e.target.setCustomValidity === 'function') e.target.setCustomValidity(''); }, true);
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
    localMessages();
    noDoubleSubmit();
}
