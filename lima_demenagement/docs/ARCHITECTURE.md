# LIMA Solutions ERP – Architecture Overview

> Versão: RC 1.0 | Última atualização: Junho 2026

---

## 1. Visão Geral

O **LIMA Solutions ERP** é uma aplicação web multi-empresa construída em PHP 8 + MySQL, seguindo um padrão MVC ligeiro sem framework externo. O design favorece legibilidade, segurança e facilidade de manutenção.

---

## 2. Stack Tecnológico

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.1+ (procedural + classes) |
| Base de dados | MySQL 8.0+ / MariaDB 10.6+ |
| Frontend | HTML5, CSS3 Vanilla, JavaScript ES6+ |
| Servidor | Apache 2.4+ com mod_rewrite |
| Autenticação | PHP Sessions + CSRF tokens |
| PDF | PdfTemplate.php (HTML-to-PDF personalizado) |

---

## 3. Estrutura de Diretórios

```
lima_demenagement/
├── private/                        ← FORA do public_html (segurança)
│   ├── config.php                  ← Credenciais da base de dados
│   ├── storage/                    ← Uploads, anexos
│   └── logs/                       ← Logs de aplicação
│
└── public_site/                    ← Raiz pública (mapeia para public_html/)
    ├── index.html                  ← Página de entrada pública (landing)
    ├── style.css                   ← Estilos globais
    │
    ├── admin/                      ← Painel administrativo (PHP renderizado)
    │   ├── index.php               ← Dashboard principal
    │   ├── login.php               ← Página de login
    │   ├── auth.php                ← Middleware de autenticação
    │   ├── audit_helper.php        ← Helper: escrita em activity_logs
    │   ├── timeline_helper.php     ← Helper: escrita em entity_timeline
    │   ├── sequences_helper.php    ← Helper: geração de códigos sequenciais
    │   └── modules_helper.php      ← Helper: controlo de acesso por módulo
    │
    ├── api/
    │   └── v1/                     ← API REST interna (consumida pelo frontend JS)
    │       ├── config.php          ← Configuração de sessão, BD e headers
    │       ├── session.php         ← Validação de sessão e permissões
    │       ├── login.php           ← Endpoint de autenticação
    │       ├── logout.php          ← Endpoint de logout
    │       ├── seed.php            ← Inicializador único (auto-apaga após uso)
    │       ├── crm/                ← CRUD: clientes e contactos
    │       ├── invoices/           ← CRUD: faturas e linhas
    │       ├── quotes/             ← CRUD: orçamentos
    │       ├── payments/           ← CRUD: pagamentos e recibos
    │       ├── projects/           ← CRUD: projetos e tarefas
    │       ├── timesheets/         ← CRUD + aprovação + faturação de horas
    │       └── reports/            ← Relatórios operacionais e BI
    │
    ├── modules/                    ← Módulos MVC (Model + Controller + View)
    │   ├── crm/
    │   ├── invoices/
    │   ├── quotes/
    │   ├── payments/
    │   ├── projects/
    │   ├── timesheets/
    │   └── reports/
    │
    ├── helpers/                    ← Helpers transversais
    │   ├── FinanceHelper.php       ← Formatação de moedas e cálculos
    │   └── PdfTemplate.php         ← Geração de PDFs
    │
    ├── assets/                     ← CSS, JS, imagens
    ├── facture/                    ← Templates de faturas
    └── db/                         ← Schema e migrações (bloqueados via .htaccess)
        ├── schema.sql              ← Schema completo (estado final RC 1.0)
        ├── migrate_v6.php          ← Migração incrementais históricas
        ├── migrate_v9.php
        └── migrate_v9_1.php
```

---

## 4. Padrão MVC

Cada módulo segue o padrão MVC ligeiro:

```
modules/<modulo>/
├── model/
│   └── ModelName.php      ← Queries PDO, lógica de negócio, validações
├── controller/
│   └── ControllerName.php ← Orquestração: recebe input, chama Model, retorna JSON
└── views/
    └── *.php              ← Renderização HTML do lado servidor
```

A **API** (`api/v1/<modulo>/<modulo>.php`) atua como **Router**: valida sessão, CSRF e método HTTP, e delega ao Controller.

---

## 5. Fluxo de um Request

```
Browser
  │
  ├── GET /admin/index.php
  │     → auth.php (verifica sessão + empresa ativa + headers)
  │     → index.php (render PHP server-side)
  │
  └── POST /api/v1/timesheets/timesheets.php
        → config.php (sessão, BD, headers)
        → auth.php (sessão + empresa)
        → CSRF validation
        → TimesheetController → Timesheet Model → PDO → MySQL
        → JSON response
```

---

## 6. Segurança em Camadas

| Camada | Mecanismo |
|---|---|
| Rede | HTTPS obrigatório, HSTS |
| Sessão | SameSite=Strict, HttpOnly, Secure (auto), CSRF token |
| Autenticação | PHP Session + validação de empresa ativa |
| Autorização | `enforceModuleAccess()` por módulo, role e empresa |
| Dados | PDO parametrizado (zero SQL injection) |
| Isolamento | `company_id` obrigatório em todas as queries |
| Imutabilidade | Timesheets bloqueados após aprovação/faturação |
| Auditoria | `entity_timeline` + `activity_logs` em todas as operações críticas |

---

## 7. Multi-empresa

O sistema suporta múltiplas empresas isoladas na mesma instância:

- Cada registo tem `company_id` obrigatório
- O `getActiveCompanyId()` resolve o contexto da sessão
- `super_admin` pode alternar entre empresas
- `enforceModuleAccess()` valida permissões por empresa + módulo + role

---

## 8. Sequências e Códigos

Os códigos de documentos (ex: `CLI-000001`, `INV-000042`) são gerados pela função `generateSequence()` em `sequences_helper.php`, que utiliza a tabela `company_sequences` com controlo de concorrência via `SELECT ... FOR UPDATE`.

```
Prefixo + número sequencial com zero-padding
Ex: INV- + 000042 = INV-000042
```

---

## 9. Módulos Disponíveis (RC 1.0)

| Módulo | Descrição |
|---|---|
| `dashboard` | KPIs, gráficos e estatísticas |
| `crm` | Clientes, contactos e histórico |
| `quotes` | Orçamentos com linhas e PDF |
| `invoices` | Faturas com linhas, IVA e PDF |
| `payments` | Registos de pagamento e recibos |
| `projects` | Projetos, fases e Kanban de tarefas |
| `timesheets` | Lançamento de horas, aprovação e faturação |
| `reports` | Relatórios operacionais e reconciliação |
| `settings` | Configurações de empresa, utilizadores e módulos |
