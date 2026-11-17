// Gate scanner (/admin/scan): a plain ES module, offline first.
//
// - The event's pass list (SHA-256 hashes of the pass tokens, family numbers, member card numbers,
//   head counts; no names) is downloaded into IndexedDB when the event is picked.
// - Online, every scan is checked and recorded by the server (it locks the pass row, so two gates
//   cannot both admit the same pass). Offline, the phone checks against its own list, shows the
//   result at once and queues the scan; the queue uploads by itself when there is signal. The
//   server keeps the earliest admission of a pass (first scan wins) and reports duplicates.
// - Camera reading uses the Barcode Detection API through the `barcode-detector` ponyfill
//   (ZXing-C++ WebAssembly). The .wasm file is bundled by Vite and served from this site, so it is
//   cached by the service worker and scanning keeps working without signal.
import { BarcodeDetector, prepareZXingModule } from 'barcode-detector/ponyfill';
import wasmUrl from 'zxing-wasm/reader/zxing_reader.wasm?url';
import { drawIcons } from './icons';

const cfgEl = document.getElementById('scan-config');
const cfg = cfgEl ? JSON.parse(cfgEl.textContent) : null;
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* ---------- Words, numbers, times ---------- */
const bn = cfg?.locale === 'bn';
const numFmt = new Intl.NumberFormat(bn ? 'bn-IN' : 'en-IN');
const timeFmt = new Intl.DateTimeFormat(bn ? 'bn-IN' : 'en-IN', { hour: 'numeric', minute: '2-digit' });
const n = (v) => numFmt.format(v);
const at = (iso) => timeFmt.format(iso ? new Date(iso) : new Date());
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
function t(key, params = {}) {
  let s = cfg?.t?.[key] ?? key;
  for (const [k, v] of Object.entries(params)) s = s.replaceAll(`:${k}`, typeof v === 'number' ? n(v) : String(v));
  return esc(s);
}
const icon = (name, cls = 'w-5 h-5') => `<i data-lucide="${name}" class="${cls}" aria-hidden="true"></i>`;

/* ---------- IndexedDB ---------- */
const DB_NAME = 'gmc-scanner';
let dbPromise;
function db() {
  dbPromise ??= new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, 1);
    req.onupgradeneeded = () => {
      const d = req.result;
      d.createObjectStore('lists', { keyPath: 'slug' });
      d.createObjectStore('queue', { keyPath: 'seq', autoIncrement: true });
      d.createObjectStore('used', { keyPath: 'k' });
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
  return dbPromise;
}
async function tx(store, mode, fn) {
  const d = await db();
  return new Promise((resolve, reject) => {
    const x = d.transaction(store, mode);
    const s = x.objectStore(store);
    let out;
    Promise.resolve(fn(s)).then((v) => { out = v; });
    x.oncomplete = () => resolve(out);
    x.onerror = () => reject(x.error);
    x.onabort = () => reject(x.error);
  });
}
const req2p = (r) => new Promise((resolve, reject) => { r.onsuccess = () => resolve(r.result); r.onerror = () => reject(r.error); });
const idbGet = (store, key) => tx(store, 'readonly', (s) => req2p(s.get(key)));
const idbPut = (store, value) => tx(store, 'readwrite', (s) => { s.put(value); });
const idbDel = (store, key) => tx(store, 'readwrite', (s) => { s.delete(key); });
const idbAll = (store) => tx(store, 'readonly', (s) => req2p(s.getAll()));

/* ---------- State ---------- */
const S = {
  evt: cfg?.events?.find((e) => e.slug === cfg.current) ?? null,
  gate: localStorage.getItem('gmc-gate') || '',
  sound: localStorage.getItem('gmc-scan-sound') !== 'off',
  online: navigator.onLine,
  list: null, // { slug, generated_at, passes: [...] }
  stats: { inside: 0, families: 0, denied: 0 },
  queued: 0,
  log: [], // recent scans this shift (newest first)
  shiftStart: localStorage.getItem('gmc-shift-start') || new Date().toISOString(),
  busy: false,
  sessionEnded: false,
};
localStorage.setItem('gmc-shift-start', S.shiftStart);

