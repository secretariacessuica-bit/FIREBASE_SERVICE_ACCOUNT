# LIMA Solutions ERP – Changelog

Todas as alterações significativas deste projeto estão documentadas neste ficheiro.  
Formato baseado em [Keep a Changelog](https://keepachangelog.com/).

---

## [RC 1.1] – 2026-06-20 (Workforce Management, Mobile PWA UX & Marketplace Monetization)

### ✨ Funcionalidades e Melhorias
- **Adicionado** Módulo de Gestão de Colaboradores (`admin/staff.php`) permitindo administração segura de equipes, redefinição de senhas, ativação/desativação e novos campos de contato.
- **Adicionado** Campos de monetização no formulário do Marketplace do Cliente ("Je souhaite receber une offre de livraison/stockage") e roteamento automático com tags "Marketplace Delivery" e "Marketplace Storage" no CRM Leads.
- **Melhorado** UI/UX do Mobile PWA (`mobile/index.html` e `app.js`):
  - Novo estado vazio profissional ("Aucun service attribué aujourd’hui") com botões offline de "Actualiser" e "Contacter l’administration".
  - Novo layout de cartões centralizado com tema dark premium otimizado para celulares.
  - Cartões de serviços atribuídos melhorados (exibindo Cliente, Endereço, Data/Hora, Status e Ações).
- **Corrigido** Bug visual do badge de cargo no Dashboard Administrativo (`admin/index.php`) para exibir dinamicamente o cargo correto do usuário a partir da sessão/banco em vez do texto fixo "STAFF".
- **Corrigido** Integração e padronização do menu lateral (Sidebar) no módulo de Workforce para reaproveitar o componente administrativo comum do ERP.

### 🗄️ Base de Dados
- **Adicionado** Migração `db/migrate_v18_marketplace_monetization.php` (campos `request_delivery` e `request_storage` na tabela `marketplace_items`, e coluna `tags` na tabela `crm_leads`).
- **Adicionado** Migração `db/migrate_v19_workforce_fields.php` (colunas `phone` e `postal_code` na tabela de usuários para gestão de staff).

---

## [RC 1.0] – 2026-06-16 (Release Candidate)

### 🔒 Segurança (Hardening de Produção)
- **Adicionado** `display_errors = Off` e `log_errors = On` em `api/v1/config.php`
- **Adicionado** deteção automática de HTTPS para `session.cookie_secure`
- **Adicionado** `session.cookie_samesite = Strict` e `session.use_strict_mode = On`
- **Adicionado** `sendSecurityHeaders()` com CSP, X-Frame-Options, HSTS, Referrer-Policy e Permissions-Policy
- **Adicionado** `admin/audit_helper.php` com `logAuditEvent()` — centraliza escrita em `activity_logs`
- **Corrigido** `admin/auth.php` — agora valida `company_id` ativo e redireciona corretamente
- **Corrigido** `api/v1/session.php` — remove `$e->getMessage()` do output; usa `error_log()` internamente
- **Atualizado** `db/.htaccess` — bloqueia acesso HTTP a todos os `.php` e `.sql` na pasta `db/`

### 🗄️ Base de Dados
- **Alinhado** `db/schema.sql` com o estado pós-migração completo (Fases 9, 9.1, 10)
- **Adicionado** colunas `approved_hourly_cost`, `approved_billable_rate`, `invoiced_at`, `locked` diretamente no schema
- **Adicionado** índices de performance `idx_ts_comp_proj_status_date`, `idx_ts_comp_user_date`, `idx_ts_billing`
- **Documentado** nota de que fresh installs não precisam executar migrações históricas

### 📚 Documentação
- **Adicionado** `docs/DEPLOY.md` — guia completo para Infomaniak / cPanel PHP 8
- **Adicionado** `docs/ARCHITECTURE.md` — estrutura, MVC, fluxo de requests, módulos
- **Adicionado** `docs/DATABASE.md` — referência das 24 tabelas, relações e índices
- **Adicionado** `docs/SECURITY.md` — sessão, CSRF, headers, isolamento, imutabilidade
- **Adicionado** `docs/BACKUP.md` — rotinas de backup (mysqldump, cron) e restauração
- **Adicionado** `docs/E2E_TEST_REPORT.md` — 29 casos de teste do fluxo completo
- **Adicionado** `CHANGELOG.md` — este ficheiro
- **Atualizado** `README.md` — requisitos mínimos, estrutura e instalação rápida

---

## [0.10.0] – 2026-06-13 (Fase 10 – Faturação Automática de Horas)

### ✨ Funcionalidades
- **Adicionado** endpoint `api/v1/timesheets/billing.php` — converte timesheets aprovados em faturas
- **Adicionado** método `convertToInvoice()` em `Timesheet.php` com transação atómica (BEGIN/COMMIT/ROLLBACK)
- **Adicionado** modos de agrupamento: `detailed`, `project`, `collaborator`, `date`, `consolidated`
- **Adicionado** validação de homogeneidade de cliente e moeda antes da geração
- **Adicionado** preenchimento de `invoice_id`, `invoiced_at`, `locked = 1` após conversão
- **Adicionado** timeline em projetos e faturas após conversão
- **Adicionado** `activity_logs` para toda operação de faturação
- **Adicionado** interface `modules/timesheets/views/billing.php`

### 🔒 Regras de Negócio Implementadas
- Faturação usa exclusivamente `approved_billable_rate` e `approved_hourly_cost`
- Apenas timesheets `status='Approved'` + `invoice_id IS NULL` são elegíveis
- Timesheets de empresas diferentes retornam `409 Conflict`

---

## [0.9.1] – 2026-06-10 (Fase 9.1 – Hardening de Timesheets)

### ✨ Funcionalidades
- **Adicionado** colunas de snapshot financeiro: `approved_hourly_cost`, `approved_billable_rate`
- **Adicionado** campos de controlo de faturação: `invoiced_at`, `locked`
- **Adicionado** índices de performance: `idx_ts_comp_proj_status_date`, `idx_ts_comp_user_date`
- **Adicionado** migração idempotente `migrate_v9_1.php`

### 🔒 Regras de Negócio
- Snapshot gravado no momento da aprovação (valores congelados para sempre)
- Bloqueio absoluto de edição/eliminação com `409 Conflict` para registos aprovados/faturados/bloqueados
- Flag `billable` para distinguir horas faturáveis de horas internas

---

## [0.9.0] – 2026-06-07 (Fase 9 – Projetos, Tarefas e Timesheets)

### ✨ Funcionalidades
- **Adicionado** módulo `projects` — CRUD completo com Kanban de tarefas
- **Adicionado** módulo `timesheets` — lançamento de horas por projeto/tarefa
- **Adicionado** workflow de aprovação de timesheets: `Draft → Submitted → Approved / Rejected`
- **Adicionado** Kanban com arrastar e largar e registo de movimentações na timeline
- **Adicionado** calendário de timesheets por colaborador
- **Adicionado** tabelas `projects`, `project_tasks`, `timesheets` no schema

---

## [0.8.0] – 2026-05-28 (Fase 8 – Pagamentos e Recibos)

### ✨ Funcionalidades
- **Adicionado** módulo `payments` — registo de pagamentos por fatura
- **Adicionado** geração de recibos em PDF
- **Adicionado** reversão controlada de pagamentos com auditoria completa
- **Adicionado** cálculo automático de `paid_amount` e `balance_due` nas faturas

---

## [0.7.0] – 2026-05-14 (Fase 7 – Orçamentos e Relatórios)

### ✨ Funcionalidades
- **Adicionado** módulo `quotes` — orçamentos com linhas, IVA e PDF
- **Adicionado** conversão de orçamento em fatura (1 clique)
- **Adicionado** módulo `reports` — relatórios operacionais e dashboards

---

## [0.6.0] – 2026-04-30 (Fase 6 – Invoices e CRM)

### ✨ Funcionalidades
- **Adicionado** módulo `invoices` — faturas completas com linhas, IVA, desconto, PDF
- **Adicionado** módulo `crm` — clientes e contactos por empresa
- **Adicionado** geração de PDF com template personalizado (logo, cor, morada)
- **Adicionado** snapshot fiscal em faturas (`fiscal_snapshot` JSON)

---

## [0.5.0] – 2026-04-10 (Fase 5 – Multi-empresa e Permissões)

### ✨ Funcionalidades
- **Adicionado** suporte multi-empresa com isolamento completo por `company_id`
- **Adicionado** sistema de roles: `super_admin`, `admin`, `staff`, `finance`, `viewer`
- **Adicionado** controlo de módulos por empresa (`company_modules`)
- **Adicionado** permissões por role e módulo (`module_permissions`)

---

## [0.1.0] – 2026-03-15 (Fase 1 – Fundação)

### ✨ Funcionalidades
- **Adicionado** schema inicial da base de dados
- **Adicionado** sistema de autenticação com PHP Sessions
- **Adicionado** painel administrativo base
- **Adicionado** sistema de sequências para geração de códigos únicos
- **Adicionado** `entity_timeline` e `activity_logs` para auditoria
