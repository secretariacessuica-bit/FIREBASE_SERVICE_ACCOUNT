# LIMA Solutions ERP – End-to-End Test Report

> Versão: RC 1.0 | Data: Junho 2026  
> Ambiente: Local (PHP 8.1, MySQL 8.0)

---

## Fluxo Testado

```
CRM (Cliente) → Orçamento → Fatura → Pagamento → Recibo
             → Projeto → Tarefa (Kanban) → Timesheet
             → Aprovação → Faturação Automática → Relatórios
```

---

## Pré-Requisitos do Teste

- [ ] Base de dados inicializada com `schema.sql`
- [ ] Seeder `seed.php` executado
- [ ] Login com `admin@limasolutions.ch` / `lima2026`
- [ ] Módulos `crm`, `invoices`, `quotes`, `payments`, `projects`, `timesheets`, `reports` ativos
- [ ] Empresa LIMA Solutions configurada com taxa IVA 8.1%

---

## Bloco 1 – CRM: Gestão de Clientes

### TC-01: Criar Cliente

**Ação**: Admin → CRM → Novo Cliente  
**Dados**: Nome: "Transport Dumont SA", email: test@dumont.ch, moeda: CHF

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Código gerado | CLI-000001 (sequencial) | ✓ |
| Registo salvo | Aparece na lista de clientes | ✓ |
| Isolamento | Não visível em outra empresa | ✓ |
| Timeline | Evento `created` na timeline do cliente | ✓ |
| Auditoria | Registo em `activity_logs` | ✓ |

### TC-02: Editar Cliente

**Ação**: Editar telefone e morada  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Dados atualizados | Novos valores salvos | ✓ |
| Timeline | Evento `updated` registado | ✓ |

### TC-03: Soft Delete de Cliente

**Ação**: Eliminar cliente sem transações  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| `deleted_at` preenchido | Registo não visível na lista | ✓ |
| Não permite deletar com faturas | Retorna `409 Conflict` | ✓ |

---

## Bloco 2 – Orçamentos

### TC-04: Criar Orçamento

**Ação**: Admin → Orçamentos → Novo  
**Dados**: Cliente Dumont, 3 linhas de serviço, IVA 8.1%, desconto 5%

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Código gerado | Q-000001 | ✓ |
| Cálculos financeiros | Subtotal, IVA e Total corretos | ✓ |
| Status inicial | `Draft` | ✓ |

### TC-05: Converter Orçamento em Fatura

**Ação**: Orçamento → Converter para Fatura  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Fatura criada | INV-000001 gerado | ✓ |
| Linhas copiadas | Itens idênticos ao orçamento | ✓ |
| `quote_id` preenchido na fatura | FK correta | ✓ |
| Orçamento marcado como `Accepted` | Status atualizado | ✓ |
| Timeline | Evento em ambas as entidades | ✓ |

---

## Bloco 3 – Faturação

### TC-06: Editar e Emitir Fatura

**Ação**: Ajustar data de vencimento → Emitir  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Status → `Issued` | Transição de status | ✓ |
| PDF gerado | Fatura renderizada correctamente | ✓ |
| `fiscal_snapshot` gravado | JSON com valores congelados | ✓ |

### TC-07: Bloquear Edição de Fatura Paga

**Ação**: Tentar editar linhas de fatura já paga  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Retorna erro | `409 Conflict` | ✓ |
| Dados inalterados | Nenhuma mutação na BD | ✓ |

---

## Bloco 4 – Pagamentos

### TC-08: Registar Pagamento

**Ação**: Admin → Pagamentos → Novo  
**Dados**: Fatura INV-000001, método: Virement bancaire, valor total

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Código gerado | PAY-000001 | ✓ |
| Fatura → `Paid` | Status atualizado | ✓ |
| `paid_amount` e `balance_due` atualizados | Corretos | ✓ |
| Timeline | Evento `payment_received` | ✓ |

### TC-09: Pagamento Parcial

**Ação**: Pagar 50% do valor da fatura  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Fatura → `Partially Paid` | Status correto | ✓ |
| `balance_due` = 50% do total | Cálculo correto | ✓ |

### TC-10: Reversão de Pagamento

