# LIMA Solutions ERP – Database Reference

> Versão: RC 1.0 | Schema: `schema.sql` | Engine: InnoDB | Charset: utf8mb4_unicode_ci

---

## Visão Geral

O banco de dados `lima_solutions` contém **24 tabelas** organizadas em grupos funcionais. Todas as tabelas de dados de negócio possuem isolamento por `company_id`.

---

## Convenções Gerais

| Convenção | Descrição |
|---|---|
| `company_id` | Presente em todas as tabelas de negócio. Obrigatório em todas as queries. |
| `deleted_at` | Soft delete. Registos com `deleted_at IS NOT NULL` são tratados como eliminados. |
| `created_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` — automático. |
| `updated_at` | `TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` — automático. |
| Códigos | Gerados via `company_sequences` com `SELECT ... FOR UPDATE`. |
| Snapshots | Colunas `approved_*` congeladas no momento da aprovação — imutáveis. |

---

## Tabelas por Grupo

### Grupo 1 – Identidade e Acesso

#### 1. `companies`
Empresas registadas no sistema. Raiz de todo o isolamento de dados.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | INT PK | Identificador único |
| `name` | VARCHAR(100) | Nome comercial |
| `legal_name` | VARCHAR(150) | Nome legal/jurídico |
| `vat_number` | VARCHAR(50) | NIF / TVA |
| `iban` | VARCHAR(50) | IBAN bancário |
| `bic` | VARCHAR(20) | BIC/SWIFT |
| `currency` | VARCHAR(10) | Moeda padrão (CHF) |
| `language` | VARCHAR(5) | Idioma padrão (FR) |
| `active` | TINYINT | 1=ativa, 0=suspensa |

#### 2. `users`
Utilizadores do sistema. Um utilizador pode pertencer a múltiplas empresas.

| Coluna | Tipo | Descrição |
|---|---|---|
| `role` | VARCHAR(30) | `super_admin` \| `admin` \| `staff` \| `finance` \| `viewer` |
| `password_hash` | VARCHAR(255) | bcrypt hash |
| `active` | TINYINT | 1=ativo |

#### 3. `user_companies`
Tabela de junção M:N entre utilizadores e empresas.

#### 4. `module_permissions`
Permissões de acesso por role e módulo (`can_view`, `can_edit`).

#### 5. `company_modules`
Módulos ativados por empresa (`enabled = 1`).

---

### Grupo 2 – Configurações

#### 6. `settings`
Configurações básicas de apresentação por empresa (logo, cor, IVA padrão).

#### 7. `company_settings`
Configurações avançadas: prefixos de documentos, timezone, formato de datas/números.

#### 8. `tax_rates`
Taxas de IVA disponíveis por empresa. Suíça: 0%, 2.6%, 3.8%, 8.1%.

#### 9. `currencies`
Catálogo de moedas disponíveis (CHF, EUR, USD, GBP).

#### 10. `units`
Unidades de medida por empresa (h, pcs, kg, m², m³, day, month).

#### 11. `company_sequences`
Geração de códigos sequenciais thread-safe. Usa `SELECT ... FOR UPDATE`.

| Coluna | Descrição |
|---|---|
| `sequence_key` | Chave do contador (ex: `INV`, `CLI`, `PRJ`) |
| `current_value` | Valor atual do contador |
| `prefix` | Prefixo do código (ex: `INV-`) |
| `padding` | Zeros à esquerda (padrão: 6) |

---

### Grupo 3 – CRM

#### 12. `clients`
Clientes por empresa. Inclui código único `customer_code` (ex: `CLI-000001`).

**Campos chave**: `customer_code`, `preferred_language`, `preferred_currency`, `active` (soft-delete via flag).

---

### Grupo 4 – Faturação

#### 13. `invoices`
Faturas emitidas. Inclui snapshot fiscal completo (`fiscal_snapshot` JSON).

| Status | Descrição |
|---|---|
| `Draft` | Rascunho |
| `Issued` | Emitida |
| `Sent` | Enviada ao cliente |
| `Paid` | Paga integralmente |
| `Partially Paid` | Pagamento parcial |
| `Overdue` | Em atraso |
| `Cancelled` | Cancelada |

