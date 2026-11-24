// Console → Panjika → "Fill a year's festivals": works out a Bengali year's pujas and parbon in the browser
// (resources/js/panjika/festivals.js, loaded only when asked) and lists them to check and pick before saving.

export function panjikaFill(entered, firstYear, words) {
    const today = new Date(Date.now() + 330 * 60000).toISOString().slice(0, 10); // IST
    const bn = document.documentElement.lang === 'bn';
    const done = new Set(entered); // 'YYYY-MM-DD|English name' already in the list

    return {
        year: firstYear,
        rows: [],
        busy: false,
        failed: false,
        years: [firstYear, firstYear + 1],

        yearLabel(y) {
            const n = (v) => (bn ? String(v).replace(/[0-9]/g, (d) => '০১২৩৪৫৬৭৮৯'[d]) : String(v));
            return `${n(y)} (${words.aprilTo.replace(':from', n(y + 593)).replace(':to', n(y + 594))})`;
        },
        async work() {
            this.busy = true;
            this.failed = false;
            this.rows = [];
            try {
                const { festivalsForYear } = await import('./panjika/festivals');
                // Let the "working" state show before the calculation holds the page for a moment
                await new Promise((r) => setTimeout(r, 30));
                this.rows = festivalsForYear(Number(this.year)).map((f) => {
                    const exists = done.has(`${f.date}|${f.name}`);
                    const past = f.date < today;
                    return { ...f, exists, past, pick: !exists && !past };
                });
            } catch {
                this.failed = true;
            }
            this.busy = false;
        },
        get picked() { return this.rows.filter((r) => r.pick) },
        dateLabel(k) {
            return new Date(`${k}T00:00:00Z`).toLocaleDateString(bn ? 'bn-IN' : 'en-IN', { timeZone: 'UTC', weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        },
        name(r) { return bn ? r.name_bn : r.name },
        timing(r) { return words.timing[r.when] || '' },
        submitLabel() {
            const n = this.picked.length;
            return (n === 1 ? words.addOne : words.addMany).replace(':count', bn ? String(n).replace(/[0-9]/g, (d) => '০১২৩৪৫৬৭৮৯'[d]) : n);
        },
    };
}
