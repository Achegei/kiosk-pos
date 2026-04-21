console.log("POS Service Worker loaded");

const CACHE_NAME = 'pos-cache-v4';

// =============================
// INSTALL
// =============================
self.addEventListener('install', event => {
    event.waitUntil(
        (async () => {

            const cache = await caches.open(CACHE_NAME);

            console.log("Caching POS shell...");

            try {
                const res = await fetch('/build/manifest.json');
                const manifest = await res.json();

                const assets = Object.values(manifest)
                    .filter(entry => entry.file)
                    .map(entry => '/build/' + entry.file);

                await cache.addAll([
                    '/',
                    '/pos',
                    '/dashboard',
                    ...assets
                ]);

                console.log("POS assets cached");

            } catch (err) {
                console.warn("Manifest fetch failed");
            }

        })()
    );

    self.skipWaiting();
});

// =============================
// ACTIVATE
// =============================
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            )
        )
    );

    self.clients.claim();
});

// =============================
// BACKGROUND SYNC (🔥 FIXED LOCATION)
// =============================
self.addEventListener('sync', event => {

    if (event.tag === 'sync-sales') {

        console.log("🔄 Background sync triggered");

        event.waitUntil(syncOfflineSales());
    }
});

// =============================
// SYNC FUNCTION
// =============================
async function syncOfflineSales() {

    try {

        const clients = await self.clients.matchAll();

        clients.forEach(client => {
            client.postMessage({
                type: "SYNC_REQUEST"
            });
        });

    } catch (err) {
        console.error("Background sync failed:", err);
    }
}

// =============================
// FETCH STRATEGY
// =============================
self.addEventListener('fetch', event => {

    const req = event.request;
    const url = new URL(req.url);

    // =============================
    // API → NETWORK FIRST
    // =============================
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/fetch/') ||
        url.pathname.startsWith('/register') ||
        url.pathname.startsWith('/cash') ||
        url.pathname.startsWith('/checkout')
    ) {

        event.respondWith(
            fetch(req).catch(() => {

                return new Response(JSON.stringify({
                    success: false,
                    offline: true
                }), {
                    headers: { "Content-Type": "application/json" }
                });
            })
        );

        return;
    }

    // =============================
    // NAVIGATION
    // =============================
    if (req.mode === 'navigate') {

        event.respondWith(
            fetch(req).catch(() =>
                caches.match('/pos') || caches.match('/')
            )
        );

        return;
    }

    // =============================
    // STATIC CACHE FIRST
    // =============================
    event.respondWith(
        caches.match(req).then(cached => {

            if (cached) return cached;

            return fetch(req).then(res => {

                const copy = res.clone();

                caches.open(CACHE_NAME).then(cache => {
                    cache.put(req, copy);
                });

                return res;

            }).catch(() => new Response('', { status: 200 }));
        })
    );
});