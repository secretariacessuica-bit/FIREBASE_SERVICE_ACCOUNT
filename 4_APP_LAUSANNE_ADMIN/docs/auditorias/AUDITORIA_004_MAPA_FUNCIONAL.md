# 🗺️ AUDITORIA 004: Mapa Funcional e Cartografia do Sistema

## TABELA 1: DOMÍNIOS FUNCIONAIS

| Domínio | Responsabilidade | Arquivos envolvidos | Nível de acoplamento |
| :--- | :--- | :--- | :--- |
| **Autenticação e Sessão** | Controle de login, verificação de permissões e roteamento de telas. | `auth-manager.js`, `index.html`, `mobile_v2.html` | **Alto** |
| **Pessoas e Cadastros** | Criação, busca, atualização e classificação de usuários. | `admin.html`, `recepcao_v2.html`, `visitante.html` | **Altíssimo** |
| **Gestão Infantil (Kids)** | Geração de credenciais, check-in, regras de segurança. | `kids.html`, `reception_kids_logic.js`, `admin.html` | **Alto** |
| **CRM e Integração** | Acompanhamento de jornada, VIPs, envio de conexões. | `integracao.html`, `connect.html`, `admin.html` | **Médio** |
| **Gabinete Pastoral** | Aconselhamento, registros sigilosos. | `gabinete.html`, `followup.html` | **Médio** |
| **Eventos e Check-in** | Presenças pontuais, abertura/fechamento. | `checkin.html` | **Baixo** |
| **Comunicação de Altar** | Sistema de painel para avisos no culto. | `altar_final.html` | **Baixo** |
| **Interface (UI) e Modais** | Renderização de janelas, sidebars, abas e alertas. | Quase todos | **Altíssimo** |

## TABELA 2: CANDIDATOS A MÓDULOS FUTUROS
(Baseado na arquitetura lógica)
*   `config.js`
*   `firebase-init.js`
*   `auth.js`
*   `ui.js`
*   `people.js`
*   `kids.js`
*   `crm.js`
*   `pastoral.js`
*   `events.js`
