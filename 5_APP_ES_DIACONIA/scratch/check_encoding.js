const fs = require('fs');
const path = require('path');
const baseDir = path.join(__dirname, '..');

// Verificar o arquivo git limpo
const gitClean = fs.readFileSync(path.join(baseDir, 'scratch', 'index_git_clean.html'));
console.log('Git clean file encoding (first 4 bytes hex):', gitClean.slice(0, 4).toString('hex'));

const asUtf8 = gitClean.toString('utf8');
const idx = asUtf8.indexOf('ximos Eventos');
if (idx >= 0) {
    console.log('GIT file context:', JSON.stringify(asUtf8.slice(idx - 10, idx + 20)));
} else {
    console.log('Not found in git file as utf8');
    // Try latin1
    const asLatin1 = gitClean.toString('latin1');
    const idx3 = asLatin1.indexOf('ximos Eventos');
    if (idx3 >= 0) {
        console.log('GIT file context (latin1):', JSON.stringify(asLatin1.slice(idx3 - 10, idx3 + 20)));
    }
}

// Verificar o arquivo atual
const current = fs.readFileSync(path.join(baseDir, 'index.html'));
const curStr = current.toString('utf8');
const idx2 = curStr.indexOf('ximos Eventos');
if (idx2 >= 0) {
    console.log('CURRENT file context:', JSON.stringify(curStr.slice(idx2 - 10, idx2 + 20)));
} else {
    console.log('Not found in current file');
}
