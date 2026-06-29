/**
 * audit_collections.js
 *
 * Audita as coleções candidatas à limpeza no projeto diaconia-a38f1.
 * Usa applicationDefault() — NÃO salva a chave dentro do repositório.
 *
 * Pré-requisito:
 *   $env:GOOGLE_APPLICATION_CREDENTIALS = "C:\Users\Wande\Documents\firebase-secrets\diaconia-serviceAccountKey.json"
 *   node js/audit_collections.js
 *
 * Restrições: SOMENTE leitura. Nenhum dado será alterado ou removido.
 */

const admin = require('firebase-admin');

// Inicialização usando credencial externa (via variável de ambiente)
admin.initializeApp({
  credential: admin.credential.applicationDefault(),
  projectId: 'diaconia-a38f1',
});

const db = admin.firestore();

// Coleções a auditar
const COLLECTIONS_TO_AUDIT = [
  'escalas_arquivadas',
  'mensagens_supervisao',
];

// Campos esperados em documentos reais (para diferenciar de dados de teste)
const EXPECTED_FIELDS = {
  escalas_arquivadas: ['data', 'setor', 'membros', 'status'],
  mensagens_supervisao: ['autor', 'texto', 'timestamp', 'setor'],
};

async function auditCollection(name) {
  console.log(`\n${'='.repeat(60)}`);
  console.log(`COLEÇÃO: ${name}`);
  console.log('='.repeat(60));

  try {
    const snapshot = await db.collection(name).get();

    if (snapshot.empty) {
      console.log('  ✅ Coleção existe mas está VAZIA (0 documentos).');
      return { name, exists: true, count: 0, verdict: 'VAZIA' };
    }

    const count = snapshot.size;
    console.log(`  📄 Documentos encontrados: ${count}`);

    // Analisa os primeiros 3 documentos para verificar campos
    const samples = snapshot.docs.slice(0, 3);
    const expected = EXPECTED_FIELDS[name] || [];

    let realDataCount = 0;
    let testDataCount = 0;

    for (const doc of samples) {
      const data = doc.data();
      const fields = Object.keys(data);
      const hasExpectedFields = expected.some(f => fields.includes(f));

      if (hasExpectedFields) {
        realDataCount++;
      } else {
        testDataCount++;
      }

      // Exibe campos do documento sem expor valores sensíveis
      console.log(`\n  → Doc ID: ${doc.id}`);
      console.log(`    Campos presentes: [${fields.join(', ')}]`);

      // Exibe apenas campos não-sensíveis (nunca senhas, tokens FCM, etc.)
      const SAFE_FIELDS = ['data', 'dataArquivo', 'setor', 'status', 'tipo', 'timestamp', 'autor'];
      for (const f of SAFE_FIELDS) {
        if (data[f] !== undefined) {
          console.log(`    ${f}: ${JSON.stringify(data[f])}`);
        }
      }
    }

    // Veredicto
    let verdict;
    if (realDataCount > 0) {
      verdict = 'DADOS REAIS — NÃO REMOVER SEM BACKUP';
    } else if (count > 0 && realDataCount === 0) {
      verdict = 'POSSÍVEL DADO DE TESTE — verificar manualmente';
    } else {
      verdict = 'INCONCLUSIVO';
    }

    console.log(`\n  📊 Veredicto: ${verdict}`);
    return { name, exists: true, count, verdict };

  } catch (err) {
    if (err.code === 5 || err.message.includes('NOT_FOUND')) {
      console.log('  ❌ Coleção NÃO ENCONTRADA no projeto diaconia-a38f1.');
      return { name, exists: false, count: 0, verdict: 'NÃO EXISTE' };
    }
    console.error(`  ⚠️  Erro inesperado: ${err.message}`);
    return { name, exists: false, count: 0, verdict: `ERRO: ${err.message}` };
  }
}

async function main() {
  console.log('\n🔍 AUDITORIA DE COLEÇÕES — diaconia-a38f1');
  console.log('   Modo: SOMENTE LEITURA — nenhum dado será alterado\n');

  const results = [];
  for (const col of COLLECTIONS_TO_AUDIT) {
    const result = await auditCollection(col);
    results.push(result);
  }

  // Tabela resumo
  console.log('\n\n' + '='.repeat(60));
  console.log('RESUMO FINAL');
  console.log('='.repeat(60));
  console.log(
    '  Coleção'.padEnd(28) +
    'Existe'.padEnd(10) +
    'Docs'.padEnd(8) +
    'Veredicto'
  );
  console.log('  ' + '-'.repeat(58));
  for (const r of results) {
    console.log(
      ('  ' + r.name).padEnd(28) +
      (r.exists ? 'SIM' : 'NÃO').padEnd(10) +
      String(r.count).padEnd(8) +
      r.verdict
    );
  }
  console.log('='.repeat(60) + '\n');

  process.exit(0);
}

main().catch(err => {
  console.error('Erro fatal:', err);
  process.exit(1);
});
