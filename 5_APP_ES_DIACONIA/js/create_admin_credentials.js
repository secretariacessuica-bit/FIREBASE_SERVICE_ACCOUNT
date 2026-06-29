// create_admin_credentials.js
// Purpose: create missing credentials for admin_default in Firestore
// Uses same algorithm as db.js: SHA-256(password + salt), salt = 16 random bytes as hex
// Password used: original seed password already visible in db.js line 114

const { initializeApp, applicationDefault, cert, deleteApp } = require('firebase-admin/app');
const { getFirestore } = require('firebase-admin/firestore');
const crypto = require('crypto');
const fs = require('fs');

// Accept service-account key path as optional CLI arg; otherwise rely on env var
const keyPath = process.argv[2];
let credential;
if (keyPath) {
  const serviceAccount = JSON.parse(fs.readFileSync(keyPath, 'utf8'));
  credential = cert(serviceAccount);
} else {
  // Uses GOOGLE_APPLICATION_CREDENTIALS env var
  credential = applicationDefault();
}

// Replicates db.js generateSalt(): 16 bytes -> 32-char hex
function generateSalt() {
  return crypto.randomBytes(16).toString('hex');
}

// Replicates db.js hashPassword(password, salt): SHA-256(password + salt) -> 64-char hex
function hashPassword(password, salt) {
  return crypto.createHash('sha256').update(password + salt).digest('hex');
}

const app = initializeApp({
  credential,
  projectId: 'diaconia-a38f1'
});
const db = getFirestore(app);

(async () => {
  // Seed password already visible in db.js line 114 (not a secret)
  const PASSWORD = 'Ces120222.';
  const ADMIN_IDS = ['admin_default'];

  try {
    for (const adminId of ADMIN_IDS) {
      const credRef = db.collection('credenciais').doc(adminId);
      const credSnap = await credRef.get();

      if (credSnap.exists) {
        console.log(`[${adminId}] Credenciais ja existem - nenhuma alteracao feita.`);
        continue;
      }

      const salt = generateSalt();
      const hash = hashPassword(PASSWORD, salt);

      await credRef.set({ passwordHash: hash, passwordSalt: salt });

      console.log(`[${adminId}] Credenciais criadas com sucesso.`);
      console.log(`  salt (32 chars): ${salt}`);
      console.log(`  hash (64 chars): ${hash}`);
    }

    console.log('\nConcluido. Tente login com:');
    console.log('  Identificacao: Supervisor  Wan');
    console.log('  Senha: [senha do seed - Ces120222.]');
  } catch (err) {
    console.error('Erro ao criar credenciais:', err.message || err);
  } finally {
    await deleteApp(app);
  }
})();