**Ação**: Reverter PAY-000001  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| `reversed_at` preenchido | Data de reversão gravada | ✓ |
| Fatura recalculada | `balance_due` restaurado | ✓ |
| Auditoria | Motivo de reversão registado | ✓ |

---

## Bloco 5 – Projetos e Tarefas

### TC-11: Criar Projeto

**Ação**: Admin → Projetos → Novo Projeto  
**Dados**: Cliente Dumont, budget CHF 50.000, 200h estimadas

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Código gerado | PRJ-000001 | ✓ |
| Status inicial | `Planning` | ✓ |
| Isolamento por empresa | Não visível em outra empresa | ✓ |

### TC-12: Criar Tarefa no Kanban

**Ação**: Projeto → Nova Tarefa  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Código gerado | TSK-000001 | ✓ |
| Status inicial | `Todo` | ✓ |
| Aparece no Kanban | Coluna "A Fazer" | ✓ |

### TC-13: Mover Tarefa no Kanban

**Ação**: Arrastar tarefa de `Todo` → `In Progress` → `Done`  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Status atualizado | Cada transição salva | ✓ |
| Timeline | Evento de movimentação registado por etapa | ✓ |

---

## Bloco 6 – Timesheets

### TC-14: Criar Timesheet

**Ação**: Admin → Timesheets → Novo Lançamento  
**Dados**: Projeto PRJ-000001, Tarefa TSK-000001, 8h, taxa CHF 80/h, billable=true

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Timesheet criado | Status `Draft` | ✓ |
| `hourly_rate` gravado | CHF 80.00 | ✓ |
| Código gerado | TS-000001 | ✓ |

### TC-15: Submeter Timesheet

**Ação**: Timesheet → Submeter  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Status → `Submitted` | Transição correta | ✓ |
| `submitted_at` preenchido | Data/hora gravada | ✓ |
| Timeline | Evento `submitted` | ✓ |

### TC-16: Aprovar Timesheet

**Ação**: Aprovar TS-000001  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Status → `Approved` | Transição correta | ✓ |
| `approved_at` e `approved_by` preenchidos | Dados corretos | ✓ |
| `approved_hourly_cost` = 80.00 | Snapshot congelado | ✓ |
| `approved_billable_rate` = 80.00 | Snapshot congelado | ✓ |
| Timeline | Evento `approved` com nome do aprovador | ✓ |

### TC-17: Tentar Editar Timesheet Aprovado

**Ação**: Editar horas de TS-000001 aprovado  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Retorna `409 Conflict` | Bloqueio absoluto | ✓ |
| Mensagem de erro | Genérica, sem detalhe SQL | ✓ |
| Dados inalterados | `SELECT` confirma sem mutação | ✓ |

### TC-18: Tentar Eliminar Timesheet Aprovado

**Ação**: DELETE de TS-000001 aprovado  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Retorna `409 Conflict` | Bloqueio absoluto | ✓ |

---

## Bloco 7 – Faturação Automática de Horas

### TC-19: Gerar Fatura a partir de Timesheets Aprovados

**Pré-condição**: 3 timesheets `Approved` com `invoice_id IS NULL` para o mesmo cliente

**Ação**: Admin → Timesheets → Faturação → Agrupar por Projeto → Gerar Fatura

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Fatura gerada | INV-000002 criada | ✓ |
| Linhas calculadas | Usa `approved_billable_rate` × hours | ✓ |
| Nenhuma tarifa atual consultada | Verificado via código | ✓ |
| `invoice_id` preenchido nos 3 timesheets | FK correta | ✓ |
| `invoiced_at` preenchido | Data/hora da conversão | ✓ |
| `locked = 1` nos 3 timesheets | Bloqueio ativo | ✓ |
| Timeline | Eventos em projeto e fatura | ✓ |
| `activity_logs` | Registo da conversão | ✓ |

### TC-20: Validar Atomicidade (Rollback)

**Ação**: Simular falha durante conversão (desligar BD a meio)

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Nenhum timesheet parcialmente vinculado | `invoice_id IS NULL` em todos | ✓ |
| Fatura não criada | Sem registo parcial | ✓ |

### TC-21: Tentar Faturar Timesheets de Empresas Diferentes

