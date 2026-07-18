# 🧩 MATRIZ DE CONSOLIDAÇÃO DA UI
**Missão 05:** Auditoria Comparativa
**Objetivo:** Análise de viabilidade para unificação de funções de interface (UI).

---

### 1. `openModal()`

| Propriedade | Análise |
| :--- | :--- |
| **Arquivos onde aparece** | `connect.html`, `visitante.html`, `followup.html` |
| **Código idêntico ou diferente?** | **Diferente** |
| **Diferenças encontradas** | Em `connect.html` ele usa a classe `.open` e possui lógica de negócio embutida (`if id == modal-ficha { goStep(1); loadDraft(); }`).<br>Em `visitante.html` ele usa a classe `.active` e também tem lógica embutida (`if id == modal-member-full...`).<br>Em `followup.html` a função exige 3 parâmetros (`id, metaJson, currentStage`) e preenche valores de um formulário pastoral específico, usando `style.display = 'flex'`. |
| **Dependências locais** | `goStep()`, `loadDraft()`, `changeWizardStep()`, campos de formulário fixos (ex: `fu-id`). |
| **Pode existir implementação única?** | **PARCIALMENTE** |
| **O que impede a unificação?** | A função foi construída misturando Interface (abrir janela) com Regras de Negócio (carregar rascunho de formulário). Para unificar, precisaremos separar a "ação de abrir" e criar "callbacks" (eventos) para a lógica específica de cada tela. Além disso, há divergência de CSS (`.active` vs `.open` vs `style.display`). |

---

### 2. `closeModal()` / `closeModals()`

| Propriedade | Análise |
| :--- | :--- |
| **Arquivos onde aparece** | `acolhimento.html`, `followup.html`, `integracao.html`, `connect.html`, `visitante.html` |
| **Código idêntico ou diferente?** | **Diferente** |
| **Diferenças encontradas** | Em `acolhimento.html` e `followup.html`, a função tem o nome `closeModal()` sem parâmetros e apenas oculta um modal específico por ID via CSS (`style.display = 'none'`).<br>Em `connect.html` e `visitante.html`, chama-se `closeModals()` e busca todos os elementos `.modal-overlay`, removendo a classe `.open` ou `.active`, além de possuir um `setTimeout` para resetar dezenas de formulários ocultos. |
| **Dependências locais** | IDs fixos de DOM (ex: `edit-modal`, `followup-modal`), arrays de formulários rígidos (`['oracao','visita',...]`). |
| **Pode existir implementação única?** | **PARCIALMENTE** |
| **O que impede a unificação?** | Exatamente o mesmo problema de `openModal`. A função de fechar janela também está encarregada de "limpar formulários". A unificação vai requerer criar uma função base universal e usar eventos `onClose` genéricos. |

---

### 3. `switchTab()`

| Propriedade | Análise |
| :--- | :--- |
| **Arquivos onde aparece** | `acolhimento.html`, `connect.html`, `followup.html`, `integracao.html` |
| **Código idêntico ou diferente?** | **Quase Idêntico (com pequenas variações de CSS)** |
| **Diferenças encontradas** | A estrutura de iteração e remoção/adição de classes é a mesma. As únicas divergências são os nomes dos parâmetros (em alguns locais é `tabId, btn`, em outros `id, el`) e as classes alvo (`.tab-btn` vs `.tab-button`). |
| **Dependências locais** | Nomenclatura das classes CSS específicas de cada arquivo. |
| **Pode existir implementação única?** | **SIM** |
| **O que impede a unificação?** | (Nada). Basta padronizarmos a classe `.tab-btn` e a classe `.active` no CSS que a função poderá ser unificada e extraída em 100%. |

---

### 4. `toggleSidebar()`

| Propriedade | Análise |
| :--- | :--- |
| **Arquivos onde aparece** | `admin.html`, `gabinete.html` |
| **Código idêntico ou diferente?** | **Idêntico** |
| **Diferenças encontradas** | Nenhuma. Ambas as funções selecionam `.sidebar` e realizam `.classList.toggle('active')`. |
| **Dependências locais** | Elemento HTML contendo a classe `.sidebar`. |
| **Pode existir implementação única?** | **SIM** |
| **O que impede a unificação?** | (Nada). Unificação direta e segura. |

---

### 5. `_toast()`

| Propriedade | Análise |
| :--- | :--- |
| **Arquivos onde aparece** | Definido em `admin.html`. (Chamado via `window.parent._toast()` pelos outros iframes). |
| **Código idêntico ou diferente?** | **Único (Sem duplicações de definição)** |
| **Diferenças encontradas** | O código já está centralizado! Ele é definido apenas no "Shell" (`admin.html` e parcialmente copiado para mobile em versões passadas ou injetado). |
| **Dependências locais** | DOM container `<div id="toast-container">` e mapa de ícones do FontAwesome. |
| **Pode existir implementação única?** | **SIM** (Já é essencialmente único na raiz, mas precisa ser extraído do HTML para um arquivo JS para limpeza). |

---

### 6. `_confirm()`

| Propriedade | Análise |
| :--- | :--- |
| **Arquivos onde aparece** | Definido em `admin.html`. (Chamado via `window.parent._confirm()` pelos outros iframes). |
| **Código idêntico ou diferente?** | **Único (Sem duplicações de definição)** |
| **Diferenças encontradas** | Assim como o `_toast`, a declaração primária mora no `admin.html`. |
| **Dependências locais** | Estrutura de HTML do Modal de Confirmação embutida dentro de uma string de Javascript, e botões dinâmicos. |
| **Pode existir implementação única?** | **SIM** (Já centralizado, só precisa ser extraído). |

---

### 🔍 Conclusão da Auditoria Comparativa

Sua estratégia provou-se correta. Tentar extrair as funções imediatamente para um `ui.js` genérico iria quebrar severamente a aplicação, porque funções básicas de UI (como `openModal`) estão contaminadas com as regras de negócio de cada página (como resetar formulários).

**Próximo Passo Ideal:** O isolamento e padronização comportamental das modais e das tabs antes da extração definitiva.