/* ---------- Server ---------- */
async function api(url, { method = 'GET', body, timeout = 5000 } = {}) {
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), timeout);
  try {
    const res = await fetch(url, {
      method,
      signal: ctrl.signal,
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
      body: body ? JSON.stringify(body) : undefined,
    });
    if (res.status === 401 || res.status === 419) { S.sessionEnded = true; throw Object.assign(new Error('session'), { offline: true }); }
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (res.status >= 500 || res.status === 429) throw Object.assign(new Error('server'), { offline: true });
      throw Object.assign(new Error(data.message || 'error'), { status: res.status, data });
    }
    S.online = true;
    S.sessionEnded = false;
    return data;
  } catch (e) {
    if (e.name === 'AbortError' || e instanceof TypeError) { S.online = false; throw Object.assign(new Error('offline'), { offline: true }); }
    throw e;
  } finally {
    clearTimeout(timer);
  }
}
const scanUrl = (uuid) => cfg.urls.scan.replace('__UUID__', uuid);
function uuid() {
  if (crypto.randomUUID) return crypto.randomUUID();
  const b = crypto.getRandomValues(new Uint8Array(16));
  b[6] = (b[6] & 0x0f) | 0x40; b[8] = (b[8] & 0x3f) | 0x80;
  const h = [...b].map((x) => x.toString(16).padStart(2, '0')).join('');
  return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}
async function sha256(text) {
  if (!crypto.subtle) return null;
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
  return [...new Uint8Array(buf)].map((x) => x.toString(16).padStart(2, '0')).join('');
}

/* ---------- Pass list (offline) ---------- */
async function loadList() {
  if (!S.evt) return;
  S.list = (await idbGet('lists', S.evt.slug).catch(() => null)) ?? null;
  if (S.list?.stats) S.stats = S.list.stats;
  await refreshList();
}
async function refreshList() {
  if (!S.evt) return;
  try {
    const data = await api(S.evt.urls.passes, { timeout: 15000 });
    S.list = { slug: S.evt.slug, generated_at: data.generated_at, cancelled: data.event.cancelled, passes: data.passes, stats: data.stats };
    S.stats = data.stats;
    await idbPut('lists', S.list);
  } catch { /* keep the copy on the phone */ }
  paint();
}
async function markUsed(hashOrFamily, used) {
  const k = `${S.evt.slug}|${hashOrFamily}`;
  if (used) await idbPut('used', { k, at: new Date().toISOString() }); else await idbDel('used', k);
}
const isUsedHere = async (key) => !!(await idbGet('used', `${S.evt.slug}|${key}`).catch(() => null));

/** What the phone's own list says about a scan: { result, family_no, max } */
async function localCheck(op) {
  const passes = S.list?.passes ?? [];
  let pass = null;
  if (op.code) {
    const code = op.code.trim();
    if (code.startsWith('GMP1.')) {
      const h = await sha256(code.slice(5));
      pass = h ? passes.find((p) => p.h === h) : null;
      if (!pass) return { result: 'invalid' };
    } else if (code.startsWith('GMC1:')) {
      const memberNo = code.split(':')[1] ?? '';
      pass = passes.find((p) => p.m.includes(memberNo));
      if (!pass) return { result: memberNo ? 'no_pass' : 'invalid' };
    } else {
      return { result: 'invalid' };
    }
  } else {
    pass = passes.find((p) => p.f === op.family_no);
    if (!pass) return { result: 'no_pass', family_no: op.family_no };
  }
  if (S.list?.cancelled || pass.s !== 'active') return { result: 'cancelled', family_no: pass.f, max: pass.n };
  if (pass.u || await isUsedHere(pass.h)) return { result: 'already', family_no: pass.f, max: pass.n, hash: pass.h };
  return { result: 'allowed', family_no: pass.f, max: pass.n, hash: pass.h };
}

/* ---------- Queue ---------- */
async function enqueue(op) {
  await idbPut('queue', { ...op, slug: S.evt.slug, by: cfg.user });
  await countQueue();
}
async function countQueue() {
  const all = await idbAll('queue').catch(() => []);
  S.queued = all.filter((q) => q.slug === S.evt?.slug).length;
  paintHead();
  return all;
}
async function findQueued(scanUuid) {
  const all = await idbAll('queue').catch(() => []);
  return all.find((q) => q.op === 'scan' && q.uuid === scanUuid) ?? null;
}
let syncing = false;
/** Upload the queue, event by event, oldest first. Returns a short report. */
async function sync() {
  if (syncing) return null;
  syncing = true;
  const report = { uploaded: 0, refused: 0, duplicates: [], failed: false };
  try {
    const all = (await idbAll('queue').catch(() => [])).sort((a, b) => a.seq - b.seq);
    const bySlug = new Map();
    for (const q of all) { if (!bySlug.has(q.slug)) bySlug.set(q.slug, []); bySlug.get(q.slug).push(q); }
    for (const [slug, items] of bySlug) {
      const evt = cfg.events.find((e) => e.slug === slug);
      if (!evt) continue;
      for (let i = 0; i < items.length; i += 200) {
        const batch = items.slice(i, i + 200);
        const ops = batch.map(({ seq, slug: _s, by: _b, ...op }) => op);
        let data;
        try {
          data = await api(evt.urls.sync, { method: 'POST', body: { ops }, timeout: 20000 });
        } catch {
          report.failed = true;
          return report;
        }
        for (const q of batch) await idbDel('queue', q.seq);
        for (const r of data.results) {
          if (!r.ok) report.refused++;
          else if (r.op === 'scan') report.uploaded++;
        }
        for (const d of data.duplicates ?? []) report.duplicates.push(d.family_no ?? '?');
        if (slug === S.evt?.slug && data.stats) S.stats = data.stats;
      }
    }
    return report;
  } finally {
    syncing = false;
    await countQueue();
    paint();
  }
}