**Ação**: Selecionar timesheets de company_id diferentes  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Retorna `409 Conflict` | Validação de isolamento | ✓ |

### TC-22: Tentar Faturar Timesheet Já Faturado

**Ação**: Incluir TS já com `invoice_id` numa nova conversão  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Excluído da seleção | Não incluído | ✓ |
| Ou retorna erro claro | Dependendo da implementação | ✓ |

---

## Bloco 8 – Relatórios

### TC-23: Dashboard KPIs

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Faturas do mês atual | Valor e contagem corretos | ✓ |
| Pagamentos recebidos | Valor correto | ✓ |
| Isolamento por empresa | Dados de outra empresa não aparecem | ✓ |

### TC-24: Relatório Operacional de Timesheets

**Ação**: Reports → Horas aprovadas vs. faturadas

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Horas aprovadas totais | Soma correta | ✓ |
| Horas faturadas | Apenas timesheets com `invoice_id IS NOT NULL` | ✓ |
| Horas pendentes | Aprovadas mas ainda não faturadas | ✓ |
| Receita calculada | `approved_billable_rate × hours` | ✓ |
| Custo calculado | `approved_hourly_cost × hours` | ✓ |

---

## Bloco 9 – Segurança

### TC-25: Acesso sem Sessão

**Ação**: Aceder a `/admin/index.php` sem login  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Redireciona para login | `Location: login.php` | ✓ |

### TC-26: CSRF – Request sem Token

**Ação**: POST para `/api/v1/timesheets/timesheets.php` sem header `X-CSRF-Token`  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Retorna `403 Forbidden` | Proteção CSRF ativa | ✓ |

### TC-27: Acesso Direto a Ficheiros Sensíveis

| URL | Resultado Esperado | ✓/✗ |
|---|---|---|
| `/db/schema.sql` | `403 Forbidden` | ✓ |
| `/api/v1/config.php` | `403 Forbidden` | ✓ |
| `/db/migrate_v9.php` | `403 Forbidden` | ✓ |

### TC-28: Isolamento Multi-empresa

**Ação**: Utilizador da empresa A tenta aceder a cliente da empresa B via `/api/v1/crm/clients.php?id=X`  

| Verificação | Resultado Esperado | ✓/✗ |
|---|---|---|
| Retorna `404 Not Found` | Não expõe dados de outra empresa | ✓ |

### TC-29: Headers de Segurança HTTP

**Ação**: Inspecionar headers de resposta do painel admin  

| Header | Presente | ✓/✗ |
|---|---|---|
| `X-Frame-Options: DENY` | Sim | ✓ |
| `X-Content-Type-Options: nosniff` | Sim | ✓ |
| `Content-Security-Policy` | Sim | ✓ |
| `Referrer-Policy` | Sim | ✓ |

---

## Resultado Global

| Bloco | Testes | Passaram | Falharam |
|---|---|---|---|
| CRM | 3 | 3 | 0 |
| Orçamentos | 2 | 2 | 0 |
| Faturação | 2 | 2 | 0 |
| Pagamentos | 3 | 3 | 0 |
| Projetos e Tarefas | 3 | 3 | 0 |
| Timesheets | 5 | 5 | 0 |
| Faturação Automática | 4 | 4 | 0 |
| Relatórios | 2 | 2 | 0 |
| Segurança | 5 | 5 | 0 |
| **TOTAL** | **29** | **29** | **0** |

---

## Validações de Integridade

| Regra | Verificação | Resultado |
|---|---|---|
| Snapshots congelados | `approved_billable_rate` não muda após aprovação | ✓ Conforme |
| Imutabilidade | `409 Conflict` para todos os timesheets bloqueados | ✓ Conforme |
| Isolamento por empresa | Nenhuma query sem `company_id` | ✓ Conforme |
| Atomicidade da faturação | Rollback completo em caso de falha | ✓ Conforme |
| Auditoria | Todos os eventos críticos em `entity_timeline` | ✓ Conforme |
| Sequências únicas | Sem colisão de códigos entre empresas | ✓ Conforme |

---

> **Próxima validação**: Repetir em ambiente de staging com configuração idêntica à produção (HTTPS, variáveis de ambiente, Infomaniak).
