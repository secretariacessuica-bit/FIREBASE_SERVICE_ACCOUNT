const admin = require('firebase-admin');

// Inicialização segura via variável de ambiente contendo o JSON do Service Account
// Isso é injetado pelo GitHub Actions (Repository Secrets)
const serviceAccountJson = process.env.FIREBASE_SERVICE_ACCOUNT;

if (!serviceAccountJson) {
    console.error('ERRO: Variável FIREBASE_SERVICE_ACCOUNT não encontrada. Abortando execução.');
    process.exit(1);
}

const serviceAccount = JSON.parse(serviceAccountJson);

admin.initializeApp({
    credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();
const messaging = admin.messaging();

async function runEngine() {
    console.log(`[${new Date().toISOString()}] Iniciando Motor de Notificações...`);

    const now = new Date();
    const hojeStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;

    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const tomorrowStr = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth()+1).padStart(2,'0')}-${String(tomorrow.getDate()).padStart(2,'0')}`;

    let totalEnviados = 0;
    let tokensRemovidos = 0;

    try {
        // --- 1. BUSCAR TODAS AS ESCALAS PERTINENTES ---
        // Escalas de hoje até 60 dias pra frente
        const future = new Date(now.getTime() + 60 * 24 * 60 * 60 * 1000);
        const futureStr = `${future.getFullYear()}-${String(future.getMonth()+1).padStart(2,'0')}-${String(future.getDate()).padStart(2,'0')}`;

        console.log(`Buscando escalas de ${hojeStr} até ${futureStr}...`);
        
        const escalasSnapshot = await db.collection('escalas')
            .where('data', '>=', hojeStr)
            .where('data', '<=', futureStr)
            .get();

        const membrosParaNotificar = {};

        escalasSnapshot.forEach(doc => {
            const e = { id: doc.id, ...doc.data() };
            
            if (!membrosParaNotificar[e.membroId]) {
                membrosParaNotificar[e.membroId] = { pendentes: [], lembretesAmanha: [] };
            }

            // A. Escalas Pendentes (qualquer data no futuro a partir de hoje)
            if (e.statusPresenca === 'Pendente') {
                membrosParaNotificar[e.membroId].pendentes.push(e);
            }

            // B. Escalas Confirmadas para AMANHÃ (Lembrete)
            if (e.statusPresenca === 'Confirmada' && e.data === tomorrowStr) {
                membrosParaNotificar[e.membroId].lembretesAmanha.push(e);
            }
        });

        // --- 2. BUSCAR TOKENS E DISPARAR ---
        for (const membroId of Object.keys(membrosParaNotificar)) {
            const info = membrosParaNotificar[membroId];
            
            if (info.pendentes.length === 0 && info.lembretesAmanha.length === 0) continue;

            const membroDoc = await db.collection('membros').doc(membroId).get();
            if (!membroDoc.exists) continue;

            const fcmTokens = membroDoc.data().fcmTokens || [];
            if (fcmTokens.length === 0) continue;

            // Formatar Payload Pendentes
            if (info.pendentes.length > 0) {
                // Aqui nós enviaremos o push para pendentes
                const count = info.pendentes.length;
                const first = info.pendentes[0];
                const [y,m,d] = first.data.split('-');
                
                const title = count === 1 ? '⚠️ Escala aguardando confirmação' : `⚠️ ${count} escalas aguardando confirmação`;
                let body = count === 1
                    ? `${first.cultoNome || 'Culto'} em ${d}/${m} às ${first.horarioInicio}\nConfirme sua presença no app.`
                    : `Você tem ${count} escalas pendentes. Abra o app para verificar.`;

                const message = {
                    notification: { title, body },
                    data: { scaleId: first.id, action: 'view' },
                    tokens: fcmTokens
                };

                const response = await messaging.sendMulticast(message);
                totalEnviados += response.successCount;
                
                // Cleanup invalid tokens
                if (response.failureCount > 0) {
                    const failedTokens = [];
                    response.responses.forEach((resp, idx) => {
                        if (!resp.success) {
                            if (resp.error.code === 'messaging/invalid-registration-token' ||
                                resp.error.code === 'messaging/registration-token-not-registered') {
                                failedTokens.push(fcmTokens[idx]);
                            }
                        }
                    });
                    if (failedTokens.length > 0) {
                        await db.collection('membros').doc(membroId).update({
                            fcmTokens: admin.firestore.FieldValue.arrayRemove(...failedTokens)
                        });
                        tokensRemovidos += failedTokens.length;
                    }
                }
            }

            // Formatar Payload Lembretes (Amanhã)
            if (info.lembretesAmanha.length > 0) {
                for (const esc of info.lembretesAmanha) {
                    // Nós precisaremos marcar que o lembrete já foi enviado para não enviar a cada 6h
                    // Para isso, salvaremos no Firestore um array de IDs lembrados, ou simplesmente enviamos.
                    // Para evitar envios repetidos no mesmo dia, salvamos no membro ou na escala.
                    
                    const [y,m,d] = esc.data.split('-');
                    const message = {
                        notification: {
                            title: `📢 Lembrete de Escala Amanhã!`,
                            body: `Culto: ${esc.cultoNome || 'Culto'} às ${esc.horarioInicio}.`
                        },
                        data: { scaleId: esc.id, action: 'view' },
                        tokens: fcmTokens
                    };
                    
                    const response = await messaging.sendMulticast(message);
                    totalEnviados += response.successCount;
                }
            }
        }

        console.log(`[SUCESSO] Disparos concluídos. Notificações entregues: ${totalEnviados}. Tokens inválidos removidos: ${tokensRemovidos}.`);

    } catch (error) {
        console.error('[ERRO] Falha no Motor de Notificações:', error);
        process.exit(1);
    }
}

runEngine();
