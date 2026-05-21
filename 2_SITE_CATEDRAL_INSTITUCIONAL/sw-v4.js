const VERSION = 'v80.0.3-site';
const CACHE_NAME = 'catedral-site-' + VERSION;
const ASSETS_TO_CACHE = [
    './',
    'index.html',
    'css/site.css',
    'assets/logo%20bulle.jpeg'
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request).catch((err) => {
                console.warn("⚠️ SW Fetch failed for " + event.request.url + ":", err);
                if (event.request.mode === 'navigate') {
                    return caches.match('index.html') || caches.match('./');
                }
                return new Response('Network error occurred', {
                    status: 480,
                    statusText: 'SW Fetch Failed'
                });
            });
        })
    );
});
