# ⚠️ ANTES DE MEXER NESTE CÓDIGO, LEIA ESTE GUIA (A BÍBLIA DO DESENVOLVEDOR)

Este documento contém as regras fundamentais de arquitetura, segurança e integridade do **LIMA Solutions ERP**. Qualquer alteração ao código deve respeitar escrupulosamente estas diretrizes para não quebrar o sistema ou expor dados sensíveis.

---

## 1. Regra de Ouro: Isolamento Total
O LIMA Solutions ERP está hospedado no mesmo servidor Infomaniak que outros projetos, mas deve permanecer **100% isolado**.
* **Ficheiros Públicos**: Estão todos em `/sites/limasolutions.ch/`.
* **Ficheiros Privados (Credenciais)**: Estão em `/sites/private_lima/`. **NUNCA** coloque esta pasta dentro da raiz pública.
* **Base de Dados**: Usa a base de dados dedicada `6o9v7p_erp` (com o utilizador `6o9v7p_lima_user`). Não partilhe tabelas ou ligações com nenhum outro site.

---

## 2. Estrutura de Diretórios em Produção
```text
/home/clients/c60c25a0672639c5f81740b42f06902c/sites/
├── private_lima/               ← FORA do acesso público via browser
│   ├── config.php              ← Dados de ligação à base de dados (MySQL)
│   └── storage/                ← Armazenamento de faturas (PDF) e recibos
│
└── limasolutions.ch/           ← Pasta raiz pública (onde este ficheiro está)
    ├── admin/                  ← Painel administrativo (login, auditorias, etc.)
    ├── api/v1/                 ← Endpoints de API públicos e privados (inclui config.php com os Headers e CSP)
    ├── db/                     ← Scripts de migração e o schema.sql
    ├── facture/                ← Visualizador público de faturas para clientes
    ├── helpers/                ← Funções auxiliares (PDF, finanças)
    └── modules/                ← Módulos do sistema (CRM, faturas, projetos, etc.)
```

---

## 3. Navegação e Caminhos Absolutos (UI/Views)
* **Nunca use links relativos** (ex: `../../admin/index.php`) dentro dos módulos (`/modules/*/views/`). Dependendo do nível de aninhamento, eles vão falhar e dar erro 404.
* **Sempre use caminhos absolutos** na raiz do site para recursos estáticos e links de navegação:
  * CSS: `/admin/css/admin.css`
  * Links: `/admin/index.php` ou `/facture/index.html`

---

## 4. Segurança e Endurecimento (Hardening)
* **Controle de Erros**: Em produção, `display_errors` está desativado (`Off`). Qualquer erro de PHP é registado no servidor e nunca mostrado ao utilizador.
* **Segurança de Sessões**: As sessões usam `HttpOnly`, `SameSite=Strict` e `cookie_secure` automático (ativado apenas quando acedido via HTTPS).
* **CSP (Content-Security-Policy)**: É estrito e definido em `api/v1/config.php`. Permite apenas `'self'`, `fonts.googleapis.com`, e CDNs específicas (`cdn.jsdelivr.net`, `cdnjs.cloudflare.com`). Se carregar novos scripts de terceiros, eles **serão bloqueados** a menos que a whitelist do CSP seja atualizada.
* **Ficheiro de Configuração**: O ficheiro público `/api/v1/config.php` apenas serve para ler o caminho seguro `/sites/private_lima/config.php`. Toda a lógica deve depender deste último.

---

## 5. Governança Fiscal e Faturação (Crítico)
Ao alterar os módulos de `timesheets` (folhas de horas) ou `invoices` (faturas), respeite as seguintes regras:
1. **Evitar Dupla Faturação (Concorrência)**: A conversão usa a instrução `SELECT ... FOR UPDATE` para bloquear as linhas na base de dados durante a transação ativa.
2. **Snapshots Financeiros**: O valor faturado baseia-se nos snapshots congelados no momento da aprovação do timesheet.
3. **Regra de Cancelamento**: O cancelamento de uma fatura **nunca** apaga metadados para garantir integridade fiscal.
4. **ID de Lote (`billing_batch_id`)**: Gera um UUID v4 único gravado na fatura e nos timesheets.

---

## 6. Desenvolvimento da API e PDO (Prevenção de Erros 500)
* **Parâmetros PDO (`array_intersect_key`)**: Ao executar statements (`$stmt->execute(...)`) com arrays dinâmicos de parâmetros, garanta que **apenas as chaves que existem exatamente na string SQL** são passadas. O driver PDO atira um erro `HY093` (Invalid parameter number) e causa um `500 Internal Server Error` se você passar placeholders a mais (como passar `:start_date_inv` para um SQL que só tem `:start_date`).
* **Relatórios (Reports API)**: Utilize as colunas físicas calculadas na base de dados para garantir performance (ex: `paid_amount`, `balance_due`, `tax_amount` em `invoices`). Não utilize aliases fictícios que a BD não reconhece.

---

## 7. Migrações e Base de Dados
* As migrações da base de dados **não podem ser executadas via HTTP** (bloqueadas via `.htaccess`).
* Devem ser sempre executadas por SSH ou via scripts backend.
* **Atenção às Estruturas Recentes**: Na atualização mais recente, foram adicionadas colunas calculadas/rastreamento fundamentais:
  * `invoices`: `paid_amount`, `balance_due`, `cancelled_at`, `sent_at`
  * `projects`: `hourly_rate`
  * `timesheets`: `task_id`, `locked`
  * `clients`: `active`
  * `marketplace_items`: `request_delivery`, `request_storage` (monetização de fretes/depósitos)
  * `crm_leads`: `tags` (origem e tags estruturadas como Marketplace)
  * `users`: `phone`, `postal_code` (contatos para gestão de Workforce/Staff)
  * Tabela `audit_log` para tracking de ações.
* O schema local em `/db/schema.sql` deve ser sempre mantido em sincronia com o estado do servidor.

---

## 8. Contactos de Emergência do Projeto
* **Dono do Alojamento**: LIMA Solutions
* **Domínio Oficial**: `https://limasolutions.ch`
* **Criado em**: Junho de 2026

*Se for um programador ou assistente de IA que acabou de chegar a este projeto: não mude a lógica estrutural sem ler o `docs/ARCHITECTURE.md` (se existir) e validar o fluxo principal de faturamento.*
