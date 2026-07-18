# 🔄 CHANGELOG TÉCNICO

Todas as mudanças técnicas notáveis no projeto serão documentadas neste arquivo, com foco principal em segurança, infraestrutura e arquitetura (não reflete lançamentos visuais ou pequenas correções de CSS).

---

### [2026-07-06] Sprint 1 - Ticket UI-001 (Consolidação da Sidebar)
* **Adicionado:** Criação do módulo global `js/ui.js` para gerenciar funções transversais.
* **Refatorado:** Função `toggleSidebar()` isolada e componentizada. Removida cópia em `admin.html` e `gabinete.html` (-6 linhas), substituída pela importação do arquivo central compartilhado.

### [2026-07-06] Sprint 0 - Fechamento da Baseline
* **Baseline de Engenharia criada.** A fundação documental do projeto foi formalmente estabelecida, sem nenhuma alteração funcional.

### [2026-07-06] Missão ENG-001 (Documentação Técnica)
* **Adicionado:** Criação do diretório central de documentação (`docs/`).
* **Adicionado:** Construção da documentação oficial da plataforma (`ARQUITETURA.md`, `BACKLOG.md` e este `CHANGELOG.md`).
* **Mapeamento:** Todos os relatórios das auditorias anteriores consolidados internamente na subpasta `docs/auditorias/`.

### [2026-07-06] Limpeza Estrutural (Arquivos Mortos)
* **Removido:** `js/firebase-config.bulle.js` (Arquivo morto sem nenhuma referência no projeto).
* **Removido:** `app.html` (Arquivo órfão defasado que executava apenas redirecionamento forçado).
* **Movido:** `ferramenta_senhas_lausanne.html` (Retirado da raiz principal do projeto e resguardado na pasta `archive/` por conter lógicas sensíveis sem fluxo de UI ativo).

### [Antes de 2026-07-06]
* **Desenvolvimento Histórico:** Todo o sistema foi mantido através de clonagem de telas baseadas em `admin.html` (Recepção, Integração, Visitante). Sem versionamento arquitetural documentado até o dia de hoje.