/* ---------- Painting ---------- */
function paintHead() {
  if (!cfg) return;
  $('#gate-name').textContent = S.gate || cfg.t.chooseGate;
  if (S.evt) $('#evt-name').textContent = S.evt.title;
  const online = S.online && navigator.onLine;
  $('#sync-btn').innerHTML = online
    ? `${icon('wifi', 'w-4 h-4')}${t('online')}${S.queued ? ` <span class="num">· ${n(S.queued)}</span>` : ''}`
    : `${icon('wifi-off', 'w-4 h-4')}${t('offline')}${S.queued ? ` <span class="num">· ${n(S.queued)}</span>` : ''}`;
  const parts = [];
  if (S.sessionEnded) parts.push(t('signInAgain'));
  if (S.list) parts.push(t('synced', { passes: S.list.passes.length, time: at(S.list.generated_at) }));
  else if (S.evt) parts.push(t('notDownloaded'));
  parts.push(S.queued ? t('waiting', { count: S.queued }) : t('allUploaded'));
  $('#sync-line').innerHTML = parts.join(' · ');
  for (const k of ['inside', 'families', 'denied']) { const el = $(`#stat-${k}`); if (el) el.textContent = n(S.stats[k] ?? 0); }
  drawIcons($('header'));
}
const TONE = { allowed: 'bg-tulsi', already: 'bg-marigold', reentry: 'bg-marigold' };
function paintRecent() {
  const ul = $('#recent');
  if (!ul) return;
  ul.innerHTML = S.log.length
    ? S.log.slice(0, 5).map((r) => `<li class="px-3.5 py-2.5 flex items-center gap-3${r.undone ? ' opacity-50 line-through' : ''}">
        <span class="w-2.5 h-2.5 rounded-full shrink-0 ${TONE[r.kind] ?? 'bg-sindoor'}"></span>
        <span class="flex-1 min-w-0 truncate">${esc(r.who)}</span>
        <span class="text-[13px] text-paper/60 whitespace-nowrap">${r.kind === 'allowed' ? `+${n(r.people)}` : r.kind === 'reentry' ? t('reentry') : r.kind === 'already' ? t('already') : t('turnedAway')} · <span class="num">${at(r.at)}</span></span></li>`).join('')
    : `<li class="px-3.5 py-4 text-paper/50 text-center">${t('noScans')}</li>`;
  const last = S.log[0];
  $('#undo').disabled = !last || last.undone || Date.now() - new Date(last.at).getTime() > cfg.undoSeconds * 1000;
}
function paint() { paintHead(); paintRecent(); }

/* ---------- Result screen ---------- */
const DENY = ['no_pass', 'cancelled', 'wrong_event', 'invalid'];
function beep(good) {
  if (!S.sound) return;
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const o = ctx.createOscillator(); const g = ctx.createGain();
    o.frequency.value = good ? 880 : 220; g.gain.value = 0.08; o.connect(g); g.connect(ctx.destination);
    o.start(); o.stop(ctx.currentTime + (good ? 0.12 : 0.35));
  } catch { /* no audio */ }
}
let timer;
/**
 * Show a result. r: { uuid, result, family_no, name, people, max, entry, conflict, local }
 * Resolves when the volunteer is done (with the head count / let-in decision applied).
 */
