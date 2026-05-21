// CES LAUSANNE - Service Worker v70.3.77 - Cache Cleaner
const CACHE_VERSION = 'ces-lausanne-v70.3.77';

self.addEventListener('install', (event) => {
    self.skipWaiting();
    console.log('[SW] v70.3.77: Instalando e limpando caches antigos...');
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_VERSION)
                    .map(name => {
                        console.log('[SW] Removendo cache antigo:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    // Passa direto - sem cache, sempre busca do servidor
    event.respondWith(fetch(event.request));
});
