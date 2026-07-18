# 📐 PADRÃO OFICIAL DE DESENVOLVIMENTO (CODING STANDARD)
**Projeto:** Lausanne Admin
**Data de Criação:** 06 de Julho de 2026

Este documento define as regras rígidas e inegociáveis para qualquer alteração, refatoração ou nova funcionalidade desenvolvida no ecossistema do Lausanne Admin.

---

## 1. Estrutura Oficial de Pastas
```text
4_APP_LAUSANNE_ADMIN/
├── assets/          # Exclusivo para mídias otimizadas (WebP, SVG). Proibido duplicar logos.
├── css/             # Folhas de estilo centralizadas (sem uso de classes órfãs).
├── docs/            # Documentação arquitetural e padrões.
├── js/              # Módulos Javascript separados por Domínio (ex: auth.js, people.js).
└── archive/         # Códigos e utilitários obsoletos (NUNCA na raiz).
```

## 2. Padrão para novos arquivos JS
* **Modularidade:** Cada arquivo deve representar um único domínio de negócio (Princípio de Responsabilidade Única).
* **Ausência de HTML:** É proibido escrever blocos gigantes de HTML dentro de variáveis JS (evite "Template Strings" para construção de Modais complexos, use o DOM).
* **Comentários:** Todo módulo deve começar com um comentário em bloco explicando sua responsabilidade primária.

## 3. Padrão para novos arquivos CSS
* **Isolamento:** Uso de variáveis nativas do CSS (`var(--color-primary)`) declaradas no `:root` do arquivo principal.
* **Componentização:** Classes devem ter nomes baseados em seu bloco (BEM - Block Element Modifier recomendado de forma leve).
* **Nenhum CSS Inline:** O uso do atributo `style=""` no HTML é proibido, a menos que justificado por cálculos dinâmicos no JS (ex: largura de progress bar).

## 4. Padrão para páginas HTML
* **Escopo:** Páginas devem ser estruturais. A lógica pesada não deve residir na tag `<script>` interna.
* **Redução de Tamanho:** Modais ou elementos que não aparecem de imediato não devem sobrecarregar a raiz do HTML.

## 5. Padrão para funções
* **Tamanho:** Funções devem fazer apenas UMA coisa (Evite funções que abrem modal, salvam no banco e recarregam a página ao mesmo tempo).
* **Callbacks/Eventos:** Funções de UI não devem chamar lógica de banco de dados diretamente; elas devem disparar eventos ou receber callbacks.

## 6. Padrão para nomes de variáveis
* **CamelCase:** Obrigatório para funções e instâncias (`findExistingPerson`, `userProfile`).
* **Screaming Snake Case:** Para constantes imutáveis (`MAX_RETRIES`, `DEFAULT_ROLE`).
* **Inglês para Lógica / Português para UI:** Variáveis e funções devem preferencialmente usar a língua inglesa por padrão de engenharia. Os textos das interfaces (strings) ficam em português.

## 7. Regras para uso do Firebase (Geral)
* A inicialização do Firebase (`firebase.initializeApp`) deve ocorrer em apenas UM único local (`firebase-init.js`). Nenhuma outra página tem permissão para instanciar o app novamente.

## 8. Regras para Firestore
* **Isolamento de Listeners:** O uso de `.onSnapshot()` deve ser restrito à "Casca" do sistema (Página Mãe/Shell) ou a instâncias super controladas. Iframes não devem possuir seus próprios listeners repetidos para a mesma coleção.
* **Desacoplamento:** Usar `postMessage` para trafegar dados em tempo real da Casca para os iframes, cortando gastos desnecessários de leitura (Reads).

## 9. Regras para UI
* **Desvinculação de Lógica:** A função de manipulação de Interface Visual JAMAIS deve saber como os dados estão estruturados no banco. O fluxo é: "Ação de UI" -> "Evento" -> "Lógica de Negócio".

## 10. Regras para criação de Modais
* Modais devem utilizar as funções universais `openModal(id)` e `closeModal(id)`.
* É expressamente proibido limpar formulários ou carregar "rascunhos" por dentro do código-fonte da função `openModal()`. Isso deve ser feito reagindo a um evento de abertura.

## 11. Regras para Tabs
* O sistema de navegação por abas deve usar as classes genéricas `.tab-btn` e `.tab-content` com a função padrão `switchTab(tabId)`. Nenhuma variação local de nomeclatura de classes será aceita.

## 12. Regras para Sidebar
* Existe uma única implementação para `toggleSidebar()`. Ela atua na classe `.sidebar`. Menus não devem recriar esse código no escopo da página.

## 13. Regras para componentes compartilhados
* Componentes de alto uso (Toasts, Confirms, Spinners) residem no topo da árvore (DOM Mãe) e devem ser acionados via chamadas padronizadas (ex: `window.parent._toast()`). Nunca duplicar o HTML/CSS do toast dentro das subpáginas.

## 14. Regras para documentação
* Nenhuma nova funcionalidade pode entrar no sistema sem ter seu domínio e fluxo atualizados no arquivo `ARQUITETURA.md`. Toda modificação estrutural de refatoração será lançada no `CHANGELOG.md`.

---

## 15. Fluxo obrigatório de desenvolvimento

```text
Nova funcionalidade 
      ↓ 
Auditoria (Compreender impacto nos domínios) 
      ↓ 
Backlog (Registrar a tarefa) 
      ↓ 
Implementação (Seguindo Coding Standard) 
      ↓ 
Teste (Verificar quebra em outras áreas) 
      ↓ 
Atualização da documentação (ARQUITETURA/CHANGELOG)
```

---

## 16. Itens Proibidos (Tolerância Zero)
* ❌ **Duplicar funções** (Copiar e colar funções idênticas em vários arquivos).
* ❌ **Copiar código entre páginas** (Usar uma página antiga como base para uma nova sem abstrair os blocos comuns).
* ❌ **CSS inline sem justificativa** (`style="margin: 10px;"` no HTML).
* ❌ **Hardcodes** (Escrever IDs de Firebase, senhas ou URLs vitais chumbadas no código).
* ❌ **Lógica de negócio dentro de funções de UI** (ex: `openModal` resetando forms).
* ❌ **Criar novo HTML copiando outro** (Construa apenas o que a nova rota precisa).

---

## 17. Checklist Obrigatório Antes de Qualquer Commit
- [ ] A função atende a apenas um objetivo (Responsabilidade Única)?
- [ ] Eu procurei se essa lógica já existe em algum outro lugar do sistema antes de escrevê-la?
- [ ] Não há nenhum CSS inline novo no meu HTML?
- [ ] As variáveis obedecem ao padrão CamelCase?
- [ ] Nenhuma nova consulta paralela ao Firebase foi criada desnecessariamente?
- [ ] A documentação (`ARQUITETURA.md` / `CHANGELOG.md`) precisa ser atualizada por causa disso?
