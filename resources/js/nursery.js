// Website → Nursery: filter by type and search without reloading, a details panel for each plant,
// and a basket in a sidebar (kept on this phone) that sends the list to the club on WhatsApp.
// Plants and words come from the page (site/nursery.blade.php).

const KEY = 'gmc-nursery-basket';
const BUYER = 'gmc-nursery-buyer'; // name and member number, so a regular buyer types them once

export function nursery() {
    const data = JSON.parse(document.getElementById('nursery-data')?.textContent || '{}');

    return {
        plants: data.plants || [],
        t: data.text || {},
        whatsapp: data.whatsapp || '',
        cat: data.category || '',
        q: '',
        basket: {},
        buyer: { name: '', memberNo: '', pickup: '' },
        today: new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10),
        open: null, // slug of the plant in the details panel
        qtyInPanel: 1,
        photoIndex: 0,
        cartOpen: false,
        lastFocus: null,
        stuck: false, // the filter bar is pinned under the header
        headerH: 64,
        searchOpen: false, // phones: the search box under the chips

        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(KEY) || '{}');
                for (const [slug, qty] of Object.entries(saved)) {
                    const p = this.plant(slug);
                    if (p && p.stock > 0) this.basket[slug] = Math.min(p.stock, Number(qty) || 0);
                }
            } catch { /* a broken saved basket is simply dropped */ }
            this.$watch('basket', (b) => localStorage.setItem(KEY, JSON.stringify(b)), { deep: true });
            try {
                const b = JSON.parse(localStorage.getItem(BUYER) || '{}');
                this.buyer.name = String(b.name || '');
                this.buyer.memberNo = String(b.memberNo || '');
            } catch { /* start blank */ }
            this.$watch('buyer', (b) => localStorage.setItem(BUYER, JSON.stringify({ name: b.name, memberNo: b.memberNo })), { deep: true });
            this.$watch('cat', (c) => {
                const url = new URL(location.href);
                if (c) url.searchParams.set('category', c); else url.searchParams.delete('category');
                history.replaceState(null, '', url);
                this.toListTop();
            });
            this.$watch('q', () => this.toListTop());

            // Pinned filter bar: sits right under the site header; "stuck" adds its shadow.
            const header = document.querySelector('.site-header');
            const measure = () => { this.headerH = header ? header.offsetHeight : 0; };
            measure();
            window.addEventListener('resize', measure, { passive: true });
            const check = () => { this.stuck = this.$refs.listTop.getBoundingClientRect().top < this.headerH; };
            window.addEventListener('scroll', check, { passive: true });
            check();
        },

        plant(slug) { return this.plants.find((p) => p.slug === slug) },
        /** After a new filter or search while scrolled down the list, show the results from the first plant. */
        toListTop() {
            if (!this.stuck) return;
            const top = this.$refs.listTop.getBoundingClientRect().top + window.scrollY - this.headerH;
            window.scrollTo({ top, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        },
        toggleSearch() {
            this.searchOpen = !this.searchOpen;
            if (this.searchOpen) this.$nextTick(() => this.$refs.mobileSearch?.focus());
        },
        visible(slug) {
            const p = this.plant(slug);
            const q = this.q.trim().toLowerCase();
            return !!p && (!this.cat || p.category === this.cat) && (!q || p.search.includes(q));
        },
        get shown() { return this.plants.filter((p) => this.visible(p.slug)).length },
        count(cat) { return cat ? this.plants.filter((p) => p.category === cat).length : this.plants.length },

        qty(slug) { return this.basket[slug] || 0 },
        add(slug, n = 1) { const p = this.plant(slug); if (p) this.basket[slug] = Math.min(p.stock, this.qty(slug) + n) },
        less(slug) { if (this.qty(slug) > 1) this.basket[slug]--; else delete this.basket[slug] },
        drop(slug) { delete this.basket[slug] },
        get lines() { return Object.entries(this.basket).map(([slug, qty]) => ({ ...this.plant(slug), qty })).filter((l) => l.slug) },
        get items() { return this.lines.reduce((t, l) => t + l.qty, 0) },
        get total() { return this.lines.reduce((t, l) => t + l.price * l.qty, 0) },
        get memberTotal() { return this.lines.reduce((t, l) => t + l.member * l.qty, 0) },
        rupees(n) { return '₹' + Number(n).toLocaleString(document.documentElement.lang === 'bn' ? 'bn-IN' : 'en-IN') },
        num(n) { return Number(n).toLocaleString(document.documentElement.lang === 'bn' ? 'bn-IN' : 'en-IN') },
        get orderLink() {
            const b = this.buyer;
            const pickup = b.pickup ? new Date(`${b.pickup}T00:00:00`).toLocaleDateString(document.documentElement.lang === 'bn' ? 'bn-IN' : 'en-IN', { weekday: 'short', day: 'numeric', month: 'short' }) : '';
            const text = [
                this.t.hello,
                ...this.lines.map((l) => `• ${l.name} × ${l.qty}`),
                `${this.t.total}: ${this.rupees(this.total)}`,
                b.memberNo ? `${this.t.memberPrice}: ${this.rupees(this.memberTotal)}` : null,
                '',
                `${this.t.name}: ${b.name}`,
                b.memberNo ? `${this.t.member}: ${b.memberNo.toUpperCase()}` : null,
                pickup ? `${this.t.pickup}: ${pickup}` : null,
            ].filter((x) => x !== null).join('\n');
            return `https://wa.me/${this.whatsapp}?text=${encodeURIComponent(text)}`;
        },

        /** The basket form: forms.js has already checked the name (a missing one stops the submit). */
        sendOrder() {
            if (!this.lines.length) return;
            window.open(this.orderLink, '_blank', 'noopener');
        },

        // Details panel
        get current() { return this.open ? this.plant(this.open) : null },
        show(slug) {
            this.lastFocus = document.activeElement;
            this.open = slug;
            this.photoIndex = 0;
            this.qtyInPanel = Math.max(1, this.qty(slug) || 1);
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.panelClose?.focus());
        },
        hide() {
            this.open = null;
            document.documentElement.style.overflow = this.cartOpen ? 'hidden' : '';
            this.lastFocus?.focus?.();
        },
        goPhoto(i) {
            const g = this.$refs.gallery;
            if (!g || !this.current) return;
            this.photoIndex = Math.max(0, Math.min(this.current.photos.length - 1, i));
            g.scrollTo({ left: this.photoIndex * g.clientWidth, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        },
        putInBasket() {
            const p = this.current;
            if (!p) return;
            this.basket[p.slug] = Math.min(p.stock, this.qtyInPanel);
            this.hide();
            this.openCart();
        },

        // Basket sidebar
        openCart() {
            this.lastFocus = this.lastFocus && this.open ? this.lastFocus : document.activeElement;
            this.cartOpen = true;
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.cartClose?.focus());
        },
        closeCart() {
            this.cartOpen = false;
            document.documentElement.style.overflow = '';
            this.lastFocus?.focus?.();
        },
        // Keep Tab inside an open panel
        trap(e, box) {
            const f = [...box.querySelectorAll('a[href], button:not([disabled]), input, select, textarea')].filter((el) => el.offsetParent !== null);
            if (!f.length) return;
            const first = f[0], last = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },
    };
}
