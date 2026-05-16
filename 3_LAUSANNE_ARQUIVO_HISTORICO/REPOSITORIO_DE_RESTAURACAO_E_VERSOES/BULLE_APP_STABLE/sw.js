const CACHE_NAME = 'catedral-bulle-v63.1.4';
const ASSETS_TO_CACHE = [
    './',
    './index.html',
    './recepcao',
    './admin',
    './altar',
    './kids',
    './integracao',
    './checkin',
    './css/style-v2.css',
    './js/firebase-config.js',
    './js/auth-manager.js',
    './manifest.json',
    './sw.js'
];

// 1. Install Event - PRE-CACHE CLEAN PATHS
self.addEventListener('install', (event) => {
    self.skipWaiting();
    console.log('🚀 SW v63.1.4: Final Unified Activation');
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE))
    );
});

// 2. Activate Event - DELETE EVERYTHING OLD
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) return caches.delete(cache);
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Fetch Event - STALE-WHILE-REVALIDATE (DIRECT)
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // A. EXTERNAL BYPASS (Firebase/Analytics/etc)
    if (event.request.method !== 'GET' ||
        url.hostname.includes('google') ||
        url.hostname.includes('firebase') ||
        url.hostname.includes('gstatic') ||
        url.pathname.includes('cloudfunctions')) {
        return;
    }

    // B. Response Handler - DIRECT MATCHING
    event.respondWith(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.match(event.request, { ignoreSearch: true }).then((cachedResponse) => {
                
                // Prepare the network fetch promise
                const fetchPromise = fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        cache.put(event.request, networkResponse.clone());
                    }
                    return networkResponse;
                }).catch(() => null);

                // D. FIXED LOGIC: Wrap both in Promise.resolve for stability
                return Promise.resolve(cachedResponse || fetchPromise).then((finalResponse) => {
                    if (finalResponse) return finalResponse;
                    
                    // FINAL FALLBACK: Reliable 404
                    return new Response("Offline (v48.12.20)", { 
                        status: 404, 
                        headers: { "Content-Type": "text/plain; charset=utf-8" }
                    });
                });
            });
        })
    );
});
