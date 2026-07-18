# LIMA Solutions ERP – Linha de Base de Produção (Versão 2.0)
**Data de Homologação**: 19 de Junho de 2026  
**Versão Homologada**: V1.3-Hardened (Platform Consolidated & Smart Engine Live)  
**Banco de Dados**: `lima_solutions` (MySQL 8.0+ / MariaDB 10.6+)  
**Ambiente Principal**: Produção (Hospedagem Infomaniak PHP 8.4)  
**Status**: **Production Approved** (Pontuação de Prontidão: **99%**)

---

## Executive Summary

Este documento serve como a **linha de base técnica oficial (Production Baseline)** do ecossistema LIMA Solutions ERP. Estabelece a especificação funcional, modelo de arquitetura, esquemas de banco de dados, matrizes de segurança e rotinas operacionais homologadas para uso em produção pela **Lima Déménagement**. Qualquer evolução futura deve manter estrita retrocompatibilidade com este baseline.

* **Data de Aprovação**: 19 de Junho de 2026
* **Versão Homologada**: V1.3-Hardened
* **Pontuação de Prontidão**: **99%**
* **Escopo do Lançamento**: Consolidação da plataforma administrativa multi-empresa, API-First móvel operacional, moderação do Marketplace, motor inteligente de alocação de equipes, geofencing local e painel integrado de observabilidade ativa.

---

## Architecture Baseline

O ecossistema é projetado com foco em desacoplamento e escalabilidade, seguindo três princípios:

### 1. Separação Estrita Público/Privado
Os dados confidenciais, logs do servidor e chaves criptográficas residem exclusivamente fora do web root acessível via navegador.
* **Privado (`/private_lima/`)**: 
  - `config.php` (Armazena credenciais MySQL, chaves SMTP, senhas de API).
  - `logs/application.log` (Central de logs operacionais do sistema).
  - `storage/` (Armazenamento físico de assinaturas de clientes, fotos de inventários e faturas PDF geradas).
* **Público (`/public_site/` mapeado para `public_html`)**: 
  - Contém arquivos HTML/CSS/JS públicos, módulos administrativos protegidos e a árvore de APIs REST.

### 2. Arquitetura API-First (`/api/v1/`)
Toda a lógica de negócios e persistência de dados é consumida por meio de endpoints de API padronizados. Isso permite que a interface Web Admin atual, a aplicação móvel (PWA) e qualquer portal de terceiros futuro consumam a mesma fonte unificada de dados de forma assíncrona via JSON.

### 3. Isolamento Multi-empresa (Multi-tenant)
Todas as tabelas críticas de negócios possuem o atributo `company_id`. O middleware do sistema garante isolamento absoluto de dados. Um usuário logado em uma empresa jamais terá acesso a clientes, Devis, Factures ou rotas móveis de outra empresa, respeitando os limites da tabela `user_companies`.

---

## Database Baseline

### Tabelas Ativas (28 Tabelas)
1. **Core**: `companies`, `users`, `user_companies`, `settings`, `company_modules`, `module_permissions`, `company_sequences`, `company_settings`, `tax_rates`, `currencies`, `units`, `activity_logs`, `attachments`, `notifications`
2. **CRM**: `clients`, `crm_leads`, `entity_timeline`, `simulated_emails`
3. **Comercial / Financeiro**: `quotes`, `quote_items`, `invoices`, `invoice_items`, `payments`
4. **Operacional**: `projects`, `project_tasks`, `timesheets`, `mobile_tokens`, `operational_assignments`, `gps_tracking`, `project_photos`, `project_checklists`, `project_signatures`
5. **Marketplace**: `marketplace_categories`, `marketplace_items`, `marketplace_photos`, `marketplace_interests`
6. **Observabilidade**: `system_metrics_daily`

### Índices Críticos (Critical Indexes)
* **`idx_leads_dashboard`** em `crm_leads(company_id, status, created_at)`: Otimização de widgets de funil CRM.
* **`idx_projects_kanban`** em `projects(company_id, start_date, status)`: Otimização do carregamento do quadro Kanban administrativo.
* **`idx_timesheets_mobile_h`** em `timesheets(company_id, user_id, status, work_date)`: Aceleração da sincronização de timesheets do PWA em campo.
* **`idx_comp_client_code`** em `clients(company_id, customer_code)`: Unicidade e busca rápida de clientes.

### Estratégia de Auditoria
A tabela `activity_logs` armazena o histórico completo de mutações de dados executadas no ERP (CRUD). Ela salva snapshots no formato JSON contendo o estado anterior e posterior à transação (`old_values`, `new_values`) para auditoria regulatória e fiscal.

