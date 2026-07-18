# LIMA Solutions ERP — Walkthrough da Etapa 6: E-mails Automáticos (Modo Simulado)

Este documento descreve a implementação técnica, o roteiro de eventos e a homologação do sistema de e-mails em modo simulado.

---

## 1. Implementações Efetuadas

### 1.1. Classe Auxiliar Central (`EmailHelper.php`)
*   **Identidade Visual Unificada:** E-mails gerados em HTML formatado com layout moderno (marca Teal corporativa `#007a87` em destaque, fontes `Inter`, tabelas estruturadas com fundo `#f4f9fa`).
*   **Gestão de Templates:** Centralização dos 5 templates na própria classe helper (`lead_confirmation`, `internal_lead_alert`, `client_welcome`, `internal_conversion_alert`, `pipeline_status_change`).
*   **Substituição de Placeholders:** Substituição robusta de variáveis `{campo}` com fallbacks para hífen (`-`) e remoção automática de placeholders crus usando expressões regulares.
*   **Google Maps Integrado:** Geração dinâmica de links diretos do Google Maps para moradas de partida/chegada no corpo de e-mails internos comerciais (`internal_lead_alert`), sem poluir o banco de dados.

### 1.2. Integração na API Leads (`leads.php`)
*   **Criação de Leads (POST):** Envia confirmação automática ao cliente e alerta instantâneo à equipa comercial com dados completos de rastreabilidade (UTMs, referer, IP).
*   **Mudança de Estado (PUT):** Monitoriza transições de status no pipeline comercial e dispara alertas apenas quando o estado é alterado de facto.
*   **Conversão (POST action=convert):** Dispara e-mails de boas-vindas e alertas internos de conversão de dossier apenas após o commit final com sucesso da transação SQL (`$pdo->commit()`).

### 1.3. Base de Dados e Log de Auditoria
Todos os e-mails são gravados exclusivamente em:
*   Tabela `simulated_emails` no banco de dados.
*   Ficheiro físico estruturado local `/private_lima/logs/emails.log`.

---

## 2. Testes de Aceitação do Utilizador (UAT)

A suite de testes UAT automatizada em `/db/run_uat_tests.php` foi expandida para cobrir:
1.  Geração e inserção em banco de dados dos templates de criação de lead.
2.  Geração do e-mail de transição do pipeline comercial.
3.  Geração dos e-mails de conversão transacionais após commit do dossier de cliente.
4.  Associação inteligente de leads duplicados ao cliente existente.
5.  Sanitização de placeholders (garantia de ausência de brackets crus `{}` nas mensagens).
6.  Geração correta de links do Google Maps codificados.
7.  Integridade estrutural dos logs físicos em `emails.log`.

### Resultado da Execução UAT Remota (Infomaniak SSH)
```text
=== LIMA solutions ERP - Automated UAT Test Suite ===

Test 1: Criando Lead de Teste e enviando e-mails de criação... [OK] ID: 5
Test 2: Alterando Status da Lead e enviando status change... [OK]
Test 3: Convertendo Lead para Novo Cliente e enviando e-mails... [OK] Cliente criado com código: CLI-000004
Test 4: Validando Deteção de Duplicados e associação... [OK] Duplicado detetado e associado ao cliente ID 5 corretamente.
Test 5: Validando Estado dos Leads pós-conversão... [OK]
Test 6: Validando Integridade do Conteúdo dos E-mails e Ficheiro de Log...
   [OK] Nenhum placeholder cru encontrado no assunto ou corpo de nenhum e-mail simulado.
   [OK] Links Google Maps gerados e codificados corretamente no alerta interno.
   [OK] O ficheiro emails.log contém todos os blocos estruturados de teste UAT.
Limpando massa de dados de teste... [OK]

=== RESULTADO FINAL: TUDO APROVADO [PASSED] ===
```

---

## 3. Conformidade de Segurança

*   **SMTP Desativado:** Não foram configuradas credenciais ou servidores SMTP reais, impossibilitando qualquer envio acidental de mensagens a clientes durante a fase UAT.
*   **Padrão Seguro:** Toda a lógica está modularizada e preparada para a futura transição de chaves SMTP/SPF/DKIM/DMARC na Fase 3 do ecossistema.
