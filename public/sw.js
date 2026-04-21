const CACHE_NAME = 'pos-cache-v2';

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(async cache => {

            // 🔥 Fetch Vite manifest dynamically
            const res = await fetch('/build/manifest.json');
            const manifest = await res.json();

            const assets = Object.values(manifest).map(entry => '/build/' + entry.file);

            // 🔥 Cache everything important
            return cache.addAll([
                '/',
                '/pos', // your POS route
                ...assets
            ]);
        })
    );
});

self.addEventListener('fetch', event => {

    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});