// setup_diaconia.js — Atualiza todos os membros no Firestore via REST API
// Projeto: catedral-connect-267b2 | Coleção: diaconia_escala_membros

const https = require('https');

const PROJECT_ID = 'catedral-connect-267b2';
const API_KEY    = 'AIzaSyAtwHODax7kq0keaLuON1ZxbNfdaBP7yfo';
const COLECAO    = 'diaconia_escala_membros';

const SENHA_ADMIN   = 'Ces120222.';
const SENHA_MEMBROS = 'ces2025';

const BASE = `https://firestore.googleapis.com/v1/projects/${PROJECT_ID}/databases/(default)/documents`;

// ── helpers ──────────────────────────────────────────────────────────────────
function req(method, path, body) {
    return new Promise((resolve, reject) => {
        const url  = `${BASE}${path}?key=${API_KEY}`;
        const data = body ? JSON.stringify(body) : null;
        const urlObj = new URL(url);

        const options = {
            hostname: urlObj.hostname,
            path:     urlObj.pathname + urlObj.search,
            method,
            headers: {
                'Content-Type': 'application/json',
                ...(data ? { 'Content-Length': Buffer.byteLength(data) } : {})
            }
        };

        const r = https.request(options, res => {
            let raw = '';
            res.on('data', c => raw += c);
            res.on('end', () => {
                try { resolve(JSON.parse(raw)); }
                catch { resolve(raw); }
            });
        });
        r.on('error', reject);
        if (data) r.write(data);
        r.end();
    });
}

function strVal(v)   { return v?.stringValue  ?? null; }
function arrVal(v)   { return v?.arrayValue?.values?.map(x => strVal(x)) ?? null; }
function getSetor(f) {
    if (f.setores) return arrVal(f.setores);
    if (f.setor)   return strVal(f.setor);
    return null;
}
const SETORES = {
    diaconia_templo:        'Diaconia do Templo',
    acolhimento_integracao: 'Acolhimento e Integração',
    limpeza:                'Limpeza',
    manutencao:             'Manutenção',
};
function labelSetor(s) {
    if (!s) return '—';
    if (Array.isArray(s)) return s.map(x => SETORES[x] || x).join(', ');
    return SETORES[s] || s;
}

// ── main ─────────────────────────────────────────────────────────────────────
async function main() {
    console.log('\n══════════════════════════════════════════════════');
    console.log('  Setup Oficial — CES Diaconia Lausanne');
    console.log('══════════════════════════════════════════════════\n');

    console.log('🔗 Conectando ao Firestore...');
    console.log(`📦 Coleção: ${COLECAO}\n`);

    // 1. Buscar todos os membros
    let allDocs = [];
    let pageToken = null;
    do {
        const ptParam = pageToken ? `&pageToken=${encodeURIComponent(pageToken)}` : '';
        const fullUrl = `https://firestore.googleapis.com/v1/projects/${PROJECT_ID}/databases/(default)/documents/${COLECAO}?pageSize=100${ptParam}&key=${API_KEY}`;

        const result = await new Promise((resolve, reject) => {
            const u = new URL(fullUrl);
            const opts = { hostname: u.hostname, path: u.pathname + u.search, method: 'GET' };
            const r = https.request(opts, res => {
                let raw = '';
                res.on('data', c => raw += c);
                res.on('end', () => { try { resolve(JSON.parse(raw)); } catch { resolve({}); } });
            });
            r.on('error', reject);
            r.end();
        });
        
        if (result.error) {
            console.error('❌ Erro ao buscar membros:', result.error.message);
            process.exit(1);
        }

        if (result.documents) allDocs.push(...result.documents);
        pageToken = result.nextPageToken || null;
    } while (pageToken);

    if (allDocs.length === 0) {
        console.log('⚠️  Nenhum membro encontrado no Firestore.');
        console.log('    Acesse o app uma vez para criar o admin padrão.');
        return;
    }

    console.log(`👥 Total de membros encontrados: ${allDocs.length}\n`);

    const resumo = [];
    const promises = [];

    for (const doc of allDocs) {
        const f      = doc.fields || {};
        const nome   = strVal(f.nome)   || '(sem nome)';
        const perfil = strVal(f.perfil) || 'membro';
        const status = strVal(f.status) || 'ativo';
        const funcao = strVal(f.funcao) || '—';
        const setor  = getSetor(f);

        const novaSenha = (perfil === 'admin') ? SENHA_ADMIN : SENHA_MEMBROS;

        const tag = perfil === 'admin' ? '[ADMIN] ' : '[MEMBRO]';
        console.log(`  ${tag}  ${nome.padEnd(36)} → ${novaSenha}`);

        // Extrair document ID do nome do doc
        const docPath = doc.name.replace(`projects/${PROJECT_ID}/databases/(default)/documents/`, '');

        // PATCH para atualizar só o campo senha
        const patchUrl = `https://firestore.googleapis.com/v1/projects/${PROJECT_ID}/databases/(default)/documents/${docPath}?updateMask.fieldPaths=senha&key=${API_KEY}`;
        
        promises.push(
            new Promise((resolve, reject) => {
                const body = JSON.stringify({ fields: { senha: { stringValue: novaSenha } } });
                const u = new URL(patchUrl);
                const opts = {
                    hostname: u.hostname,
                    path: u.pathname + u.search,
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(body) }
                };
                const r = https.request(opts, res => {
                    res.on('data', () => {});
                    res.on('end', resolve);
                });
                r.on('error', reject);
                r.write(body);
                r.end();
            })
        );

        resumo.push({ nome, perfil, setor, funcao, senha: novaSenha, status });
    }

    console.log('\n⏳ Aplicando senhas no Firestore...');
    await Promise.all(promises);

    console.log('\n══════════════════════════════════════════════════');
    console.log('✅  CONCLUÍDO! Todas as senhas foram atualizadas.');
    console.log('══════════════════════════════════════════════════\n');

    // Tabela final
    const col1 = Math.max(...resumo.map(r => r.nome.length), 4) + 2;
    const col2 = 8;
    const col3 = Math.max(...resumo.map(r => labelSetor(r.setor).length), 5) + 2;
    const col4 = 12;

    const sep = '─'.repeat(col1 + col2 + col3 + col4 + 13);
    console.log(' TABELA DE ACESSO\n ' + sep);
    console.log(
        ' ' +
        'NOME'.padEnd(col1) + ' │ ' +
        'PERFIL'.padEnd(col2) + ' │ ' +
        'SETOR'.padEnd(col3) + ' │ ' +
        'SENHA'
    );
    console.log(' ' + sep);

    // Admins primeiro, depois membros, ambos por nome
    const sorted = [...resumo].sort((a, b) => {
        if (a.perfil === b.perfil) return a.nome.localeCompare(b.nome, 'pt');
        return a.perfil === 'admin' ? -1 : 1;
    });

    for (const r of sorted) {
        const inativo = r.status !== 'ativo' ? ' (inativo)' : '';
        console.log(
            ' ' +
            (r.nome + inativo).padEnd(col1) + ' │ ' +
            r.perfil.padEnd(col2) + ' │ ' +
            labelSetor(r.setor).padEnd(col3) + ' │ ' +
            r.senha
        );
    }

    console.log(' ' + sep);
    console.log(`\n  Total: ${resumo.length} membro(s)\n`);
    console.log(`  🌐 App: https://catedral-connect-267b2.web.app\n`);
}

main().catch(e => {
    console.error('\n❌ Erro inesperado:', e.message);
    process.exit(1);
});
