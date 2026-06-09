const functions = require('firebase-functions');
const admin = require('firebase-admin');
const cors = require('cors')({ origin: true });
const crypto = require('crypto');

admin.initializeApp();

const db = admin.firestore();

// --- CRYPTO HELPERS ---
function normalizeStr(str) {
    return (str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
}

function generateSalt() {
    return crypto.randomBytes(16).toString('hex');
}

function hashPassword(password, salt) {
    return crypto.createHash('sha256').update(password + salt).digest('hex');
}

// --- CLOUD FUNCTIONS ---

// 1. SECURE LOGIN AND MIGRATION
exports.login = functions.https.onRequest((req, res) => {
    return cors(req, res, async () => {
        if (req.method !== 'POST') {
            return res.status(405).json({ error: 'Method Not Allowed' });
        }

        const { nome, senha } = req.body;
        if (!nome || !senha) {
            return res.status(400).json({ error: 'Nome e Senha são obrigatórios' });
        }

        try {
            const nomeNorm = normalizeStr(nome);

            // Buscar membro ativo pelo nome normalizado
            const snap = await db.collection('membros')
                .where('nomeNormalizado', '==', nomeNorm)
                .where('status', '==', 'ativo')
                .limit(1)
                .get();

            let matchedDoc = null;
            let mData = null;

            if (!snap.empty) {
                matchedDoc = snap.docs[0];
                mData = matchedDoc.data();
            } else {
                // Fallback legado temporário: buscar varrendo todos os membros ativos
                console.log(`[Login] Nome normalizado '${nomeNorm}' não encontrado diretamente. Executando varredura...`);
                const allActiveSnap = await db.collection('membros')
                    .where('status', '==', 'ativo')
                    .get();

                allActiveSnap.forEach(doc => {
                    const docData = doc.data();
                    if (normalizeStr(docData.nome) === nomeNorm) {
                        matchedDoc = doc;
                        mData = docData;
                    }
                });
            }

            if (!matchedDoc) {
                return res.status(404).json({ error: 'Nome não encontrado. Verifique se digitou o nome completo.' });
            }

            const membroId = matchedDoc.id;
            const credRef = db.collection('credenciais').doc(membroId);
            const credSnap = await credRef.get();

            let passwordMatch = false;
            let needsMigration = false;

            if (credSnap.exists) {
                const credData = credSnap.data();
                const computedHash = hashPassword(senha, credData.passwordSalt);
                if (computedHash === credData.passwordHash) {
                    passwordMatch = true;
                }
            } else if (mData.senha) {
                // Senha legado em texto plano
                if (mData.senha === senha) {
                    passwordMatch = true;
                    needsMigration = true;
                }
            }

            if (!passwordMatch) {
                return res.status(401).json({ error: 'Senha incorreta.' });
            }

            // Realizar migração híbrida segura no backend
            if (needsMigration) {
                const salt = generateSalt();
                const hash = hashPassword(senha, salt);

                const batch = db.batch();
                // 1. Criar credencial com hash e salt
                batch.set(credRef, {
                    passwordHash: hash,
                    passwordSalt: salt
                });
                // 2. Remover senha em texto plano e gravar nomeNormalizado
                batch.update(db.collection('membros').doc(membroId), {
                    nomeNormalizado: nomeNorm,
                    senha: admin.firestore.FieldValue.delete()
                });
                await batch.commit();
                console.log(`[Segurança] Membro '${mData.nome}' migrado com sucesso para Salted SHA-256!`);
            } else if (!mData.nomeNormalizado && mData.nome) {
                // Apenas atualizar nome normalizado se estiver ausente
                await db.collection('membros').doc(membroId).update({
                    nomeNormalizado: nomeNorm
                });
            }

            // Gerar Token Customizado do Firebase Auth com claims de perfil
            const customToken = await admin.auth().createCustomToken(membroId, {
                role: mData.perfil || 'membro'
            });

            return res.json({
                success: true,
                token: customToken,
                user: {
                    id: membroId,
                    nome: mData.nome,
                    email: mData.email,
                    perfil: mData.perfil,
                    setor: mData.setor,
                    setores: mData.setores || (mData.setor ? [mData.setor] : []),
                    funcao: mData.funcao,
                    fotoUrl: mData.fotoUrl || null,
                    eRepositor: mData.eRepositor || false
                }
            });

        } catch (e) {
            console.error('Erro na autenticação:', e);
            return res.status(500).json({ error: 'Erro interno no servidor de autenticação.' });
        }
    });
});

// 2. SECURE PASSWORD RESET
exports.resetPassword = functions.https.onRequest((req, res) => {
    return cors(req, res, async () => {
        if (req.method !== 'POST') {
            return res.status(405).json({ error: 'Method Not Allowed' });
        }

        const { adminUid, membroId, novaSenha } = req.body;
        if (!adminUid || !membroId || !novaSenha) {
            return res.status(400).json({ error: 'adminUid, membroId e novaSenha são obrigatórios.' });
        }

        try {
            // Verificar autorização do administrador emissor
            const adminDoc = await db.collection('membros').doc(adminUid).get();
            if (!adminDoc.exists || adminDoc.data().perfil !== 'admin') {
                return res.status(403).json({ error: 'Acesso negado. Apenas administradores podem redefinir senhas.' });
            }

            const salt = generateSalt();
            const hash = hashPassword(novaSenha, salt);

            const batch = db.batch();
            // Salvar novo hash e salt
            batch.set(db.collection('credenciais').doc(membroId), {
                passwordHash: hash,
                passwordSalt: salt
            });
            // Garantir remoção de senha legada
            batch.update(db.collection('membros').doc(membroId), {
                senha: admin.firestore.FieldValue.delete()
            });

            await batch.commit();
            console.log(`[Segurança] Senha do membro ID ${membroId} redefinida pelo admin ${adminUid}.`);

            return res.json({ success: true });
        } catch (e) {
            console.error('Erro ao redefinir senha:', e);
            return res.status(500).json({ error: 'Erro interno ao processar redefinição de senha.' });
        }
    });
});
