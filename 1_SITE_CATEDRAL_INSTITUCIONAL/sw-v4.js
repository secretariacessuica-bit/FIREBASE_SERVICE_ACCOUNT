const VERSION = 'v80.0.5-site';
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
    // Only intercept local GET requests
    if (event.request.method !== 'GET' || !event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((response) => {
            if (response) {
                return response;
            }

            let requestToFetch = event.request;
            // Solve: "a redirected response was used for a request whose redirect mode is not 'follow'"
            if (event.request.mode === 'navigate') {
                requestToFetch = new Request(event.request, {
                    redirect: 'follow'
                });
            }

            return fetch(requestToFetch).then((networkResponse) => {
                // If it is redirected, we must recreate the response to clear the redirected flag (Chrome redirect safety)
                if (networkResponse.redirected) {
                    return new Response(networkResponse.body, {
                        status: networkResponse.status,
                        statusText: networkResponse.statusText,
                        headers: networkResponse.headers
                    });
                }
                return networkResponse;
            }).catch((err) => {
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
