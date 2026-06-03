const fs = require('fs');
const path = require('path');
const baseDir = path.join(__dirname, '..');

// Check CSS for mojibake
const css = fs.readFileSync(path.join(baseDir, 'css', 'style.css'), 'utf8');
const badMatch = css.match(/Ã[²³°]/);
console.log('CSS mojibake check:', badMatch ? 'FOUND: ' + badMatch[0] : 'CLEAN - OK');

// Check app.js syntax (quick)
const app = fs.readFileSync(path.join(baseDir, 'js', 'app.js'), 'utf8');
const appBad = app.match(/Ã[²³°]/);
console.log('app.js mojibake check:', appBad ? 'FOUND: ' + appBad[0] : 'CLEAN - OK');

// Check index.html final
const html = fs.readFileSync(path.join(baseDir, 'index.html'), 'utf8');
const htmlBad = html.match(/Ã[²³°¡-¿À-ÿ]/g);
console.log('index.html mojibake check:', htmlBad ? 'FOUND ' + htmlBad.length + ' instances' : 'CLEAN - OK');
console.log('index.html "Próximos Eventos":', html.includes('Próximos Eventos') ? 'CORRECT' : 'MISSING');
