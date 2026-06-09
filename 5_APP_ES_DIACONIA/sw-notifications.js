// CES Diaconia - Service Worker de Notificações (v3.6.33)
// Gerencia alertas de escala pendente e lembretes de escalas confirmadas.

const SW_VERSION = 'ces-diaconia-sw-v3.6.35';
const APP_URL = '/';

// ── Instalação ──────────────────────────────────────────────
self.addEventListener('install', event => {
    console.log('[SW] Instalado:', SW_VERSION);
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('[SW] Ativado:', SW_VERSION);
    event.waitUntil(clients.claim());
});

// ── Receber mensagem do app principal ────────────────────────
self.addEventListener('message', event => {
    const data = event.data;
    if (!data) return;

    if (data.type === 'SHOW_PENDING_NOTIFICATION') {
        const promise = showPendingNotification(data.scales);
        if (event.waitUntil) event.waitUntil(promise);
    }

    if (data.type === 'SHOW_CONFIRMED_REMINDER_NOTIFICATION') {
        const promise = showConfirmedReminderNotification(data.scale);
        if (event.waitUntil) event.waitUntil(promise);
    }

    if (data.type === 'CLEAR_NOTIFICATIONS') {
        const promise = self.registration.getNotifications().then(notifications => {
            notifications.forEach(n => n.close());
        });
        if (event.waitUntil) event.waitUntil(promise);
    }
});

// ── Exibir notificação de escala pendente ────────────────────
function showPendingNotification(scales) {
    if (!scales || scales.length === 0) return Promise.resolve();

    const count = scales.length;
    const firstScale = scales[0];

    const title = count === 1
        ? `⚠️ Escala aguardando confirmação`
        : `⚠️ ${count} escalas aguardando confirmação`;

    let body = '';
    if (count === 1) {
        body = `${firstScale.cultoNome || 'Culto'} em ${firstScale.dataFmt} às ${firstScale.horarioInicio}\nConfirme ou recuse sua presença.`;
    } else {
        body = scales.slice(0, 2).map(s => `• ${s.cultoNome || 'Culto'} – ${s.dataFmt}`).join('\n');
        if (count > 2) body += `\n...e mais ${count - 2} escala(s).`;
    }

    const options = {
        body,
        icon: '/assets/logo.png',
        badge: '/assets/logo.png',
        tag: 'ces-diaconia-pendente',       // sobrescreve notificação anterior do mesmo tipo
        renotify: true,                      // toca o som mesmo se a tag já existir
        requireInteraction: true,            // fica visível até o usuário interagir
        vibrate: [200, 100, 200, 100, 400],
        data: { url: APP_URL, scaleId: firstScale.id },
        actions: [
            { action: 'confirm', title: '✅ Confirmar Presença' },
            { action: 'refuse',  title: '❌ Não poderei ir' }
        ]
    };

    return self.registration.showNotification(title, options);
}

// ── Exibir notificação de lembrete de escala confirmada ──────
function showConfirmedReminderNotification(scale) {
    if (!scale) return Promise.resolve();

    const title = `📢 Lembrete de Escala Amanhã!`;
    const body = `Você tem escala confirmada no "${scale.cultoNome || 'Culto'}" amanhã às ${scale.horarioInicio || ''}.\nFunção: ${scale.funcao || ''}.`;

    const options = {
        body,
        icon: '/assets/logo.png',
        badge: '/assets/logo.png',
        tag: `ces-diaconia-confirmada-${scale.id}`,
        renotify: true,
        requireInteraction: true,
        vibrate: [100, 50, 100],
        data: { url: APP_URL, scaleId: scale.id }
    };

    return self.registration.showNotification(title, options);
}

// ── Click na notificação ──────────────────────────────────────
self.addEventListener('notificationclick', event => {
    const notification = event.notification;
    notification.close();

    const action = event.action;
    const scaleId = notification.data ? notification.data.scaleId : null;

    let targetUrl = APP_URL;
    if (action === 'confirm' && scaleId) {
        targetUrl = `${APP_URL}?action=confirm&scaleId=${scaleId}`;
    } else if (action === 'refuse' && scaleId) {
        targetUrl = `${APP_URL}?action=refuse&scaleId=${scaleId}`;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            // Se o app já está aberto, foca e envia mensagem
            for (const client of clientList) {
                if (client.url.includes(self.location.origin)) {
                    client.focus();
                    client.postMessage({
                        type: 'NOTIFICATION_ACTION',
                        action,
                        scaleId,
                        targetUrl
                    });
                    return;
                }
            }
            // Se não está aberto, abre o app
            return clients.openWindow(targetUrl);
        })
    );
});

// ── Fechar notificação ────────────────────────────────────────
self.addEventListener('notificationclose', event => {
    console.log('[SW] Notificação fechada pelo usuário.');
});