function show(r) {
  const el = $('#result');
  const who = r.name ? `${r.name} · ${r.family_no}` : (r.family_no ?? '');
  const views = {
    allowed: { bg: 'bg-tulsi text-white', icon: 'check', head: t('letIn'), who: esc(who), detail: t('familyPass', { count: r.max ?? r.people }) },
    already: { bg: 'bg-marigold text-ink', icon: 'history', head: t('already'), who: esc(who),
      detail: r.entry ? t('enteredAt', { time: r.entry.time ?? at(r.entry.at), gate: r.entry.gate || t('gate'), name: r.entry.by || '' }) : t('enteredEarlier'),
      note: t('reentryNote'), ask: true },
    cancelled: { bg: 'bg-sindoor text-white', icon: 'x', head: t('cancelled'), who: esc(who), detail: t('cancelledDetail'), note: t('deskNote') },
    no_pass: { bg: 'bg-sindoor text-white', icon: 'x', head: t('noPass'), who: esc(who), detail: t('noPassDetail'), note: t('deskNote') },
    wrong_event: { bg: 'bg-sindoor text-white', icon: 'x', head: t('wrongEvent'), who: esc(who), detail: t('wrongEventDetail'), note: t('deskNote') },
    invalid: { bg: 'bg-sindoor text-white', icon: 'x', head: t('notFound'), who: r.family_no ? esc(r.family_no) : t('notClubPass'), detail: t('askCard') },
  };
  const v = views[r.result] ?? views.invalid;
  let people = r.people || r.max || 1;
  const max = Math.max(1, r.max || people);
  const conflict = r.conflict === 'duplicate' ? t('duplicateNote') : r.conflict === 'cancelled' ? t('cancelledConflict') : '';
  el.className = `fixed inset-0 z-50 flex flex-col items-center justify-center text-center px-8 ${v.bg}`;
  el.innerHTML = `
    <div class="w-24 h-24 rounded-full bg-white/20 grid place-items-center rise">${icon(v.icon, 'w-14 h-14')}</div>
    <p class="font-display text-[2.6rem] leading-tight mt-5">${v.head}</p>
    <p class="text-xl font-semibold mt-2 num">${v.who}</p>
    <p class="text-lg mt-1 opacity-90">${v.detail}</p>
    ${r.result === 'allowed' ? `<div class="mt-5 flex items-center gap-4 rounded-2xl bg-black/15 px-4 py-2.5">
      <span class="text-[15px]">${t('comingIn')}</span>
      <button type="button" data-step="-1" class="w-11 h-11 rounded-full bg-white/20 text-2xl grid place-items-center" aria-label="${t('fewer')}">−</button>
      <span class="font-display text-3xl w-8 num" id="r-n" aria-live="polite"></span>
      <button type="button" data-step="1" class="w-11 h-11 rounded-full bg-white/20 text-2xl grid place-items-center" aria-label="${t('more')}">+</button></div>` : ''}
    ${v.note ? `<p class="mt-4 text-[15px] rounded-xl bg-black/15 px-4 py-3 max-w-sm">${v.note}</p>` : ''}
    ${conflict ? `<p class="mt-3 text-[14px] rounded-xl bg-black/20 px-4 py-2 max-w-sm">${conflict}</p>` : ''}
    ${r.local ? `<p class="mt-3 text-[13px] opacity-80 flex items-center gap-1.5">${icon('wifi-off', 'w-4 h-4')}${t('checkedHere')}</p>` : ''}
    <div class="mt-8 flex flex-wrap justify-center gap-3">
      ${v.ask ? `<button type="button" class="btn h-14 px-6 bg-white/25 hover:bg-white/35 text-[17px] text-inherit" data-allow>${t('letBackIn')}</button>` : ''}
      <button type="button" class="btn h-14 px-8 bg-white/20 hover:bg-white/30 text-[17px] text-inherit" data-next>${v.ask ? t('dontAllow') : r.result === 'allowed' ? t('doneNext') : t('nextScan')}</button>
    </div>`;
  const paintN = () => { const c = $('#r-n', el); if (c) c.textContent = n(people); };
  paintN();
  drawIcons(el);
  if (navigator.vibrate) navigator.vibrate(DENY.includes(r.result) ? [80, 60, 80] : 60);
  beep(r.result === 'allowed');
  $('[data-next]', el)?.focus();
  clearTimeout(timer);

  return new Promise((resolve) => {
    let done = false;
    const finish = (letIn) => {
      if (done) return;
      done = true;
      clearTimeout(timer);
      el.className = 'fixed inset-0 z-50 hidden';
      el.onclick = null;
      resolve({ people, letIn });
    };
    el.onclick = (e) => {
      const st = e.target.closest('[data-step]');
      if (st) { clearTimeout(timer); people = Math.max(1, Math.min(max, people + Number(st.dataset.step))); paintN(); return; }
      if (e.target.closest('[data-allow]')) finish(true);
      else if (e.target.closest('[data-next]')) finish(false);
    };
    el.onkeydown = (e) => { if (e.key === 'Escape') finish(false); };
    if (r.result === 'allowed') timer = setTimeout(() => finish(false), 4500);
  });
}