#### 14. `invoice_items`
Linhas de fatura (produto/serviço, quantidade, preço, IVA).

#### 15. `payments`
Pagamentos registados por fatura. Suporta reversão controlada (`reversed_at`, `reversal_reason`).

#### 16. `quotes`
Orçamentos. Podem ser convertidos em faturas.

#### 17. `quote_items`
Linhas de orçamento.

---

### Grupo 5 – Projetos e Timesheets

#### 18. `projects`
Projetos por empresa e cliente. Código `PRJ-XXXXXX`.

| Status | Descrição |
|---|---|
| `Planning` | Em planeamento |
| `Active` | Em execução |
| `On Hold` | Pausado |
| `Completed` | Concluído |
| `Cancelled` | Cancelado |

#### 19. `project_tasks`
Tarefas de projeto para o Kanban. Código `TSK-XXXXXX`.

| Status | Descrição |
|---|---|
| `Todo` | A fazer |
| `In Progress` | Em progresso |
| `Review` | Em revisão |
| `Done` | Concluído |

#### 20. `timesheets`
Lançamentos de horas. Tabela central do módulo de faturação de horas.

**Colunas críticas de imutabilidade:**

| Coluna | Descrição |
|---|---|
| `approved_hourly_cost` | Custo/hora congelado na aprovação |
| `approved_billable_rate` | Taxa faturável congelada na aprovação |
| `locked` | `1` = imutável (após faturação) |
| `invoice_id` | FK para a fatura gerada |
| `invoiced_at` | Data/hora da faturação |
| `status` | `Draft` \| `Submitted` \| `Approved` \| `Rejected` |

> ⚠️ **Regra de imutabilidade**: Qualquer tentativa de editar/eliminar um timesheet com `status='Approved'`, `locked=1` ou `invoice_id IS NOT NULL` retorna `409 Conflict`.

---

### Grupo 6 – Auditoria e Rastreabilidade

#### 21. `activity_logs`
Log de auditoria de todas as ações de utilizadores.

| Coluna | Descrição |
|---|---|
| `action` | Descrição da ação |
| `entity` | Tipo de entidade afetada |
| `entity_id` | ID da entidade |
| `before_data` | Snapshot JSON antes da mutação |
| `after_data` | Snapshot JSON após a mutação |
| `ip_address` | IP do utilizador |

#### 22. `entity_timeline`
Timeline de eventos por entidade (faturas, projetos, timesheets).

| Coluna | Descrição |
|---|---|
| `module` | Módulo (ex: `timesheets`) |
| `entity` | Tipo (ex: `timesheets`) |
| `entity_id` | ID do registo |
| `action` | Evento (ex: `approved`, `invoiced`) |

---

### Grupo 7 – Suporte

#### 23. `attachments`
Ficheiros anexados a entidades (faturas, orçamentos, projetos).

#### 24. `notifications`
Notificações internas por utilizador.

---

## Relações Principais

```
companies ──< user_companies >── users
companies ──< clients
companies ──< invoices ──< invoice_items
companies ──< quotes ──< quote_items
invoices ──< payments
companies ──< projects ──< project_tasks
projects ──< timesheets
timesheets >── invoices (invoice_id)
companies ──< entity_timeline
companies ──< activity_logs
```

---

## Índices de Performance

| Tabela | Índice | Finalidade |
|---|---|---|
| `timesheets` | `idx_ts_comp_proj_status_date` | Queries de billing e relatórios |
| `timesheets` | `idx_ts_comp_user_date` | Timesheets por colaborador |
| `timesheets` | `idx_ts_billing` | Seleção de timesheets faturáveis |
| `invoices` | `idx_invoices_company_status` | Filtragem por status |
| `invoices` | `idx_invoices_issue_date` | Relatórios por período |
| `clients` | `idx_comp_active` | Listagem de clientes ativos |
| `entity_timeline` | `idx_timeline_entity` | Timeline por entidade |
