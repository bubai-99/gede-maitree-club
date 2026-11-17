// Network first, cache as fallback: pages already opened keep working on a weak network
// (at the puja gate), and a new version is picked up on the next load.
// The cache is cleared when someone signs out (see resources/js/app.js).
const CACHE = 'gmc-v2';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(
  caches.keys()
    .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
    .then(() => self.clients.claim()),
));

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Paying is always live: never serve a payment page from the cache.
  if (request.method !== 'GET' || url.origin !== location.origin || url.pathname.endsWith('/logout') || url.pathname.startsWith('/app/pay')) return;

  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response.ok && response.type === 'basic') {
          const copy = response.clone();
          caches.open(CACHE).then((cache) => cache.put(request, copy));
        }
        return response;
      })
      .catch(() => caches.match(request).then((hit) => hit || caches.match('/app'))),
  );
});

self.addEventListener('message', (event) => {
  if (event.data === 'clear-cache') event.waitUntil(caches.delete(CACHE));
});
