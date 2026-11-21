// Network first, cache as fallback: pages already opened keep working on a weak network
// (at the puja gate), and a new version is picked up on the next load.
// The cache is cleared on sign-out, on any sign-in page and when a page redirects to sign-in
// (see resources/js/forget.js).
//
// - Member passes (/app/events/passes/...) are ordinary pages: once opened they show offline.
// - The gate scanner page (/admin/scan) and its script and .wasm are cached the same way; its data
//   (pass list, queued scans) lives in IndexedDB, so its JSON calls (/admin/scan/...) are never cached.
const CACHE = 'gmc-v3';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(
  caches.keys()
    .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
    .then(() => self.clients.claim()),
));

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Paying is always live: never serve a payment page from the cache. Scanner data is always live.
  if (request.method !== 'GET' || url.origin !== location.origin || url.pathname.endsWith('/logout')
    || url.pathname.startsWith('/app/pay') || url.pathname.startsWith('/admin/scan/')) return;

  const fallback = url.pathname.startsWith('/admin') ? '/admin' : '/app';

  event.respondWith(
    fetch(request)
      .then((response) => {
        // Sent to a sign-in page: the session has ended, so forget the last person's pages.
        if (response.redirected && new URL(response.url).pathname.endsWith('/login')) {
          caches.delete(CACHE);
          return response;
        }
        if (response.ok && response.type === 'basic') {
          const copy = response.clone();
          caches.open(CACHE).then((cache) => cache.put(request, copy));
        }
        return response;
      })
      .catch(() => caches.match(request, { ignoreSearch: url.pathname === '/admin/scan' })
        .then((hit) => hit || caches.match(fallback))),
  );
});

self.addEventListener('message', (event) => {
  if (event.data === 'clear-cache') event.waitUntil(caches.delete(CACHE));
});