/* ---------- A scan, start to finish ---------- */
async function handle(input) {
  if (!S.evt || S.busy) return;
  S.busy = true;
  try {
    const op = { op: 'scan', uuid: uuid(), gate: S.gate || null, scanned_at: new Date().toISOString(), ...input };
    const local = await localCheck(op);
    let r = null;
    let queued = false;

    if (navigator.onLine && !S.sessionEnded) {
      try {
        const data = await api(S.evt.urls.scans, { method: 'POST', body: { uuid: op.uuid, code: op.code, family_no: op.family_no, gate: op.gate } });
        S.stats = data.stats;
        r = { ...data.scan, max: data.scan.max_people };
        if (r.result === 'allowed' && local.hash) await markUsed(local.hash, true);
      } catch (e) {
        if (!e.offline) { toast(e.message || cfg.t.error); return; }
      }
    }
    if (!r) {
      r = { uuid: op.uuid, result: local.result, family_no: local.family_no ?? op.family_no ?? null, people: local.max ?? 1, max: local.max, local: true };
      await enqueue({ ...op, local_result: local.result, people: r.result === 'allowed' ? r.max : null });
      if (r.result === 'allowed' && local.hash) await markUsed(local.hash, true);
      if (DENY.includes(r.result)) S.stats.denied = (S.stats.denied ?? 0) + 1;
      if (r.result === 'allowed') { S.stats.inside = (S.stats.inside ?? 0) + (r.max ?? 1); S.stats.families = (S.stats.families ?? 0) + 1; }
      queued = true;
    }

    const entry = { uuid: r.uuid, who: r.name ? `${r.name} · ${r.family_no}` : (r.family_no ?? '—'), kind: DENY.includes(r.result) ? 'denied' : r.result, people: r.result === 'allowed' ? r.people : 0, at: new Date().toISOString(), hash: local.hash ?? null, queued };
    S.log.unshift(entry);
    paint();

    const { people, letIn } = await show(r);
    if (r.result === 'allowed' && people !== r.people) {
      if (!queued) S.stats.inside += people - r.people;
      else S.stats.inside += people - (r.max ?? 1);
      entry.people = people;
      await change(entry, { op: 'people', people });
    }
    if (r.result === 'already' && letIn) {
      entry.kind = 'reentry';
      await change(entry, { op: 'let_in' });
    }
    paint();
  } finally {
    S.busy = false;
  }
}

/** Head count, let back in or undo: straight to the server, else into the queue. */
async function change(entry, op) {
  const queuedScan = await findQueued(entry.uuid);
  if (queuedScan) {
    if (op.op === 'undo') { await idbDel('queue', queuedScan.seq); await countQueue(); return true; }
    if (op.op === 'people') queuedScan.people = op.people;
    if (op.op === 'let_in') queuedScan.let_in = true;
    await idbPut('queue', queuedScan);
    return true;
  }
  if (navigator.onLine && !S.sessionEnded) {
    try {
      const data = op.op === 'undo'
        ? await api(scanUrl(entry.uuid), { method: 'DELETE' })
        : await api(scanUrl(entry.uuid), { method: 'PATCH', body: op.op === 'people' ? { people: op.people } : { let_in: true } });
      S.stats = data.stats;
      return true;
    } catch (e) {
      if (!e.offline) { toast(e.message || cfg.t.error); return false; }
    }
  }
  await enqueue({ ...op, uuid: entry.uuid, at: new Date().toISOString() });
  return true;
}

async function undoLast() {
  const last = S.log[0];
  if (!last || last.undone) return;
  if (Date.now() - new Date(last.at).getTime() > cfg.undoSeconds * 1000) { toast(cfg.t.tooLate); paintRecent(); return; }
  if (await change(last, { op: 'undo' })) {
    last.undone = true;
    if (last.hash && last.kind === 'allowed') await markUsed(last.hash, false);
    if (last.queued) {
      if (last.kind === 'allowed') { S.stats.inside -= last.people; S.stats.families -= 1; }
      if (last.kind === 'denied') S.stats.denied -= 1;
    }
    toast(cfg.t.undone);
    paint();
  }
}

function toast(message) {
  const el = document.createElement('p');
  el.setAttribute('role', 'status');
  el.className = 'fixed left-1/2 -translate-x-1/2 bottom-6 z-[60] rounded-xl bg-paper text-ink px-4 py-3 text-[15px] shadow-lg max-w-[90vw]';
  el.textContent = message;
  document.body.append(el);
  setTimeout(() => el.remove(), 3500);
}

