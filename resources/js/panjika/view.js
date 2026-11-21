// Panjika on the website and in the member app: the full page (a day, its month, pujas and wedding
// days) and the "today's panjika" box. The committee's days and the club's events come from the
// page (App\Support\PanjikaData); tithi, nakshatra, sunrise and the Bengali date from ./calc.
// Names of tithis, months and days are bilingual data, so they are kept here in both languages.
import { drawIcons } from '../icons';
import {
    PLACE, BN_MONTHS, WEEKDAYS, NAKSHATRA, PAKSHA, tithiName,
    panjikaDay, tithiAt, todayKey, addDays, parseKey, keyOf, istHM,
} from './calc';

let D = { locale: 'en', days: [], events: [], source: { en: '', bn: '' }, urls: {} };
const bn = () => D.locale === 'bn';
const L = (en, bnText) => (bn() ? bnText : en);
const bnNum = (v) => String(v).replace(/[0-9]/g, (d) => '০১২৩৪৫৬৭৮৯'[d]);
const N = (v) => (bn() ? bnNum(v) : String(v));
const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
const $ = (root, s) => root.querySelector(s);

const G_MONTHS = [
    ['January', 'জানুয়ারি'], ['February', 'ফেব্রুয়ারি'], ['March', 'মার্চ'], ['April', 'এপ্রিল'], ['May', 'মে'], ['June', 'জুন'],
    ['July', 'জুলাই'], ['August', 'আগস্ট'], ['September', 'সেপ্টেম্বর'], ['October', 'অক্টোবর'], ['November', 'নভেম্বর'], ['December', 'ডিসেম্বর'],
];
const WD_SHORT = [['Sun', 'রবি'], ['Mon', 'সোম'], ['Tue', 'মঙ্গল'], ['Wed', 'বুধ'], ['Thu', 'বৃহঃ'], ['Fri', 'শুক্র'], ['Sat', 'শনি']];

/* ---------- Formatting ---------- */
export function clock(date) {
    if (!date) return '–';
    const [h, m] = istHM(date);
    const h12 = ((h + 11) % 12) + 1;
    const mm = String(m).padStart(2, '0');
    if (!bn()) return `${h12}:${mm} ${h < 12 ? 'am' : 'pm'}`;
    const part = h < 4 ? 'রাত' : h < 6 ? 'ভোর' : h < 12 ? 'সকাল' : h < 15 ? 'দুপুর' : h < 17 ? 'বিকেল' : h < 19 ? 'সন্ধ্যা' : 'রাত';
    return `${part} ${bnNum(`${h12}:${mm}`)}`;
}
function gDate(k, { year = false, weekday = false, short = true } = {}) {
    const { y, m, d } = parseKey(k);
    const wd = new Date(Date.UTC(y, m - 1, d)).getUTCDay();
    const mon = bn() ? G_MONTHS[m - 1][1] : short ? G_MONTHS[m - 1][0].slice(0, 3) : G_MONTHS[m - 1][0];
    const s = `${N(d)} ${mon}${year ? ` ${N(y)}` : ''}`;
    return weekday ? `${L(...WEEKDAYS[wd])}, ${s}` : s;
}
const bDate = (b, year = true) => `${N(b.day)} ${L(...BN_MONTHS[b.month])}${year ? ` ${N(b.year)}` : ''}`;
const when = (date, k) => (date ? (keyOf(date) === k ? clock(date) : `${gDate(keyOf(date))}, ${clock(date)}`) : '–');

