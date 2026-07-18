# Project Margin Analytics

Este documento descreve o funcionamento, as fórmulas matemáticas, as fontes de dados e as métricas calculadas para a análise de margem operacional por projeto no ERP da LIMA Solutions.

---

## 1. Fórmulas de Cálculo

A rentabilidade de cada projeto é avaliada com base nas seguintes fórmulas matemáticas:

### Receita (Revenue)
A receita do projeto é determinada pelo valor total faturado do projeto.
\[\text{Receita} = \text{Valor total das faturas associadas ao projeto} (\text{status} \notin \{\text{'Draft'}, \text{'Cancelled'}\})\]

### Custo de Mão de Obra (Labor Cost)
O custo operacional é determinado pelo somatório das horas de trabalho registadas nos timesheets multiplicadas pela taxa horária do colaborador.
\[\text{Custo de Mão de Obra} = \sum (\text{Horas do Apontamento} \times \text{Taxa Horária do Colaborador})\]
*Nota: Para garantir exatidão histórica e auditoria, utiliza-se prioritariamente o snapshot congelado no momento de aprovação (`approved_hourly_cost`), com fallback para o valor dinâmico (`hourly_rate`) se a hora ainda estiver pendente ou em rascunho.*

### Margem Bruta (Gross Margin)
A margem operacional líquida do projeto em valor absoluto.
\[\text{Margem Bruta} = \text{Receita} - \text{Custo de Mão de Obra}\]

### Margem % (Margin Percentage)
A rentabilidade relativa expressa em percentagem.
\[\text{Margem \%} = \left( \frac{\text{Margem Bruta}}{\text{Receita}} \right) \times 100\]
*Se a receita for igual a zero, a margem percentual será renderizada como `-100%` (ou `0%` se não houver custos).*

---

## 2. Fontes de Dados

Os dados são cruzados a partir das seguintes tabelas normalizadas do banco de dados:
* **`projects`** : Cadastro do projeto (título, orçamento/budget estimado).
* **`timesheets`** : Horas de trabalho registadas por colaborador (`hours`), taxa horária do colaborador (`hourly_rate` e snapshot `approved_hourly_cost`), status (`Approved`, `Submitted`, `Draft`).
* **`invoices`** : Faturas associadas direta ou indiretamente via lote de faturamento (`invoice_id` ligado aos timesheets ou faturas gerais da mesma entidade).

---

## 3. Alertas e Indicadores de Negócio

Para apoiar a gestão executiva nas decisões de otimização operacional, o sistema monitoriza a margem e sinaliza riscos:
* **Margem Segura (Green)** : \(\ge 25\%\). O projeto opera com margem de lucro saudável.
* **Margem Crítica (Warning/Red)** : \(< 25\%\). O projeto consome demasiada mão de obra relativamente ao valor faturado, exigindo revisão de preços ou processos.