/* ---------- Bottom sheets ---------- */
function sheet(html, wire) {
  const s = $('#sheet');
  $('#sheet-body').innerHTML = `<div class="w-10 h-1.5 rounded-full bg-white/20 mx-auto mb-4"></div>${html}`;
  s.classList.remove('hidden');
  drawIcons(s);
  wire?.($('#sheet-body'));
  $('#sheet-body').querySelector('input, button')?.focus();
}
const closeSheet = () => $('#sheet').classList.add('hidden');

function manualSheet() {
  let digits = '';
  sheet(`
    <h2 class="font-display text-2xl">${t('familyNumber')}</h2>
    <p class="text-[14px] text-paper/60">${t('familyNumberHint')}</p>
    <p class="mt-4 rounded-xl bg-night px-4 h-16 flex items-center font-display text-3xl tracking-wider"><span class="text-paper/50">MC-</span><span id="kp-v" class="num"></span><span class="w-0.5 h-8 bg-marigold ml-0.5 animate-pulse"></span></p>
    <p id="kp-hint" class="text-[14px] text-paper/60 mt-2 min-h-5" aria-live="polite"></p>
    <div class="grid grid-cols-3 gap-2 mt-3">${['1', '2', '3', '4', '5', '6', '7', '8', '9', 'del', '0', 'go'].map((k) => `<button type="button" data-k="${k}" class="h-14 rounded-xl text-2xl font-semibold ${k === 'go' ? 'bg-marigold text-ink text-lg' : 'bg-white/10 hover:bg-white/15'}" ${k === 'del' ? `aria-label="⌫"` : ''}>${k === 'del' ? icon('delete', 'w-6 h-6 mx-auto') : k === 'go' ? t('check') : n(Number(k))}</button>`).join('')}</div>
    <button type="button" data-sheet-close class="btn w-full h-12 mt-3 bg-transparent text-paper/80">${t('cancel')}</button>`, (b) => {
    const familyNo = () => `MC-${digits.padStart(4, '0')}`;
    const upd = () => {
      $('#kp-v', b).textContent = bn ? digits.replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[d]) : digits;
      if (digits.length < 3 || !S.list) { $('#kp-hint', b).textContent = ''; return; }
      const p = S.list.passes.find((x) => x.f === familyNo());
      $('#kp-hint', b).innerHTML = p ? t('peopleCount', { count: p.n }) : t('noFamily');
    };
    const go = () => { if (!digits) return; closeSheet(); handle({ family_no: familyNo() }); };
    b.addEventListener('click', (e) => {
      const k = e.target.closest('[data-k]')?.dataset.k;
      if (!k) return;
      if (k === 'del') digits = digits.slice(0, -1);
      else if (k === 'go') { go(); return; } else if (digits.length < 5) digits += k;
      upd();
    });
    b.addEventListener('keydown', (e) => {
      if (/^\d$/.test(e.key) && digits.length < 5) { digits += e.key; upd(); }
      else if (e.key === 'Backspace') { digits = digits.slice(0, -1); upd(); }
      else if (e.key === 'Enter') { e.preventDefault(); go(); }
    });
    upd();
  });
}

function eventSheet() {
  sheet(`
    <h2 class="font-display text-2xl">${t('eventAndGate')}</h2>
    <p class="text-[13px] font-semibold tracking-wide text-paper/50 mt-4 mb-2">${t('eventLabel')}</p>
    <div class="space-y-2">${cfg.events.map((e) => `<button type="button" data-evt="${esc(e.slug)}" aria-pressed="${S.evt?.slug === e.slug}" class="w-full text-left rounded-xl bg-white/5 px-4 py-3 border border-white/10 aria-pressed:border-marigold aria-pressed:bg-marigold/10">
      <span class="block font-semibold">${esc(e.title)}</span><span class="block text-[13px] text-paper/60">${esc(e.date)}${e.place ? ` · ${esc(e.place)}` : ''}</span></button>`).join('')}</div>
    <label class="block mt-5"><span class="block text-[13px] font-semibold tracking-wide text-paper/50 mb-2">${t('gateLabel')}</span>
      <input id="gate-input" class="w-full h-12 rounded-xl bg-night border border-white/15 px-3 text-paper" maxlength="60" value="${esc(S.gate)}" placeholder="${t('gatePlaceholder')}"></label>
    <p class="text-[13px] text-paper/60 mt-4">${t('downloadNote')}</p>
    <button type="button" data-sheet-close class="btn w-full h-12 mt-4 bg-marigold text-ink">${t('done')}</button>`, (b) => {
    b.addEventListener('click', async (e) => {
      const ev = e.target.closest('[data-evt]');
      if (!ev) return;
      S.evt = cfg.events.find((x) => x.slug === ev.dataset.evt);
      $$('[data-evt]', b).forEach((x) => x.setAttribute('aria-pressed', String(x === ev)));
      history.replaceState(null, '', `?event=${encodeURIComponent(S.evt.slug)}`);
      S.list = null;
      S.log = [];
      await countQueue();
      await loadList();
    });
    $('#gate-input', b).addEventListener('input', (e) => {
      S.gate = e.target.value.trim();
      localStorage.setItem('gmc-gate', S.gate);
      paintHead();
    });
  });
}