/* ---------- Moon drawing: lit fraction and side ---------- */
let moonSeq = 0;
export function moonSVG(lit, waxing, { glow = true } = {}) {
    const gid = `pjm${++moonSeq}`;
    const r = 46, cx = 50, cy = 50;
    const rx = Math.abs(1 - 2 * lit) * r;
    const crescent = lit < 0.5;
    const limb = waxing ? 1 : 0;
    const term = waxing ? (crescent ? 0 : 1) : (crescent ? 1 : 0);
    const d = lit < 0.01 ? '' : lit > 0.99 ? `M${cx} ${cy - r}a${r} ${r} 0 1 1 0 ${2 * r}a${r} ${r} 0 1 1 0 ${-2 * r}Z`
        : `M${cx} ${cy - r}A${r} ${r} 0 0 ${limb} ${cx} ${cy + r}A${rx.toFixed(2)} ${r} 0 0 ${term} ${cx} ${cy - r}Z`;
    return `<svg viewBox="0 0 100 100" class="w-full h-full" aria-hidden="true">
    <defs><radialGradient id="${gid}" cx="40%" cy="38%" r="70%"><stop offset="0" stop-color="#fff7e0"/><stop offset=".7" stop-color="#f4dfa6"/><stop offset="1" stop-color="#d9bd78"/></radialGradient></defs>
    ${glow ? `<circle cx="${cx}" cy="${cy}" r="${r + 3}" fill="rgba(255,226,168,${0.08 + lit * 0.18})"/>` : ''}
    <circle cx="${cx}" cy="${cy}" r="${r}" fill="#2b3150" stroke="rgba(255,255,255,.18)" stroke-width=".8"/>
    ${d ? `<path d="${d}" fill="url(#${gid})"/>` : ''}
  </svg>`;
}

/* ---------- What happens on a day (committee days + club events) ---------- */
const marksOn = (k) => [
    ...D.days.filter((p) => p.date === k),
    ...D.events.filter((e) => e.date === k).map((e) => ({ kind: 'event', en: e.en, bn: e.bn, url: e.url })),
];
const tithiTag = (i) => (i === 14 ? 'purnima' : i === 29 ? 'amavasya' : '');
const kindIcon = (k) => ({ puja: 'flame', parbon: 'sun', wedding: 'heart', event: 'calendar-check' }[k] || 'dot');
const chipCls = (k) => ({ puja: 'bg-sindoor text-white', parbon: 'bg-marigold text-ink', wedding: 'bg-turmeric-wash text-ink border border-turmeric/40', event: 'bg-ink text-white' }[k]);
const dotCls = (k) => ({ puja: 'bg-sindoor', parbon: 'bg-marigold', wedding: 'bg-turmeric ring-2 ring-turmeric/30', event: 'bg-ink' }[k]);

/* ---------- The full page ---------- */
let root;
let sel = null;
let view = null;

function pick(k, { keepMonth = false } = {}) {
    sel = k;
    const { y, m } = parseKey(k);
    if (!keepMonth || !view) view = { y, m };
    const url = new URL(location.href);
    if (k === todayKey()) url.searchParams.delete('date'); else url.searchParams.set('date', k);
    history.replaceState(null, '', url);
    draw();
}

function drawBanner(p) {
    $(root, '#pj-eyebrow').textContent = `${L('Panjika', 'পঞ্জিকা')} · ${L(PLACE.en, PLACE.bn)}${sel === todayKey() ? ` · ${L('Today', 'আজ')}` : ''}`;
    $(root, '#pj-title').textContent = bDate(p.bengali);
    $(root, '#pj-sub').textContent = `${L(...WEEKDAYS[p.weekday])} · ${gDate(sel, { year: true, short: false })}`;
    $(root, '#pj-moon').innerHTML = moonSVG(p.moon.lit, p.moon.waxing);
    $(root, '#pj-moon-cap').innerHTML = `${L(`${Math.round(p.moon.lit * 100)}% of the moon lit`, `চাঁদের ${bnNum(Math.round(p.moon.lit * 100))}% আলোকিত`)}<br>${L(p.moon.waxing ? 'Waxing' : 'Waning', p.moon.waxing ? 'শুক্লপক্ষ' : 'কৃষ্ণপক্ষ')}`;
    $(root, '#pj-date').value = sel;
}

