// CES Diaconia - Service Worker de Notificações (v3.10.5U)
// Controla recebimento e exibição de push notifications via Firebase Cloud Messaging

importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');

const firebaseConfigProd = {
    apiKey: "AIzaSyDqlZ5pukg4NAg2mqMjAzAcRJCIeNN_K24",
    authDomain: "diaconia-a38f1.firebaseapp.com",
    projectId: "diaconia-a38f1",
    storageBucket: "diaconia-a38f1.firebasestorage.app",
    messagingSenderId: "489746524173",
    appId: "1:489746524173:web:f0eead38951fb738364d44"
};

firebase.initializeApp(firebaseConfigProd);
const messaging = firebase.messaging();

const SW_VERSION = 'ces-diaconia-sw-v3.10.5U-FCM';
const APP_URL = '/';

// ── Instalação ──────────────────────────────────────────────
self.addEventListener('install', event => {
    console.log('[SW FCM] Instalado:', SW_VERSION);
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('[SW FCM] Ativado:', SW_VERSION);
    event.waitUntil(clients.claim());
});

// ── Receber mensagens via FCM Background ─────────────────────
messaging.onBackgroundMessage((payload) => {
    console.log('[SW FCM] Recebeu push em background:', payload);
    
    const notificationTitle = payload.notification?.title || 'Novo Aviso na Escala';
    const notificationOptions = {
        body: payload.notification?.body,
        icon: '/assets/logo.png',
        badge: '/assets/logo.png',
        requireInteraction: true,
        vibrate: [200, 100, 200, 100, 400],
        data: payload.data || {}
    };

    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// ── Click na notificação ──────────────────────────────────────
self.addEventListener('notificationclick', event => {
    const notification = event.notification;
    notification.close();

    const data = notification.data || {};
    const action = event.action; // if there are action buttons
    const scaleId = data.scaleId;

    let targetUrl = APP_URL;
    if (scaleId) {
        targetUrl = `${APP_URL}?action=view&scaleId=${scaleId}`;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            for (const client of clientList) {
                if (client.url.includes(self.location.origin)) {
                    client.focus();
                    client.postMessage({
                        type: 'NOTIFICATION_ACTION',
                        action: action || 'view',
                        scaleId: scaleId
                    });
                    return;
                }
            }
            return clients.openWindow(targetUrl);
        })
    );
});

// ── Fechar notificação ────────────────────────────────────────
self.addEventListener('notificationclose', event => {
    console.log('[SW FCM] Notificação fechada pelo usuário.');
});
