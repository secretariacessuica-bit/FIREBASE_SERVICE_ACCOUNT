/**
 * Restaura index.html da versão limpa do git e reaplica as edições necessárias.
 */
const fs = require('fs');
const path = require('path');
const baseDir = path.join(__dirname, '..');

// 1. Ler a versão limpa do git
let content = fs.readFileSync(path.join(baseDir, 'scratch', 'index_git_clean.html'), 'utf8');
console.log('Loaded git clean version. Length:', content.length);

// 2. Verificar que o texto está correto
if (content.includes('Próximos Eventos')) {
    console.log('✓ Text "Próximos Eventos" is correct in git version');
} else {
    console.log('✗ WARNING: Text not found correctly in git version!');
}

// 3. Verificar e aplicar: versão do cache app.js
if (content.includes('app.js?v=')) {
    // Detectar a versão atual no git
    const vMatch = content.match(/app\.js\?v=([0-9.]+)/);
    console.log('Git app.js version:', vMatch ? vMatch[1] : 'not found');
    content = content.replace(/app\.js\?v=[0-9.]+/, 'app.js?v=3.7.6');
    console.log('✓ Updated app.js cache to v3.7.6');
}

// 4. Verificar e aplicar: classe do modal-escala-rascunho
if (content.includes('modal-escala-rascunho')) {
    const modalMatch = content.match(/id="modal-escala-rascunho"[^>]*class="([^"]+)"/);
    const modalMatch2 = content.match(/id="modal-escala-rascunho" class="([^"]+)"/);
    console.log('Modal class in git:', modalMatch ? modalMatch[1] : (modalMatch2 ? modalMatch2[1] : 'checking other format'));
    
    // Verificar se já tem a classe correta
    if (content.includes('id="modal-escala-rascunho" class="premium-modal glass-modal"')) {
        console.log('✓ Modal already has correct classes in git version');
    } else if (content.includes('modal-escala-rascunho')) {
        // Substituir classe modal-overlay pela classe correta
        content = content.replace(
            /(<div\s+id="modal-escala-rascunho"\s+class=")[^"]+(")/,
            '$1premium-modal glass-modal$2'
        );
        console.log('✓ Fixed modal class to premium-modal glass-modal');
    }
}

// 5. Salvar o arquivo corrigido
fs.writeFileSync(path.join(baseDir, 'index.html'), content, 'utf8');
console.log('\n✓ index.html restored and updated successfully!');

// 6. Verificação final
const final = fs.readFileSync(path.join(baseDir, 'index.html'), 'utf8');
console.log('Final check - Próximos Eventos:', final.includes('Próximos Eventos') ? '✓ CORRECT' : '✗ STILL BROKEN');
console.log('Final check - app.js version:', final.match(/app\.js\?v=[0-9.]+/)?.[0]);
console.log('Final check - modal class:', final.includes('premium-modal glass-modal') ? '✓ CORRECT' : '? checking');