function drawDay(p) {
    const tile = (icon, label, main, sub) => `<div class="card p-4 sm:p-5 min-w-0">
      <p class="flex items-center gap-2 text-[12px] sm:text-[13px] font-semibold uppercase tracking-[0.1em] text-ink-3"><i data-lucide="${icon}" class="w-4 h-4 shrink-0"></i>${label}</p>
      <p class="mt-2 font-display text-[1.3rem] sm:text-[1.7rem] leading-tight break-words">${main}</p>
      <p class="mt-1 text-[13px] sm:text-[14.5px] text-ink-2">${sub}</p>
    </div>`;
    const nx = p.nextTithi;
    const tithiSub = `${L(...PAKSHA[p.paksha])} · ${L('till', 'শেষ')} ${when(p.tithi.end, sel)}`
        + (nx && p.sun.rise && nx.end && keyOf(nx.end) === sel ? `<br>${L('then', 'তারপর')} ${L(...tithiName(nx.i))}` : '');
    $(root, '#pj-day').innerHTML = [
        tile('moon-star', L('Tithi', 'তিথি'), L(...tithiName(p.tithi.i)), tithiSub),
        tile('sparkles', L('Nakshatra', 'নক্ষত্র'), L(...NAKSHATRA[p.nakshatra.i]), `${L('till', 'শেষ')} ${when(p.nakshatra.end, sel)}`),
        tile('sunrise', L('Sunrise', 'সূর্যোদয়'), clock(p.sun.rise), `${L('Sunset', 'সূর্যাস্ত')} ${clock(p.sun.set)}`),
        tile('calendar', L('Bengali date', 'বাংলা তারিখ'), bDate(p.bengali, false), `${N(p.bengali.year)} ${L('Bangabda', 'বঙ্গাব্দ')} · ${L(...WEEKDAYS[p.weekday])}`),
    ].join('');
    const on = marksOn(sel);
    $(root, '#pj-on-day').innerHTML = on.length ? `<ul class="mt-4 flex flex-wrap gap-2">${on.map((x) => {
        const href = x.url || (x.kind === 'wedding' ? D.urls.catering : null);
        const tag = href ? 'a' : 'span';
        return `<li><${tag}${href ? ` href="${esc(href)}"` : ''} class="chip h-9 px-3.5 ${chipCls(x.kind)}"><i data-lucide="${kindIcon(x.kind)}" class="w-4 h-4"></i>${esc(L(x.en, x.bn))}${x.lagna ? ` · ${esc(L(...x.lagna))}` : ''}${href ? '<i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>' : ''}</${tag}></li>`;
    }).join('')}</ul>` : '';
}

function drawMonth() {
    const { y, m } = view;
    const first = `${y}-${String(m).padStart(2, '0')}-01`;
    const days = new Date(Date.UTC(y, m, 0)).getUTCDate();
    const lead = new Date(Date.UTC(y, m - 1, 1)).getUTCDay();
    const today = todayKey();
    const bFirst = panjikaDay(first).bengali;
    const bLast = panjikaDay(addDays(first, days - 1)).bengali;
    $(root, '#pj-month-title').textContent = `${L(...G_MONTHS[m - 1])} ${N(y)}`;
    $(root, '#pj-month-bn').textContent = bFirst.month === bLast.month ? `${L(...BN_MONTHS[bFirst.month])} ${N(bFirst.year)}`
        : `${L(...BN_MONTHS[bFirst.month])} – ${L(...BN_MONTHS[bLast.month])} ${N(bLast.year)}`;
    const cells = [];
    for (let i = 0; i < lead; i++) cells.push('<div class="pj-cell is-empty" aria-hidden="true"></div>');
    for (let d = 1; d <= days; d++) {
        const k = addDays(first, d - 1);
        const p = panjikaDay(k);
        const tag = tithiTag(p.tithi.i);
        const marks = marksOn(k);
        const label = `${gDate(k, { weekday: true, short: false })}, ${bDate(p.bengali)}, ${L(...tithiName(p.tithi.i))}${marks.length ? `, ${marks.map((x) => L(x.en, x.bn)).join(', ')}` : ''}`;
        cells.push(`<button type="button" class="pj-cell${k === sel ? ' is-sel' : ''}${k === today ? ' is-today' : ''}${p.bengali.firstDay ? ' is-bfirst' : ''}" data-pj-day="${k}" aria-pressed="${k === sel}" aria-label="${esc(label)}">
      <span class="flex items-start justify-between gap-1">
        <span class="pj-g num">${N(d)}</span>
        <span class="pj-b num">${p.bengali.firstDay ? `${N(1)} ${L(BN_MONTHS[p.bengali.month][0].slice(0, 3), BN_MONTHS[p.bengali.month][1])}` : N(p.bengali.day)}</span>
      </span>
      <span class="pj-t">${tag === 'purnima' ? '<span class="pj-moon-full"></span>' : tag === 'amavasya' ? '<span class="pj-moon-new"></span>' : ''}<span class="truncate">${L(...tithiName(p.tithi.i))}</span></span>
      ${marks.length ? `<span class="pj-dots">${marks.map((x) => `<span class="${dotCls(x.kind)}"></span>`).join('')}</span>` : ''}
    </button>`);
    }
    $(root, '#pj-cal').innerHTML = `<div class="pj-head">${WD_SHORT.map((w) => `<span>${L(...w)}</span>`).join('')}</div><div class="pj-grid">${cells.join('')}</div>`;
}

