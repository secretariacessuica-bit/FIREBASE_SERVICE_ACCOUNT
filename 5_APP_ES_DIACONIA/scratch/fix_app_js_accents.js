const fs = require('fs');

const filePath = 'c:/Users/Wande/Documents/ia/5_APP_ES_DIACONIA/js/app.js';
let content = fs.readFileSync(filePath, 'utf8');

// Substituições mapeadas usando escapes Unicode para segurança de codificação
const replacements = [
    { from: 'volunt\uFFFDriosAtivos', to: 'voluntariosAtivos' },
    { from: 'volunt\uFFFDrios', to: 'voluntários' },
    { from: 'volunt\uFFFDrio', to: 'voluntário' },
    { from: 'volunt\uFFFDria', to: 'voluntária' },
    { from: 'dispon\uFFFDvel', to: 'disponível' },
    { from: 'Dispon\uFFFDvel', to: 'Disponível' },
    { from: 'per\uFFFDodo', to: 'período' },
    { from: 'VOC\uFFFD EST\uFFFD', to: 'VOCÊ ESTÁ' },
    { from: 'fun\uFFFD\uFFFDo', to: 'função' },
    { from: 'Fun\uFFFD\uFFFDo', to: 'Função' },
    { from: 'fun\uFFFD\uFFFDes', to: 'funções' },
    { from: 'Fun\uFFFD\uFFFDes', to: 'Funções' },
    { from: 'presen\uFFFD\uFFFD', to: 'presença' },
    { from: 'se\uFFFD\uFFFDo', to: 'seção' },
    { from: 'Ning\uFFFDm', to: 'Ninguém' },
    { from: 'm\uFFFDs', to: 'mês' },
    { from: 'ATEN\uFFFD\uFFFD O', to: 'ATENÇÃO' },
    { from: 'ser\uFFFD o', to: 'serão' },
    { from: 'exclu\uFFFD das', to: 'excluídas' },
    { from: '\uFFFDs', to: 'às' },
    { from: 'Voc\uFFFD', to: 'Você' },
    { from: 'voc\uFFFD', to: 'você' },
    { from: 'Relat\uFFFDrio', to: 'Relatório' },
    { from: 'relat\uFFFDrio', to: 'relatório' },
    { from: 'Usu\uFFFDrio', to: 'Usuário' },
    { from: 'usu\uFFFDrio', to: 'usuário' }
];

replacements.forEach(r => {
    // Escapa caracteres especiais se houver
    const escapedFrom = r.from.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    const regex = new RegExp(escapedFrom, 'g');
    content = content.replace(regex, r.to);
});

// Adiciona também substituições de caracteres  avulsos baseadas em palavras específicas conhecidas
content = content.replace(/volunt\uFFFDrio/g, 'voluntário');
content = content.replace(/volunt\uFFFDria/g, 'voluntária');
content = content.replace(/volunt\uFFFDrios/g, 'voluntários');
content = content.replace(/dispon\uFFFDvel/g, 'disponível');
content = content.replace(/Dispon\uFFFDvel/g, 'Disponível');
content = content.replace(/fun\uFFFD\uFFFDo/g, 'função');
content = content.replace(/Fun\uFFFD\uFFFDo/g, 'Função');
content = content.replace(/presen\uFFFD\uFFFD/g, 'presença');
content = content.replace(/se\uFFFD\uFFFDo/g, 'seção');
content = content.replace(/Ning\uFFFDm/g, 'Ninguém');
content = content.replace(/m\uFFFDs/g, 'mês');
content = content.replace(/ATEN\uFFFD\uFFFD\uFFFD\uFFFD O/g, 'ATENÇÃO:');
content = content.replace(/ATEN\uFFFD\uFFFD O/g, 'ATENÇÃO');
content = content.replace(/ser\uFFFD\uFFFD/g, 'serão');
content = content.replace(/ser\uFFFD/g, 'serão');
content = content.replace(/exclu\uFFFD\uFFFD/g, 'excluídas');
content = content.replace(/exclu\uFFFD/g, 'excluídas');
content = content.replace(/ \uFFFDs /g, ' às ');
content = content.replace(/Voc\uFFFD/g, 'Você');
content = content.replace(/voc\uFFFD/g, 'você');

// Se sobrou algum caractere  que está quebrando o JS, substitui por letra correspondente ou avisa
const matches = content.match(/\uFFFD/g);
if (matches) {
    console.log(`[AVISO] Ainda restam ${matches.length} caracteres corrompidos (\uFFFD) no arquivo.`);
    // O mais crítico é "voluntriosAtivos" -> "voluntariosAtivos"
    content = content.replace(/volunt\uFFFDriosAtivos/g, 'voluntariosAtivos');
}

fs.writeFileSync(filePath, content, 'utf8');
console.log(`[SUCESSO] js/app.js acentos corrigidos com sucesso!`);
