# 🛣️ ROADMAP V1 — Evolução Lausanne Admin

Este documento delineia o plano macro de evolução do sistema, partindo da auditoria inicial até a transformação em um produto "White Label" seguro, modular e escalável para atender múltiplas instituições.

---

## 🎯 Fase 1: Diagnóstico e Fundação (Sprint 0) — [CONCLUÍDA]
**Foco:** Compreender o legado e frear o crescimento da Dívida Técnica.
*   **[CONCLUÍDO]** Inventário Arquitetural (Mapeamento de pastas, HTMLs e dependências).
*   **[CONCLUÍDO]** Auditoria de Desperdícios (Identificação de duplicações, arquivos pesados e chamadas Firebase nocivas).
*   **[CONCLUÍDO]** Limpeza Estrutural (Remoção de código morto e arquivos órfãos de forma controlada).
*   **[CONCLUÍDO]** Cartografia Funcional (Documentação dos Domínios Funcionais do app atual).
*   **[CONCLUÍDO]** Oficialização de Regras (Criação do Backlog, Changelog e *Coding Standard* estrito para Engenharia).
*   **[PENDENTE]** (ENG-006) Redução Imediata de Assets Pesados (Compressão do Logotipo v70).

---

## 🛠️ Fase 2: Componentização e UI Core (Sprint 1)
**Foco:** Isolar lógicas visuais e extrair funções globais sem quebrar os formulários de negócio das páginas filhas (Iframes).
*   **[PENDENTE]** (ENG-003) Extração direta de código idêntico de Interface (`switchTab`, `toggleSidebar`).
*   **[PENDENTE]** (ENG-004) Cirurgia Funcional em Modais: Desmembrar `openModal` / `closeModal` para separar UI State de Busines Logic (rascunhos de banco de dados).
*   **[PENDENTE]** (ENG-005) Centralização dos alertas dinâmicos (`_toast` e `_confirm`), emancipando o Shell.

---

## 🏗️ Fase 3: Desacoplamento Lógico e Infraestrutura (Sprint 2)
**Foco:** Criar o coração em JavaScript do sistema, cortando os custos operacionais da infraestrutura e unindo regras de autenticação fragmentadas.
*   **[PENDENTE]** (ENG-008) Isolar Lógica de Login: Construção do módulo `auth.js` garantindo Single Source of Truth para permissões e redirecionamentos.
*   **[PENDENTE]** (ENG-007) Mudança Estrutural de Listeners (PostMessage): Interrupção imediata da "Guerra de Leituras" no Firebase, obrigando iframes a usarem mensagens de sistema da Casca (Shell) ao invés de consultas repetidas ao banco.

---

## 🧠 Fase 4: Core de Domínios e Pessoas (Sprint 3)
**Foco:** Limpeza interna de como dados trafegam.
*   **[PENDENTE]** (ENG-009) Criação do módulo `people.js` unificando a função crítica de busca (`findExistingPerson()`) e removendo todos os helpers de classificação de visitantes e membros espalhados nos arquivos `recepcao_v2.html` e `admin.html`.

---

## 🚀 Fase 5: Preparação White Label (Sprint 4)
**Foco:** Desacoplar identidades e criar variáveis estáticas para expansão.
*   **[PLANEJAMENTO]** Substituição de "Hardcodes" por arquivos de "Tenants". Exigir que chaves Firebase e strings como `"Catedral"` sejam preenchidas através de um arquivo de configuração `config.js` externo.
*   **[PLANEJAMENTO]** Isolamento e padronização do Tema Visual em CSS Root customizável por Tenant.
*   **[PLANEJAMENTO]** Mecanismo Multi-Instância: Capacidade nativa de hospedar e compartilhar esse front-end de forma White-Label em subdomínios (Ex: Infomaniak Host).