function drawLists() {
    const today = todayKey();
    const pujas = D.days.filter((p) => p.kind !== 'wedding' && p.date >= today).slice(0, 6);
    $(root, '#pj-pujas').innerHTML = pujas.map((p) => {
        const d = panjikaDay(p.date);
        let times;
        if (p.tithi !== null && p.tithi !== undefined) {
            const span = d.tithi.i === p.tithi ? d.tithi : tithiAt(new Date(Date.parse(`${p.date}T12:00:00+05:30`)));
            times = `${L(...tithiName(p.tithi))}: ${L('begins', 'শুরু')} ${gDate(keyOf(span.start))}, ${clock(span.start)} · ${L('ends', 'শেষ')} ${gDate(keyOf(span.end))}, ${clock(span.end)}`;
        } else {
            times = `${L('Sunrise', 'সূর্যোদয়')} ${clock(d.sun.rise)} · ${L('Sunset', 'সূর্যাস্ত')} ${clock(d.sun.set)}`;
        }
        const { m, d: day } = parseKey(p.date);
        return `<li class="border-b gz-hair">
      <button type="button" data-pj-day="${p.date}" class="w-full text-left py-4 grid grid-cols-[64px_1fr_auto] gap-4 items-center hover:bg-paper-2/60 -mx-2 px-2 rounded-lg">
        <span class="text-center leading-none"><span class="block font-display text-[2rem] num">${N(day)}</span><span class="block mt-1 text-[12.5px] font-semibold uppercase">${L(G_MONTHS[m - 1][0].slice(0, 3), G_MONTHS[m - 1][1])}</span></span>
        <span class="min-w-0"><span class="block font-semibold text-[17px] leading-snug">${esc(L(p.en, p.bn))}</span><span class="block text-[14px] text-ink-2">${bDate(d.bengali)} · ${L(...WEEKDAYS[d.weekday])}</span><span class="block mt-0.5 text-[13.5px] text-ink-3">${times}</span></span>
        <span class="w-3 h-3 rounded-full ${dotCls(p.kind)}" aria-hidden="true"></span>
      </button>
    </li>`;
    }).join('') || `<li class="py-6 text-ink-3">${L('No pujas entered yet.', 'এখনও কোনো পূজার দিন দেওয়া হয়নি।')}</li>`;
    const weds = D.days.filter((p) => p.kind === 'wedding' && p.date >= today).slice(0, 6);
    $(root, '#pj-weddings').innerHTML = weds.map((w) => {
        const d = panjikaDay(w.date);
        return `<li><button type="button" data-pj-day="${w.date}" class="w-full text-left flex items-center gap-3 rounded-xl bg-white/80 hover:bg-white px-4 py-3">
      <i data-lucide="heart" class="w-4 h-4 text-turmeric shrink-0"></i>
      <span class="min-w-0 flex-1"><span class="block font-semibold">${gDate(w.date, { weekday: true })}</span><span class="block text-[13.5px] text-ink-2">${bDate(d.bengali, false)}${w.lagna ? ` · ${L('Lagna', 'লগ্ন')} ${esc(L(...w.lagna))}` : ''}</span></span>
    </button></li>`;
    }).join('') || `<li class="text-ink-2">${L('No wedding days entered yet.', 'এখনও বিয়ের দিন দেওয়া হয়নি।')}</li>`;
    $(root, '#pj-note').innerHTML = `<p>${L(`Tithi, nakshatra, sunrise, sunset and the Bengali date are worked out from the positions of the sun and moon for ${PLACE.en}. A day’s tithi is the one at sunrise.`, `তিথি, নক্ষত্র, সূর্যোদয়, সূর্যাস্ত ও বাংলা তারিখ ${PLACE.bn}-র জন্য সূর্য ও চাঁদের অবস্থান থেকে হিসাব করা। দিনের তিথি মানে সূর্যোদয়ের সময়ের তিথি।`)}</p>
    <p>${esc(L(`Puja dates, wedding days and lagna come from ${D.source.en}, entered by the committee. Panjikas can differ by a day; for a puja, the committee’s notice is final.`, `পূজার দিন, বিয়ের দিন ও লগ্ন ${D.source.bn} থেকে কমিটি দেয়। পঞ্জিকাভেদে এক দিন এদিক-ওদিক হতে পারে; পূজার ব্যাপারে কমিটির নোটিসই শেষ কথা।`))}</p>`;
}