function syncSheet() {
  const online = S.online && navigator.onLine;
  sheet(`
    <h2 class="font-display text-2xl">${online ? t('online') : t('workingOffline')}</h2>
    <ul class="mt-3 space-y-2.5 text-[15px]">
      <li class="flex gap-2.5">${icon('check', 'w-5 h-5 text-marigold shrink-0')}${S.list ? t('listOnPhone', { count: S.list.passes.length }) : t('notDownloaded')}</li>
      <li class="flex gap-2.5">${icon('cloud-upload', 'w-5 h-5 text-marigold shrink-0')}${S.queued ? t('willUpload', { count: S.queued }) : t('nothingWaiting')}</li>
      <li class="flex gap-2.5">${icon('users', 'w-5 h-5 text-marigold shrink-0')}${t('otherGates')}</li>
    </ul>
    <p id="sync-msg" class="text-[14px] text-paper/70 mt-4 min-h-5" role="status"></p>
    <button type="button" id="sync-now" class="btn w-full h-12 mt-2 bg-marigold text-ink">${icon('refresh-cw')}${t('syncNow')}</button>
    <button type="button" data-sheet-close class="btn w-full h-12 mt-2 bg-transparent text-paper/80">${t('close')}</button>`, (b) => {
    $('#sync-now', b).addEventListener('click', async (e) => {
      const btn = e.currentTarget;
      btn.disabled = true;
      $('#sync-msg', b).innerHTML = t('looking');
      const report = await sync();
      await refreshList();
      const msg = [];
      if (S.sessionEnded) msg.push(t('signInAgain'));
      else if (report?.failed) msg.push(t('syncFailed'));
      else if (report) msg.push(t('uploaded', { count: report.uploaded }));
      if (report?.refused) msg.push(t('refused', { count: report.refused }));
      if (report?.duplicates?.length) msg.push(t('duplicatesFound', { list: report.duplicates.join(', ') }));
      $('#sync-msg', b).innerHTML = msg.join(' ');
      btn.disabled = false;
    });
  });
}

async function endSheet() {
  let shift = null;
  if (navigator.onLine && S.evt) {
    try { shift = (await api(`${S.evt.urls.shift}?since=${encodeURIComponent(S.shiftStart)}`)).shift; } catch { /* use the phone's own count */ }
  }
  const mine = S.log.filter((r) => !r.undone);
  const scans = shift?.scans ?? mine.length;
  const letIn = shift?.people ?? mine.reduce((a, r) => a + (r.people || 0), 0);
  const away = shift?.denied ?? mine.filter((r) => r.kind === 'denied').length;
  await countQueue();
  sheet(`
    <h2 class="font-display text-2xl">${t('endShift')}</h2>
    <p class="text-[14px] text-paper/60">${esc(S.gate || '')}${S.gate ? ' · ' : ''}${esc(S.evt?.title ?? '')}</p>
    <div class="grid grid-cols-3 gap-2 mt-4 text-center">
      <div class="rounded-xl bg-night p-3"><p class="font-display text-2xl num">${n(scans)}</p><p class="text-[12.5px] text-paper/60">${t('scans')}</p></div>
      <div class="rounded-xl bg-night p-3"><p class="font-display text-2xl num">${n(letIn)}</p><p class="text-[12.5px] text-paper/60">${t('letInCount')}</p></div>
      <div class="rounded-xl bg-night p-3"><p class="font-display text-2xl num">${n(away)}</p><p class="text-[12.5px] text-paper/60">${t('turnedAway')}</p></div>
    </div>
    ${S.queued ? `<p class="mt-3 rounded-xl bg-marigold/15 text-marigold px-3.5 py-2.5 text-[14px]">${t('stillOnPhone', { count: S.queued })}</p>
      <button type="button" id="end-sync" class="btn w-full h-12 mt-3 bg-white/10 text-paper">${icon('refresh-cw')}${t('syncNow')}</button>` : ''}
    <button type="button" id="end-out" class="btn w-full h-12 mt-4 bg-marigold text-ink">${icon('log-out')}${S.queued ? t('signOutAnyway') : t('endAndSignOut')}</button>
    <button type="button" data-sheet-close class="btn w-full h-12 mt-2 bg-transparent text-paper/80">${t('keepScanning')}</button>`, (b) => {
    $('#end-sync', b)?.addEventListener('click', async () => { await sync(); closeSheet(); endSheet(); });
    $('#end-out', b).addEventListener('click', () => {
      localStorage.removeItem('gmc-shift-start');
      navigator.serviceWorker?.controller?.postMessage('clear-cache');
      $('#logout-form').submit();
    });
  });
}

