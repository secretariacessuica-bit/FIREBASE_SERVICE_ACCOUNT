/**
 * Detecta e corrige mojibake em arquivos de texto.
 * 
 * Mojibake simples:  bytes UTF-8 lidos como Latin-1
 *   ex: "Próximo" salvo como UTF-8 → lido como Latin-1 → aparece como "PrÃ³ximo"
 *   Correção: reinterpretar a string Latin-1 como bytes UTF-8
 *
 * Duplo mojibake: o processo acima aplicado duas vezes
 *   ex: "PrÃ³ximo" → "PrÃÂ³ximo"
 *   Correção: aplicar a correção duas vezes
 */
const fs = require('fs');
const path = require('path');

const baseDir = path.join(__dirname, '..');

function fixOnce(str) {
    return Buffer.from(str, 'latin1').toString('utf8');
}

function detectAndFix(filePath) {
    const buf = fs.readFileSync(filePath);
    let content = buf.toString('utf8');

    // Detecta duplo mojibake (ex: ÃÂ³ = ó duplo corrompido)
    const doubleMojibake = /ÃÂ|Ã‚|Ã€|ÃƒÂ/.test(content);
    // Detecta mojibake simples (ex: Ã³ = ó corrompido)
    const singleMojibake = /Ã[²³°¡¢£¤¥¦§¨©ª«¬­®¯±´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ]/.test(content);

    if (doubleMojibake) {
        // Desfaz duplo: aplicar fixOnce duas vezes
        const step1 = fixOnce(content);
        const step2 = fixOnce(step1);
        fs.writeFileSync(filePath, step2, 'utf8');
        console.log('Fixed (double mojibake): ' + path.relative(baseDir, filePath));
    } else if (singleMojibake) {
        const fixed = fixOnce(content);
        fs.writeFileSync(filePath, fixed, 'utf8');
        console.log('Fixed (single mojibake): ' + path.relative(baseDir, filePath));
    } else {
        console.log('OK (clean): ' + path.relative(baseDir, filePath));
    }
}

const files = [
    'index.html',
    'js/app.js',
    'js/db.js',
    'js/firebase-config.js',
    'css/style.css'
];

files.forEach(f => {
    const fullPath = path.join(baseDir, f);
    if (fs.existsSync(fullPath)) {
        try {
            detectAndFix(fullPath);
        } catch (e) {
            console.log('Error on ' + f + ': ' + e.message);
        }
    } else {
        console.log('Not found: ' + f);
    }
});

console.log('\nDone! Verifying index.html...');
const html = fs.readFileSync(path.join(baseDir, 'index.html'), 'utf8');
const match = html.match(/Pr.{0,5}ximos Eventos/);
console.log('Sample text found: ' + (match ? match[0] : '(not found)'));