function draw() {
    const p = panjikaDay(sel);
    drawBanner(p); drawDay(p); drawMonth(); drawLists();
    drawIcons(root);
}

export function panjikaPage(el, data) {
    root = el;
    D = data;
    const asked = new URL(location.href).searchParams.get('date');
    sel = /^\d{4}-\d{2}-\d{2}$/.test(asked || '') ? asked : todayKey();
    view = { y: parseKey(sel).y, m: parseKey(sel).m };
    draw();

    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    root.addEventListener('click', (e) => {
        const day = e.target.closest('[data-pj-day]');
        if (day) {
            const fromList = !day.classList.contains('pj-cell');
            pick(day.dataset.pjDay, { keepMonth: !fromList });
            if (fromList) $(root, '#pj-title').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'center' });
            else $(root, `[data-pj-day="${sel}"].pj-cell`)?.focus();
            return;
        }
        const step = e.target.closest('[data-pj-step]');
        if (step) pick(addDays(sel, Number(step.dataset.pjStep)));
    });
    $(root, '#pj-today').addEventListener('click', () => pick(todayKey()));
    const dateIn = $(root, '#pj-date');
    dateIn.addEventListener('click', () => { try { dateIn.showPicker(); } catch { /* older browsers */ } });
    dateIn.addEventListener('change', () => { if (dateIn.value) pick(dateIn.value); });
    const month = (n) => {
        let { y, m } = view;
        m += n;
        if (m < 1) { m = 12; y--; }
        if (m > 12) { m = 1; y++; }
        view = { y, m };
        drawMonth();
        drawIcons(root);
    };
    $(root, '#pj-mprev').addEventListener('click', () => month(-1));
    $(root, '#pj-mnext').addEventListener('click', () => month(1));
    $(root, '#pj-cal').addEventListener('keydown', (e) => {
        const step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
        if (!step || !e.target.closest('.pj-cell')) return;
        e.preventDefault();
        pick(addDays(sel, step));
        $(root, `[data-pj-day="${sel}"].pj-cell`)?.focus();
    });
}

/* ---------- "Today's panjika" box (website home, member app home) ---------- */
export function todayBox(el, data) {
    D = data;
    const k = todayKey();
    const p = panjikaDay(k);
    const next = D.days.filter((x) => x.kind !== 'wedding' && x.date >= k)[0];
    const ticks = Array.from({ length: 27 }, (_, i) => {
        const ang = (i / 27) * Math.PI * 2, r1 = 46, r2 = i % 3 ? 43.5 : 41.5;
        return `<line x1="${(50 + r1 * Math.cos(ang)).toFixed(2)}" y1="${(50 + r1 * Math.sin(ang)).toFixed(2)}" x2="${(50 + r2 * Math.cos(ang)).toFixed(2)}" y2="${(50 + r2 * Math.sin(ang)).toFixed(2)}"/>`;
    }).join('');
    el.innerHTML = `<a href="${esc(D.urls.panjika)}" class="pj-cosmos group">
      <span class="pj-sky" aria-hidden="true"><span class="pj-nebula"></span><span class="pj-stars pj-s1"></span><span class="pj-stars pj-s2"></span><span class="pj-shoot"></span></span>
      <span class="pj-orbit" aria-hidden="true">
        <svg viewBox="0 0 100 100" class="pj-ring"><circle cx="50" cy="50" r="46" fill="none" stroke="rgba(255,255,255,.22)" stroke-width=".6"/><circle cx="50" cy="50" r="38" fill="none" stroke="rgba(255,226,168,.3)" stroke-width=".5" stroke-dasharray="1.5 2.5"/><g stroke="rgba(255,255,255,.45)" stroke-width=".7" stroke-linecap="round">${ticks}</g><circle cx="96" cy="50" r="1.8" fill="#ffd27a"/></svg>
        <span class="pj-orbit-moon">${moonSVG(p.moon.lit, p.moon.waxing)}</span>
      </span>
      <span class="relative min-w-0 flex-1">
        <span class="block text-[12px] font-semibold uppercase tracking-[0.14em] text-marigold">${L('Today’s panjika', 'আজকের পঞ্জিকা')}</span>
        <span class="block mt-1 font-display text-white leading-tight text-[clamp(1.5rem,3.2vw,2rem)]">${bDate(p.bengali)}</span>
        <span class="block mt-0.5 text-[14.5px] text-white/80">${gDate(k, { weekday: true, year: true, short: false })}</span>
        <span class="mt-2.5 flex flex-wrap gap-1.5 text-[13px]">
          <span class="pj-pill"><i data-lucide="moon-star" class="w-3.5 h-3.5"></i>${L(...tithiName(p.tithi.i))} ${L('till', 'শেষ')} ${when(p.tithi.end, k)}</span>
          <span class="pj-pill"><i data-lucide="sparkles" class="w-3.5 h-3.5"></i>${L(...NAKSHATRA[p.nakshatra.i])}</span>
          <span class="pj-pill"><i data-lucide="sunrise" class="w-3.5 h-3.5"></i>${clock(p.sun.rise)}<span class="opacity-50">·</span><i data-lucide="sunset" class="w-3.5 h-3.5"></i>${clock(p.sun.set)}</span>
        </span>
        ${next ? `<span class="block mt-2.5 text-[13.5px] text-white/70">${L('Next', 'সামনে')}: <span class="text-white font-semibold">${esc(L(next.en, next.bn))}</span>, ${gDate(next.date)}</span>` : ''}
      </span>
      <span class="relative hidden md:inline-flex btn btn-glass h-11 px-5 shrink-0">${L('Open panjika', 'পঞ্জিকা দেখুন')}<i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-0.5"></i></span>
    </a>`;
    drawIcons(el);
}