/* ---------- Camera ---------- */
let stream = null;
let detector = null;
let lastCode = '';
let lastAt = 0;
async function startCamera() {
  const video = $('#video');
  const msg = $('#cam-msg');
  const btn = $('#cam-start');
  if (!video) return;
  if (!navigator.mediaDevices?.getUserMedia) { msg.textContent = cfg.t.noCamera; return; }
  try {
    detector ??= new BarcodeDetector({ formats: ['qr_code'] });
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
    video.srcObject = stream;
    await video.play();
    btn.hidden = true;
    const track = stream.getVideoTracks()[0];
    const caps = track.getCapabilities?.() ?? {};
    if (caps.torch) $('#torch').hidden = false;
    loop();
  } catch {
    msg.textContent = cfg.t.noCamera;
    btn.hidden = false;
  }
}
async function loop() {
  const video = $('#video');
  if (!stream || !video) return;
  const resultOpen = !$('#result').classList.contains('hidden');
  const sheetOpen = !$('#sheet').classList.contains('hidden');
  if (!resultOpen && !sheetOpen && !S.busy && video.readyState >= 2) {
    try {
      const codes = await detector.detect(video);
      const code = codes[0]?.rawValue;
      if (code && (code !== lastCode || Date.now() - lastAt > 4000)) {
        lastCode = code;
        lastAt = Date.now();
        handle({ code });
      }
    } catch { /* a frame that could not be read */ }
  }
  setTimeout(loop, 200);
}

/* ---------- Start ---------- */
async function boot() {
  if (!cfg || !S.evt) { paintHead(); return; }

  // Fetch the .wasm now (from this site), so it is cached for scanning without signal.
  prepareZXingModule({ overrides: { locateFile: (path, prefix) => (path.endsWith('.wasm') ? wasmUrl : prefix + path) }, fireImmediately: true })?.catch?.(() => {});

  $('#manual').addEventListener('click', manualSheet);
  $('#evt-btn').addEventListener('click', eventSheet);
  $('#sync-btn').addEventListener('click', syncSheet);
  $('#end-shift').addEventListener('click', endSheet);
  $('#undo').addEventListener('click', undoLast);
  $('#cam-start').addEventListener('click', startCamera);
  $('#sheet').addEventListener('click', (e) => { if (e.target.closest('[data-sheet-close]')) closeSheet(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSheet(); });
  $('#torch').addEventListener('click', async (e) => {
    const on = e.currentTarget.getAttribute('aria-pressed') !== 'true';
    try { await stream?.getVideoTracks()[0]?.applyConstraints({ advanced: [{ torch: on }] }); e.currentTarget.setAttribute('aria-pressed', String(on)); } catch { /* no torch */ }
  });
  $('#sound').addEventListener('click', (e) => {
    S.sound = !S.sound;
    localStorage.setItem('gmc-scan-sound', S.sound ? 'on' : 'off');
    e.currentTarget.setAttribute('aria-pressed', String(S.sound));
    e.currentTarget.innerHTML = icon(S.sound ? 'volume-2' : 'volume-x');
    drawIcons(e.currentTarget);
  });
  $('#sound').setAttribute('aria-pressed', String(S.sound));
  if (!S.sound) { $('#sound').innerHTML = icon('volume-x'); drawIcons($('#sound')); }

  window.addEventListener('online', async () => { S.online = true; await sync(); await refreshList(); });
  window.addEventListener('offline', () => { S.online = false; paintHead(); });
  setInterval(() => { if (navigator.onLine && S.queued) sync(); }, 20000);
  setInterval(() => { if (navigator.onLine) refreshList(); }, 5 * 60000);
  setInterval(paintRecent, 15000);

  paint();
  await countQueue();
  await loadList();
  if (navigator.onLine && S.queued) sync();
  startCamera();
}

if ('serviceWorker' in navigator && document.body.dataset.sw) {
  window.addEventListener('load', () => navigator.serviceWorker.register(document.body.dataset.sw).catch(() => {}));
}
drawIcons();
boot();
