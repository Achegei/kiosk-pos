console.log("POS Service Worker loaded");

const CACHE_NAME = 'pos-cache-v3';

// =============================
// INSTALL (APP SHELL CACHE)
// =============================
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(async cache => {

            console.log("Caching POS shell...");

            try {
                const res = await fetch('/build/manifest.json');
                const manifest = await res.json();

                const assets = Object.values(manifest).map(entry => '/build/' + entry.file);

                const staticAssets = [
                    '/',
                    '/pos',
                    '/dashboard',
                    '/offline', // optional fallback page if you have it
                    ...assets
                ];

                await cache.addAll(staticAssets);

                console.log("POS assets cached");

            } catch (err) {
                console.error("SW install cache error:", err);
            }
        })
    );

    self.skipWaiting();
});

// =============================
// ACTIVATE (CLEAN OLD CACHE)
// =============================
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        console.log("Deleting old cache:", key);
                        return caches.delete(key);
                    }
                })
            );
        })
    );

    self.clients.claim();
});

// =============================
// FETCH STRATEGY
// =============================
self.addEventListener('fetch', event => {

    const url = new URL(event.request.url);

    // =============================
    // 1. API REQUESTS (CRITICAL FIX)
    // =============================
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/register') ||
        url.pathname.startsWith('/cash') ||
        url.pathname.startsWith('/checkout')
    ) {

        event.respondWith(
            fetch(event.request).catch(() => {

                // 🔥 Offline-safe response so UI doesn't break
                return new Response(JSON.stringify({
                    success: false,
                    offline: true,
                    message: "You are offline. Action queued locally."
                }), {
                    headers: { "Content-Type": "application/json" }
                });
            })
        );

        return;
    }

    // =============================
    // 2. STATIC FILES (CACHE FIRST)
    // =============================
    event.respondWith(
        caches.match(event.request).then(cached => {

            if (cached) return cached;

            return fetch(event.request).then(response => {

                // Optional: update cache dynamically
                const copy = response.clone();

                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, copy);
                });

                return response;

            }).catch(() => {
                // last fallback
                return caches.match('/');
            });
        })
    );
});