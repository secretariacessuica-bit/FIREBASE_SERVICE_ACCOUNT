# 📋 BACKLOG DE ENGENHARIA

Este documento registra todas as tarefas técnicas pendentes mapeadas pelas auditorias, guiando a refatoração.

---

### [ENG-001] Limpeza de Arquivos Mortos
**Descrição:** Remoção do `firebase-config.bulle.js` e `app.html`. Movimentação de utilitários isolados para `archive/`.
**Prioridade:** Alta
**Status:** ✅ Concluído
**Sprint:** Sprint 0 (Fundação)
**Responsável:** Equipe de Engenharia
**Data:** 2026-07-06
**Versão:** v70.4.0
**Dependências:** Nenhuma
**Critério de aceite:** Arquivos devem ser removidos e o sistema deve continuar rodando sem erros 404 no console.
**Critério de rollback:** Restaurar arquivos do repositório `archive` ou do backup histórico se o sistema quebrar.
**Documento relacionado:** `AUDITORIA_003_LIMPEZA.md`, `CHANGELOG.md`
**Risco:** Baixo

---

### [ENG-002] Consolidação Documental
**Descrição:** Construção da pasta `docs/`, elaboração da arquitetura oficial, backlog e changelog técnico. Centralização de relatórios.
**Prioridade:** Alta
**Status:** ✅ Concluído
**Sprint:** Sprint 0 (Fundação)
**Responsável:** Equipe de Engenharia
**Data:** 2026-07-06
**Versão:** v70.4.0
**Dependências:** Nenhuma
**Critério de aceite:** Árvore de documentos `docs/` populada e contendo regras oficiais estritas de refatoração.
**Critério de rollback:** Excluir diretório `docs/`.
**Documento relacionado:** `ARQUITETURA.md`, `CODING_STANDARD.md`
**Risco:** Baixo

---

### [ENG-003] Extração UI Transversal: `toggleSidebar` e `switchTab`
**Descrição:** Extrair a implementação idêntica encontrada na matriz de consolidação de UI para estas duas funções e componentizá-las em um novo arquivo global (`ui.js` ou similar), removendo as repetições de `admin.html`, `gabinete.html`, `acolhimento.html`, etc.
**Prioridade:** Média
**Status:** ⏳ Parcial (toggleSidebar Concluído, switchTab pendente)
**Sprint:** Sprint 1 (Refatoração Base)
**Responsável:** Equipe de Engenharia
**Data:** 2026-07-06
**Versão:** v70.4.x
**Dependências:** Criação do arquivo `js/ui.js`
**Critério de aceite:** Navegação de abas e abertura de menus laterais devem continuar funcionando perfeitamente sem o código duplicado nas páginas.
**Critério de rollback:** Restaurar a tag `<script>` dos arquivos HTML alterados.
**Documento relacionado:** `AUDITORIA_005_CONSOLIDACAO_UI.md`
**Risco:** Baixo

---

### [ENG-004] Separação de Lógica de Negócio em `openModal` e `closeModal`
**Descrição:** Extrair a parte visual de abrir e fechar a modal de tela para uma função universal. Refatorar as chamadas locais para realizarem a lógica de negócio (como limpar formulário ou carregar rascunho) usando callbacks e eventos amarrados ao DOM de cada página.
**Prioridade:** Alta
**Status:** ⏳ Pendente
**Sprint:** Sprint 1 (Refatoração Base)
**Responsável:** Equipe de Engenharia
**Data:** A definir
**Versão:** v70.4.x
**Dependências:** ENG-003
**Critério de aceite:** Abertura e fechamento de modais com reset correto de dados isolados por evento/callback.
**Critério de rollback:** Reversão do HTML para as chamadas explícitas anteriores.
**Documento relacionado:** `AUDITORIA_005_CONSOLIDACAO_UI.md`
**Risco:** Alto (risco de quebrar formulários)

---

