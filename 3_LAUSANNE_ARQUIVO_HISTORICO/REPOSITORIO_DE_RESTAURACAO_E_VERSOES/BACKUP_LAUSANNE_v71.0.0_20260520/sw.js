self.addEventListener('install', (event) => {
    self.skipWaiting();
    console.log('SW Kill Protocol (sw.js): Installing...');
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    console.log('SW Kill Protocol (sw.js): Cleaning cache:', cacheName);
                    return caches.delete(cacheName);
                })
            );
        }).then(() => {
            console.log('SW Kill Protocol (sw.js): Unregistering...');
            return self.registration.unregister();
        }).then(() => {
            console.log('SW Kill Protocol (sw.js): SUCCESS. Refreshing clients...');
            return self.clients.claim();
        }).then(() => {
            return self.clients.matchAll().then(clients => {
                clients.forEach(client => {
                    if (client.url && 'navigate' in client) {
                        client.navigate(client.url);
                    }
                });
            });
        })
    );
});
