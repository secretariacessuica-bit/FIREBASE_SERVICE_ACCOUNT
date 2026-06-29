// check_credentials.js – read-only: inspect the credenciais collection for both admins
const { initializeApp, applicationDefault, cert, deleteApp } = require('firebase-admin/app');
const { getFirestore } = require('firebase-admin/firestore');
const fs = require('fs');

const keyPath = process.argv[2];
let credential;
if (keyPath) {
  credential = require('firebase-admin/app').cert(JSON.parse(fs.readFileSync(keyPath, 'utf8')));
} else {
  credential = applicationDefault();
}

const app = initializeApp({ credential, projectId: 'diaconia-a38f1' });
const db = getFirestore(app);

(async () => {
  const IDS = ['admin_default', 'QhHFaUPskwaMHhXGekku'];
  try {
    for (const id of IDS) {
      const snap = await db.collection('credenciais').doc(id).get();
      if (!snap.exists) {
        console.log(`[${id}] credenciais: NAO EXISTE`);
      } else {
        const d = snap.data();
        console.log(`[${id}] credenciais EXISTE:`);
        console.log(`  campos presentes: ${Object.keys(d).join(', ')}`);
        console.log(`  passwordHash exists: ${'passwordHash' in d}`);
        console.log(`  passwordHash type: ${typeof d.passwordHash}`);
        console.log(`  passwordHash length: ${typeof d.passwordHash === 'string' ? d.passwordHash.length : 'N/A'}`);
        console.log(`  passwordSalt exists: ${'passwordSalt' in d}`);
        console.log(`  passwordSalt length: ${typeof d.passwordSalt === 'string' ? d.passwordSalt.length : 'N/A'}`);
      }
    }
  } catch (err) {
    console.error('Erro:', err.message || err);
  } finally {
    await deleteApp(app);
  }
})();