/* ---------- Console: tithi at sunrise beside each day, and a hint while adding one ---------- */
export function consolePanjika(el, data) {
    D = data;
    el.querySelectorAll('[data-pj-row]').forEach((row) => {
        const d = panjikaDay(row.dataset.pjRow);
        const cell = row.querySelector('[data-pj-tithi]');
        const entered = row.dataset.pjEntered === '' ? null : Number(row.dataset.pjEntered);
        cell.innerHTML = `${L(...tithiName(d.tithi.i))}${entered !== null && entered !== d.tithi.i
            ? `<span class="mt-1 flex items-start gap-1.5 text-[12.5px] text-sindoor-deep"><i data-lucide="triangle-alert" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>${L(`You entered ${tithiName(entered)[0]}. Check the panjika.`, `আপনি দিয়েছেন ${tithiName(entered)[1]}। পঞ্জিকা মিলিয়ে নিন।`)}</span>` : ''}`;
        const b = row.querySelector('[data-pj-bdate]');
        if (b) b.textContent = `${N(d.bengali.day)} ${L(...BN_MONTHS[d.bengali.month])}`;
    });

    const today = el.querySelector('[data-pj-today]');
    if (today) {
        const k = todayKey();
        const p = panjikaDay(k);
        today.querySelector('[data-pj-today-date]').textContent = bDate(p.bengali);
        today.querySelector('[data-pj-today-line]').textContent = `${L(...tithiName(p.tithi.i))} ${L('till', 'শেষ')} ${when(p.tithi.end, k)} · ${L('Sunrise', 'সূর্যোদয়')} ${clock(p.sun.rise)} · ${L('Sunset', 'সূর্যাস্ত')} ${clock(p.sun.set)}`;
    }

    el.querySelectorAll('form[data-pj-form]').forEach((form) => {
        const dateIn = form.querySelector('[name="date"]');
        const hint = form.querySelector('[data-pj-hint]');
        const tSel = form.querySelector('[name="tithi"]');
        dateIn?.addEventListener('change', () => {
            if (!dateIn.value) { hint.textContent = ''; return; }
            const d = panjikaDay(dateIn.value);
            hint.textContent = `${bDate(d.bengali)} · ${L('tithi at sunrise', 'সূর্যোদয়ের তিথি')}: ${L(...tithiName(d.tithi.i))}`;
            if (tSel && tSel.value === '' && form.querySelector('[name="kind"]:checked')?.value !== 'parbon') tSel.value = String(d.tithi.i);
        });
    });
    drawIcons(el);
}