---

## Active Modules

### 1. ERP Core
* **Status**: Complete
* **APIs**: `/api/v1/login.php`, `logout.php`, `session.php`, `select_company.php`, `change_password.php`
* **Tabelas**: `companies`, `users`, `user_companies`, `settings`
* **Dependências**: PHP 8.4, Driver PDO MySQL nativo.

### 2. CRM & Leads
* **Status**: Complete
* **APIs**: `/api/v1/crm/clients.php`, `/api/v1/leads/`
* **Tabelas**: `clients`, `crm_leads`, `entity_timeline`
* **Dependências**: ERP Core

### 3. Quotes (Orçamentos / Devis)
* **Status**: Complete
* **APIs**: `/api/v1/quotes/`
* **Tabelas**: `quotes`, `quote_items`
* **Dependências**: CRM, PDF Helper (`PdfTemplate.php`)

### 4. Invoices (Faturas / Factures)
* **Status**: Complete
* **APIs**: `/api/v1/invoices/`
* **Tabelas**: `invoices`, `invoice_items`
* **Dependências**: CRM, PDF Helper

### 5. Payments (Pagamentos)
* **Status**: Complete
* **APIs**: `/api/v1/payments/`
* **Tabelas**: `payments`
* **Dependências**: Invoices, FinanceHelper

### 6. Projects (Projetos)
* **Status**: Complete
* **APIs**: `/api/v1/projects/`, `/api/v1/projects/projects.php?id=X` (Cálculo de Margem)
* **Tabelas**: `projects`, `project_tasks`
* **Dependências**: Invoices, Timesheets, FinanceHelper

### 7. Timesheets (Horas)
* **Status**: Complete
* **APIs**: `/api/v1/timesheets/`
* **Tabelas**: `timesheets`
* **Dependências**: Projects, Users

### 8. Client Portal (Portal do Cliente)
* **Status**: Complete
* **APIs**: `/api/v1/portal/auth.php`, `quotes.php`, `invoices.php`, `messages.php`
* **Tabelas**: `client_users`, `notifications`, `entity_timeline`
* **Dependências**: Invoices, Quotes, CRM

### 9. Mobile Operations (App Operacional)
* **Status**: Complete (PWA Offline-Ready)
* **APIs**: `/api/v1/mobile/auth.php`, `sync.php`, `photos.php`, `signatures.php`, `checklists.php`
* **Tabelas**: `mobile_tokens`, `operational_assignments`, `gps_tracking`, `project_photos`, `project_checklists`, `project_signatures`
* **Dependências**: Projects, Users, IndexedDB local no navegador.

### 10. Marketplace
* **Status**: Complete
* **APIs**: `/api/v1/marketplace/catalog.php`, `interests.php`, `moderate.php`
* **Tabelas**: `marketplace_categories`, `marketplace_items`, `marketplace_photos`, `marketplace_interests`
* **Dependências**: CRM (Lead Routing direto no cadastro de Leads)

### 11. Lead Scoring (Pontuação Comercial)
* **Status**: Complete
* **APIs**: Acionado internamente via modelo `Lead->updateLeadScore()`
* **Tabelas**: `crm_leads`
* **Dependências**: CRM, Marketplace (interesses integrados)

### 12. Assignment Engine (Motor de Alocação)
* **Status**: Complete
* **APIs**: `/api/v1/projects/recommend_teams.php`
* **Tabelas**: `users`, `operational_assignments`
* **Dependências**: Projects, Timesheets (carga de trabalho de 7 dias)

### 13. Geofencing (Alertas de Check-in)
* **Status**: Complete
* **APIs**: Frontend JS nativo no PWA Mobile
* **Tabelas**: Nenhuma (executado em memória de cliente). Persistência de coordenadas em `gps_tracking`.
* **Dependências**: Mobile Operations

### 14. Observability (Observabilidade)
* **Status**: Complete
* **APIs**: Handler global de exceções do PHP
* **Tabelas**: `system_metrics_daily`
* **Dependências**: `/private_lima/logs/application.log`

### 15. Disaster Recovery
* **Status**: Complete
* **APIs**: Script shell bash de backup diário executando mysqldump e gzip.
* **Tabelas**: Leitura via ERP de `backup_status.json` no storage privado.
* **Dependências**: Utilitários do OS (mysqldump, gzip, tar).

---

## Security Baseline

