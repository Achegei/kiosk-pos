console.log("POS Service Worker loaded");

const CACHE_NAME = 'pos-cache-v4';

// =============================
// INSTALL (CACHE APP SHELL)
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
                console.warn("Manifest fetch failed, continuing without it");
            }

        })()
    );

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
                    .filter(key => key !== CACHE_NAME)
                    .map(key => {
                        console.log("Deleting old cache:", key);
                        return caches.delete(key);
                    })
            )
        )
    );

    self.clients.claim();
});

// =============================
// FETCH STRATEGY
// =============================
self.addEventListener('fetch', event => {

    const req = event.request;
    const url = new URL(req.url);

    // =============================
    // 1. API REQUESTS → NETWORK FIRST
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

                console.warn("Offline API:", url.pathname);

                return new Response(JSON.stringify({
                    success: false,
                    offline: true,
                    message: "Offline mode"
                }), {
                    headers: { "Content-Type": "application/json" }
                });
            })
        );

        return;
    }

    // =============================
    // 2. NAVIGATION (HTML PAGES)
    // =============================
    if (req.mode === 'navigate') {

        event.respondWith(
            fetch(req).catch(() => {

                console.warn("Offline page → serving cache");

                return caches.match(req)
                    || caches.match('/pos')
                    || caches.match('/');
            })
        );

        return;
    }

    // =============================
    // 3. STATIC FILES → CACHE FIRST
    // =============================
    event.respondWith(
        caches.match(req).then(cached => {

            if (cached) return cached;

            return fetch(req)
                .then(res => {

                    const copy = res.clone();

                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(req, copy);
                    });

                    return res;
                })
                .catch(() => {
                    // last fallback (prevents crash)
                    return new Response('', { status: 200 });
                });
        })
    );
});