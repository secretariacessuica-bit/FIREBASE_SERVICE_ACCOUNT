// list_admins_node.js – Node.js (run with PowerShell)
// Purpose: read‑only audit of Firestore collection "membros"
// Requirements:
//   * firebase-admin (npm install firebase-admin)
//   * GOOGLE_APPLICATION_CREDENTIALS env var pointing to a service‑account JSON key
//   * Uses modular SDK (initializeApp, applicationDefault, deleteApp)
//   * Filters: perfil == "admin" && status == "ativo"
//   * Does NOT expose the value of the "credenciais" field, only reports its existence, type and length.

const { initializeApp, applicationDefault, deleteApp } = require('firebase-admin/app');
const { getFirestore } = require('firebase-admin/firestore');

// Initialize the Admin SDK with default credentials (service‑account key via env var)
const app = initializeApp({
  credential: applicationDefault()
});

const db = getFirestore(app);

(async () => {
  try {
    const snapshot = await db.collection('membros')
      .where('perfil', '==', 'admin')
      .where('status', '==', 'ativo')
      .get();

    if (snapshot.empty) {
      console.log('Nenhum usuário admin ativo encontrado.');
      return;
    }

    snapshot.forEach(doc => {
      const data = doc.data();
      const credInfo = data.hasOwnProperty('credenciais')
        ? {
            exists: true,
            type: typeof data.credenciais,
            size: typeof data.credenciais === 'string' ? data.credenciais.length : 'N/A'
          }
        : { exists: false };

      const output = {
        documentId: doc.id,
        nome: data.nome || null,
        nomeNormalizado: data.nomeNormalizado || null,
        email: data.email || null,
        perfil: data.perfil || null,
        status: data.status || null,
        credenciais: credInfo
      };
      console.log(JSON.stringify(output, null, 2));
    });
  } catch (err) {
    console.error('Erro ao consultar Firestore:', err);
  } finally {
    // Graceful shutdown of the Admin SDK instance
    await deleteApp(app);
  }
})();