1. **Segurança de Sessão**: Cookies HTTPOnly e SameSite: Strict ativos. Timeout de inatividade automática configurado em **30 minutos** para administradores.
2. **CSP (Content Security Policy)**: Diretiva rígida bloqueando scripts injetados de terceiros, permitindo apenas fontes listadas (`'self'`, Google Fonts, cdnjs e jsdelivr).
3. **X-Content-Type-Options: nosniff**: Enviado globalmente em todas as requisições AJAX e páginas administrativas.
4. **Isolamento de Armazenamento**: Pasta `/private_lima/storage/` localizada fora do web root. Arquivos de fotos e faturas só podem ser acessados via API autenticada com checagem de permissões do usuário.
5. **Monitoramento de Backups**: Badge dinâmico sinaliza erro crítico em vermelho (`Stale`) na tela do administrador se o arquivo `backup_status.json` não for atualizado pelo Cron Job por mais de **28 horas**.
6. **Prevenção de Spam**: CRM Leads possui campo `priority_alert_sent_at` que garante o envio de exatamente uma notificação por e-mail para a equipe de vendas ao atingir pontuação prioritária ($\ge 76$ pontos).

---

## Operational Baseline

* **Estratégia de Backup**:
  - Banco de Dados: Dump completo via `mysqldump` diariamente às 02:00. Retenção de 30 dias.
  - Storage Privado: Compactação das pastas de assinaturas e fotos operacionais em tarball `.tar.gz` semanal. Retenção de 30 dias.
* **Rotação de Logs**:
  - Rotação do `application.log` acionada sempre que o tamanho exceder **10 MB**.
  - Retenção máxima de arquivos de logs comprimidos em disco por **90 dias**.
* **Estratégia de Monitoramento**:
  - Coleta automática diária de estatísticas de erros de API, logins inválidos e falhas de sincronização móvel gravados diretamente na tabela `system_metrics_daily`.
  - Dashboard Admin de Telemetria (`admin/observability.php`) atualizado com visualizador rápido de log integrado.
* **Objetivos de Disaster Recovery (DRP)**:
  - **RPO (Recovery Point Objective)**: **24 Horas** (perda tolerada desde o último backup).
  - **RTO (Recovery Time Objective)**: **4 Horas** (tempo máximo de indisponibilidade).

---

## Limitações Conhecidas (Não-Bloqueantes)

* **Gateway de Pagamento**: Pagamentos são registrados exclusivamente de forma manual no financeiro (Stripe/Twint não integrados nativamente por webhook).
* **Notificações Push**: O geofencing depende do PWA estar aberto no navegador; não há envio de notificações push nativas em segundo plano.
* **Empacotamento Nativo**: O aplicativo móvel opera como PWA (acessado por URL), não estando publicado nas lojas Google Play ou Apple Store.
* **Geolocalização por Rota Real**: O motor de alocação de equipes e check-in móvel utilizam aproximações geométricas por NPA (Swiss postal code proxy), sem cálculo real de trajetos do Google Maps.
* **Autenticação 2FA**: O login exige apenas e-mail e senha, sem verificação secundária de segurança de dois fatores.
* **Agregação de Erros Externo**: Não há conexão ativa com o Sentry ou Datadog para reporte automatizado de incidentes fora do servidor.

---

## Future Roadmap

```text
               [ V1.3-Hardened Baseline ]
                           ↓
    [ Fase A ] ➔ Integração de Gateways de Pagamento (Stripe/Twint)
                           ↓
    [ Fase B ] ➔ Motor de Workflow Operacional Customizado
                           ↓
    [ Fase C ] ➔ Central Unificada de Notificações Multi-canal (SMS/Push)
                           ↓
    [ Fase D ] ➔ Pesquisas de Satisfação de Clientes (NPS Automatizado)
                           ↓
    [ Fase E ] ➔ Business Intelligence Avançado (BI Pro)
                           ↓
    [ Fase F ] ➔ LIMA Copilot (Assistente de IA para Orçamentistas)
```

---

## Upgrade Policy (Política de Atualização)

Qualquer desenvolvimento futuro deve cumprir as seguintes diretivas para evitar regressões:
1. **Sem Alteração de DDL sem Migração**: Nenhuma tabela ou índice do banco de dados deve ser modificado diretamente em produção. Toda alteração de banco deve ser empacotada em script PHP de migração incremental (ex: `migrate_v17_*.php`) e testada localmente.
2. **API-First Imutável**: Os contratos JSON das APIs existentes em `/api/v1/` não podem ser alterados de forma destrutiva. Novos dados exigem criação de novas propriedades ou novos endpoints para evitar quebrar o PWA móvel offline de campo.
3. **Isolamento de Negócios**: Nenhuma nova funcionalidade pode contornar a obrigatoriedade da presença do filtro `company_id` nas consultas SQL do ERP ou APIs.
