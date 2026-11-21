// Forget what this phone kept for the last person who signed in: the pages saved for offline use
// and the gate scanner's pass lists. Run on sign-out and whenever a sign-in page loads (the session
// may have ended without a sign-out). Scans still waiting to upload are kept.
const SCANNER_DB = 'gmc-scanner';

function clearScannerLists() {
    if (!('indexedDB' in window)) return Promise.resolve();

    return new Promise((resolve) => {
        const req = indexedDB.open(SCANNER_DB, 1);
        // Same stores as resources/js/scan.js, so opening here never leaves a half-made database.
        req.onupgradeneeded = () => {
            const d = req.result;
            d.createObjectStore('lists', { keyPath: 'slug' });
            d.createObjectStore('queue', { keyPath: 'seq', autoIncrement: true });
            d.createObjectStore('used', { keyPath: 'k' });
        };
        req.onsuccess = () => {
            const d = req.result;
            const x = d.transaction(['lists', 'used'], 'readwrite');
            x.objectStore('lists').clear();
            x.objectStore('used').clear();
            x.oncomplete = x.onerror = x.onabort = () => { d.close(); resolve(); };
        };
        req.onerror = req.onblocked = () => resolve();
    });
}

function clearPageCache() {
    navigator.serviceWorker?.controller?.postMessage('clear-cache');
    if (!('caches' in window)) return Promise.resolve();

    return caches.keys()
        .then((keys) => Promise.all(keys.filter((key) => key.startsWith('gmc-')).map((key) => caches.delete(key))))
        .catch(() => {});
}

export function forgetThisDevice() {
    localStorage.removeItem('gmc-shift-start');

    return Promise.all([clearPageCache(), clearScannerLists().catch(() => {})]);
}
