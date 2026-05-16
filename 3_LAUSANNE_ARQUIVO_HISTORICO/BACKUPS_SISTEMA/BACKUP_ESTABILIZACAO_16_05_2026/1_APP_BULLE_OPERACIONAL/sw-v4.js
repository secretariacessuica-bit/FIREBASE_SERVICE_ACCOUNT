const VERSION = 'v71.0.0';
const CACHE_NAME = 'catedral-connect-' + VERSION;
const ASSETS_TO_CACHE = [
    './',
    'index.html',
    'portal.html',
    'app.html',
    'mobile.html',
    'admin.html',
    'altar.html',
    'integracao_v2.html',
    'recepcao.html',
    'connect.html',
    'kids.html',
    'css/style-v2.css',
    'css/mobile-v2.css',
    'js/auth-manager.js',
    'js/firebase-config.js',
    'js/reception_kids_logic.js'
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    console.log('🚀 SW ' + VERSION + ': INSTALANDO (MODO BARE)...');
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

// BARE MODE: No fetch interception to ensure maximum compatibility with Google Auth APIs.
// The fetch listener is omitted to silence 'no-op' browser warnings.
