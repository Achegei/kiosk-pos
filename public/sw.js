console.log("POS Service Worker loaded");

const CACHE_NAME = 'pos-cache-v5';

// =============================
// INSTALL (CACHE APP SHELL)
// =============================
self.addEventListener('install', event => {

    event.waitUntil((async () => {

        const cache = await caches.open(CACHE_NAME);

        console.log("Caching POS shell...");

        try {
            const res = await fetch('/build/manifest.json');
            const manifest = await res.json();

            const assets = Object.values(manifest)
                .filter(e => e.file)
                .map(e => '/build/' + e.file);

            await cache.addAll([
                '/',
                '/pos',
                '/dashboard',   // 🔥 MUST EXIST
                ...assets
            ]);

            console.log("✅ Assets cached");

        } catch (err) {

            console.warn("⚠️ Manifest failed, caching minimal shell");

            await cache.addAll([
                '/',
                '/pos',
                '/dashboard'
            ]);
        }

    })());

    self.skipWaiting();
});

// =============================
// ACTIVATE (CLEAN OLD CACHE)
// =============================
self.addEventListener('activate', event => {

    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(k => k !== CACHE_NAME)
                    .map(k => caches.delete(k))
            )
        )
    );

    self.clients.claim();
});

// =============================
// BACKGROUND SYNC
// =============================
self.addEventListener('sync', event => {

    if (event.tag === 'sync-sales') {

        console.log("🔄 Background sync triggered");

        event.waitUntil(syncOfflineSales());
    }
});

async function syncOfflineSales() {

    try {

        const clients = await self.clients.matchAll();

        for (const client of clients) {
            client.postMessage({ type: "SYNC_REQUEST" });
        }

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
    // 1. API → NETWORK FIRST
    // =============================
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/fetch/') ||
        url.pathname.startsWith('/register') ||
        url.pathname.startsWith('/cash') ||
        url.pathname.startsWith('/checkout')
    ) {

        event.respondWith(
            fetch(req).catch(() =>
                new Response(JSON.stringify({
                    success: false,
                    offline: true
                }), {
                    headers: { "Content-Type": "application/json" }
                })
            )
        );

        return;
    }

    // =============================
    // 2. NAVIGATION (CRITICAL FIX)
    // =============================
    if (req.mode === 'navigate') {

        event.respondWith((async () => {

            try {
                return await fetch(req);
            } catch (err) {

                console.warn("📴 Offline navigation:", req.url);

                const cache = await caches.open(CACHE_NAME);

                // 🔥 STRICT FALLBACK ORDER
                return (
                    await cache.match(req.url) ||      // exact page
                    await cache.match('/dashboard') || // preferred
                    await cache.match('/pos') ||
                    await cache.match('/') ||
                    new Response('<h1>Offline</h1>', {
                        headers: { "Content-Type": "text/html" }
                    })
                );
            }

        })());

        return;
    }

    // =============================
    // 3. STATIC → CACHE FIRST
    // =============================
    event.respondWith((async () => {

        const cached = await caches.match(req);
        if (cached) return cached;

        try {

            const res = await fetch(req);

            const copy = res.clone();

            const cache = await caches.open(CACHE_NAME);
            cache.put(req, copy);

            return res;

        } catch {

            // prevent crash
            return new Response('', { status: 200 });
        }

    })());
});