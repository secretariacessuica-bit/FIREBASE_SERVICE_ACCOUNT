const VERSION = 'v67.6.4';
const CACHE_NAME = 'ces-bulle-site-' + VERSION;
const ASSETS_TO_CACHE = [
    './',
    'index.html',
    'css/site.css',
    'favicon.ico',
    'assets/logo bulle.jpeg',
    'assets/historia.png',
    'assets/hero_1.jpg',
    'assets/hero_2.jpg',
    'assets/mensagem.mp4',
    'assets/logo_3d.png'
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    console.log('🚀 SW ' + VERSION + ': INSTALANDO (MODO REORGANIZADO)...');
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
                        console.log('🗑️ SW: Expurgo de cache antigo:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => {
            console.log('🚀 SW ' + VERSION + ': ATIVADO (LIMPO)');
            return self.clients.claim();
        })
    );
});
