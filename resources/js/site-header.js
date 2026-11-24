// Website header: the shadow once the page scrolls, and the phone menu (a sidebar from the right,
// components/layouts/site.blade.php). Open: the page behind doesn't scroll, Tab stays inside the
// menu, Escape or a tap outside closes it, and the cursor goes back to the menu button.

export function siteHeader() {
    return {
        open: false,
        scrolled: false,

        show() {
            this.open = true;
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.drawerClose?.focus());
        },
        close() {
            if (!this.open) return;
            this.open = false;
            document.documentElement.style.overflow = '';
            this.$refs.menuButton?.focus();
        },
        trap(e) {
            const items = [...this.$refs.drawer.querySelectorAll('a[href], button:not([disabled])')].filter((el) => el.offsetParent !== null);
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },

        init() {
            // Turning a phone sideways into desktop width: the sidebar isn't used there
            matchMedia('(min-width: 1024px)').addEventListener('change', (m) => { if (m.matches) this.close(); });
        },
    };
}

// Desktop header: the Notices bell. A dot until the newest notice has been opened here (remembered on this
// browser); opening the list clears it.
const SEEN = 'gmc-notices-seen';

export function noticeBell(newest) {
    return {
        open: false,
        unseen: false,
        init() {
            const seen = Number(localStorage.getItem(SEEN) || 0);
            this.unseen = newest > 0 && newest > seen;
        },
        toggle() {
            this.open = !this.open;
            if (this.open && newest > 0) {
                localStorage.setItem(SEEN, String(newest));
                this.unseen = false;
            }
        },
        shut() { this.open = false },
    };
}