### [ENG-005] Centralização de `_toast` e `_confirm`
**Descrição:** Remover essas funções de dentro de `admin.html` e levá-las para a raiz do JS para que os iframes não precisem depender da chamada de `window.parent`, facilitando testes isolados e padronizando alertas visuais.
**Prioridade:** Média
**Status:** ⏳ Pendente
**Sprint:** Sprint 1 (Refatoração Base)
**Responsável:** Equipe de Engenharia
**Data:** A definir
**Versão:** v70.4.x
**Dependências:** ENG-003, ENG-004
**Critério de aceite:** Iframes não quebram ao invocar `_toast()` e o layout original é preservado.
**Critério de rollback:** Retornar bloco de função visual para o `admin.html`.
**Documento relacionado:** `AUDITORIA_005_CONSOLIDACAO_UI.md`
**Risco:** Médio

---

### [ENG-006] Otimização e Unificação de Assets (Logos)
**Descrição:** Substituir os 5 arquivos `logo*.png` idênticos e pesados (2.1 MB) por apenas um (`logo_main.webp` ou `.png` otimizado), pesando menos de 100 KB, e atualizar os caminhos no HTML.
**Prioridade:** Alta
**Status:** ⏳ Pendente
**Sprint:** Sprint 0 (Fundação)
**Responsável:** Equipe de Engenharia
**Data:** A definir
**Versão:** v70.4.x
**Dependências:** Nenhuma
**Critério de aceite:** Apenas 1 logo no diretório `/assets` com peso mínimo, refletindo perfeitamente em toda aplicação.
**Critério de rollback:** Restaurar arquivos do backup de Assets.
**Documento relacionado:** `AUDITORIA_002_DESPERDICIOS.md`
**Risco:** Baixo

---

### [ENG-007] Refatoração de Múltiplos Listeners (Firestore)
**Descrição:** O modelo de `iframes` causa múltiplas conexões simulâneas à coleção `pending`. Implementar um modelo onde o parent (Shell) escuta o banco de dados e repassa os estados para os iframes via `postMessage()`, reduzindo os reads do Firebase drasticamente.
**Prioridade:** Alta
**Status:** ⏳ Pendente
**Sprint:** Sprint 2 (Desacoplamento de Infra)
**Responsável:** Equipe de Engenharia
**Data:** A definir
**Versão:** v70.5.x
**Dependências:** Arquitetura estabilizada de UI
**Critério de aceite:** Aba de métricas do Firebase deve constar uma drástica redução de Reads simultâneas após deploy.
**Critério de rollback:** Reversão dos arquivos para usar seus próprios `db.collection('pending').onSnapshot(...)`.
**Documento relacionado:** `AUDITORIA_002_DESPERDICIOS.md`
**Risco:** Altíssimo

---

### [ENG-008] Extração Lógica de Login
**Descrição:** Isolar a cópia integral existente em `index.html` e `mobile_v2.html` das funções `validatePin`, `showRoles`, etc., e movê-las para `auth.js`.
**Prioridade:** Alta
**Status:** ⏳ Pendente
**Sprint:** Sprint 2 (Desacoplamento de Infra)
**Responsável:** Equipe de Engenharia
**Data:** A definir
**Versão:** v70.5.x
**Dependências:** Nenhuma
**Critério de aceite:** Um único arquivo de sessão que garante bloqueio transversal sem variação visual nas páginas index/mobile.
**Critério de rollback:** Desfazer injeção do script global.
**Documento relacionado:** `AUDITORIA_004_MAPA_FUNCIONAL.md`
**Risco:** Médio

---

### [ENG-009] Unificação do `findExistingPerson()`
**Descrição:** Criar o módulo conceitual `People Core` padronizando a busca global em um único arquivo, eliminando os Helpers repetidos em admin, recepcao_v2 e visitante.
**Prioridade:** Crítica
**Status:** ⏳ Pendente
**Sprint:** Sprint 3 (Lógica Core & White Label Prep)
**Responsável:** Equipe de Engenharia
**Data:** A definir
**Versão:** v70.6.x
**Dependências:** Finalização da Refatoração de UI
**Critério de aceite:** Retorno absoluto na exatidão de dados de perfil sem queries conflitantes de Firestore entre modais de visitantes e admins.
**Critério de rollback:** Restauração dos helpers antigos por arquivo.
**Documento relacionado:** `ARQUITETURA.md`, `AUDITORIA_004_MAPA_FUNCIONAL.md`
**Risco:** Alto
