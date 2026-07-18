# 🗑️ RELATÓRIO DE AUDITORIA: Desperdícios e Duplicações

**Missão 02:** Auditoria Passiva
**Módulo:** 4 (App Lausanne Admin)
**Status:** Concluído (Nenhuma linha de código foi alterada)

---

## 1. JavaScript

**Identificações principais:**
*   **Funções Repetidas no Mesmo Arquivo:** Identificamos "copiar e colar" acidental de blocos inteiros dentro do mesmo arquivo. Por exemplo, `renderNotifLeaders()`, `saveNotifLeaders()` estão duplicadas dentro de `admin.html`. Em `recepcao_v2.html`, as funções `submitVisit()` e `submitGroupInterest()` também possuem declarações duplicadas.
*   **Lógica de Login Clonada:** Toda a complexa lógica de login (`showLoginView`, `selectRole`, `validatePin`, `enterApp`) foi integralmente copiada e colada entre `index.html` e `mobile_v2.html`.
*   **Ajudantes (Helpers) Espalhados:** Funções úteis e pesadas como `findExistingPerson()` (para checar se alguém já existe no banco) existem repetidas em `admin.html`, `recepcao_v2.html` e `visitante.html`. Cada uma carrega sua própria lógica de consulta.
*   **Navegação e UI:** `switchTab()`, `openModal()`, `closeModal()` e `logout()` aparecem repetidas em praticamente todos os arquivos secundários (`acolhimento.html`, `integracao.html`, `followup.html`, `connect.html`).
*   **Arquivo Morto:** `firebase-config.bulle.js` está inativo e consumindo espaço na pasta.

## 2. HTML

**Identificações principais:**
*   **Páginas Órfãs/Inúteis:** O arquivo `app.html` não tem interface real. Ele contém apenas 17 linhas de um código JavaScript que força um redirecionamento imediato para `index.html`. 
*   **Scripts de Manutenção:** `ferramenta_senhas_lausanne.html` é um utilitário de uso único (provavelmente criado para migração) que ficou esquecido na raiz do projeto, expondo lógicas sensíveis e importações duplicadas.
*   **UI Copiada:** `admin.html` e `gabinete.html` possuem dezenas de painéis modais (`div`s enormes) copiados identicamente (Sidebars, Menus Superiores, Tabelas Genéricas).

## 3. CSS

**Identificações principais:**
*   **Estilos Embutidos vs Classes:** Mais de 70% das cores e marcações visuais estão escritas diretamente na linha do HTML (`style="color: red; padding: 10px;"`), anulando o propósito do `style-v2.css`.
*   **Lixo Legado:** O `style-v2.css` pesa quase 90 KB e carrega um excesso de classes antigas de versões anteriores ("v1") que já não existem no HTML atual.

## 4. Assets

**Identificações principais:**
*   **Clonagem Pesada de Logos:** O diretório hospeda os seguintes arquivos com o MESMO visual (alguns idênticos em tamanho e bytes, confirmando ser o exato mesmo arquivo renomeado):
    *   `logo.png` (2.1 MB)
    *   `logo 2.png` (2.1 MB)
    *   `logo_lausanne_v70.png` (2.1 MB)
    *   `logo_3.png` (1.3 MB)
    *   `logo 3.png` (1.3 MB)
*   **Impacto:** 9 MB de espaço e banda jogados fora por arquivos que poderiam ser apenas um único `logo.png` otimizado (em formato WebP, pesaria ~50 KB).

## 5. Banco de Dados (Firestore)

**Identificações principais:**
*   **Múltiplos Listeners Silenciosos:** Como o sistema utiliza um modelo de "Shell com Iframes" (o `index.html` abre `admin.html`, `recepcao_v2.html`, etc. dentro dele), cada janela embutida chama o Firebase e inicia seu próprio "Listener" em tempo real para a coleção `pending` e `people`. Isso significa que se um usuário deixar a aba aberta, o banco de dados está sendo consultado simultaneamente de 3 a 5 vezes a mais do que o necessário (cobrança multiplicada).

---

## 6. TABELA DE RISCO E IMPACTO

Abaixo está o resumo dos itens encontrados e a complexidade de removê-los na futura refatoração.

| Item | Arquivo(s) | Impacto | Risco de Remoção |
| :--- | :--- | :--- | :--- |
| **Logos Duplicados** | `assets/logo*.png` | Desperdício de Banda (Alto) | **Baixo** (Apenas requer atualizar o `src` no HTML) |
| **`app.html`** | Raiz | Sujeira de Navegação (Baixo) | **Baixo** (Remover o arquivo) |
| **`firebase-config.bulle.js`** | `js/` | Arquivo Morto (Baixo) | **Baixo** (Remoção segura) |
| **Lógica de Login Repetida** | `index.html`, `mobile_v2.html` | Manutenção Dupla (Médio) | **Médio** (Requer extração cuidadosa para um arquivo JS comum) |
| **Funções JS Duplicadas no mesmo HTML** | `admin.html`, `recepcao_v2.html` | Bugs de Sobrescrita (Médio) | **Médio** (Apagar a cópia inferior) |
| **Helpers Globais Repetidos** (ex: `findExistingPerson`) | `admin.html`, `visitante.html`... | Inconsistência de Regras (Alto) | **Alto** (A lógica varia levemente entre os arquivos; precisa consolidar) |
| **Listeners Múltiplos no Firestore** | `admin.html` vs Iframes | Custo Financeiro Firebase (Alto) | **Alto** (Exige refatorar a comunicação via *window.postMessage* para compartilhar os dados de um só lugar) |
| **Ferramenta de Senhas** | `ferramenta_senhas_...html` | Segurança / Sujeira (Médio) | **Baixo** (Pode ser movido para uma pasta zipada ou apagado) |
