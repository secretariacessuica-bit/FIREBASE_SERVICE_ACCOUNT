# CRM Lead Scoring & Sales Automation

Este documento descreve as regras, pesos, categorias, limitações e as automações comerciais implementadas no LIMA Solutions ERP para a pontuação automática e priorização de oportunidades (Leads).

---

## 1. Regras de Pontuação (Lead Scoring)

O score final é calculado dinamicamente em uma escala de `0 a 100`. Se a soma dos pontos ultrapassar o limite, a pontuação é limitada a `100` usando a fórmula `min(100, calculated_score)`.

A pontuação é composta pelos seguintes fatores:

### Origem do Lead (Source)
- **Referral (Recomendação)**: `+20` pontos.
- **Marketplace**: `+15` pontos.
- **Website (Site Institucional)**: `+10` pontos.
- **Manual (Inserção no ERP)**: `+5` pontos.

### Interesse no Marketplace
Acompanha manifestações de interesse feitas pelo email/telefone associado ao lead:
- **1 interesse registado**: `+5` pontos.
- **3 interesses registados**: `+10` pontos.
- **5 ou mais interesses registados**: `+20` pontos.
- **Interesses repetidos no mesmo item**: `+10` pontos adicionais por cada interesse extra além do primeiro no mesmo objeto.

### Cliente Existente
- Se o email ou telefone do lead já estiver associado a um cliente ativo cadastrado no ERP: `+15` pontos.

### Valor Potencial (Limitações do ERP)
- **Valor > CHF 3.000**: `+20` pontos.
- **Valor > CHF 1.500**: `+10` pontos.
- **Valor > CHF 500**: `+5` pontos.

> [!IMPORTANT]
> **Regra de Valor & Limitação Técnica:**
> - Se existir um valor estimado inserido diretamente no lead, esse valor é usado para a classificação.
> - Se existir um orçamento (`quote`/`devis`) associado ao cliente convertido, é considerado o valor total do orçamento.
> - Se não existir nenhuma informação financeira confiável, esta regra de valor potencial é ignorada (não atribui pontos).

### Recência
- **Criado nos últimos 7 dias**: `+10` pontos.
- **Criado nos últimos 30 dias**: `+5` pontos.

---

## 2. Classificação de Prioridade

Os leads são divididos em quatro categorias com base na pontuação:

| Pontuação | Categoria | Descrição |
| :--- | :--- | :--- |
| **0 - 25** | **Cold** | Leads frios, baixo interesse inicial ou de origem com pouca conversão. |
| **26 - 50** | **Warm** | Leads mornos, com interesse moderado ou novos acessos via site. |
| **51 - 75** | **Hot** | Leads quentes, alta probabilidade de fechar ou clientes recorrentes. |
| **76 - 100** | **Priority** | Oportunidade prioritária comercial! Exige ação comercial urgente. |

---

## 3. Automações e Alertas Comerciais

### Alertas de Lead Priority (Evitar Spam)
- Quando um lead atinge a categoria **Priority** (score `76-100`), um alerta interno (`priority_lead_alert`) é enviado imediatamente por e-mail para a equipe comercial.
- **Prevenção de Spam**: O alerta é controlado pelo campo `priority_alert_sent_at` na tabela `crm_leads`. O alerta só é disparado se este campo estiver vazio (`NULL`), garantindo que a equipe comercial receba exatamente um alerta por oportunidade.

### Lembrete de Leads sem Resposta
- O script `/admin/process_uncontacted_leads.php` pode ser acionado de forma manual ou agendada para varrer o pipeline.
- Identifica leads ativos (que não estão em estado `Won` ou `Lost`) que não receberam nenhum contacto há mais de 7 dias (usando `last_contacted_at` ou a data de criação `created_at` caso nunca tenham sido contactados).
- Envia um e-mail consolidado de lembrete (`lead_uncontacted_reminder`) e registra a ação no histórico da entidade (`entity_timeline`) para evitar reenvios redundantes no mesmo ciclo de 7 dias.

---

## 4. Fluxo Marketplace ➔ CRM

1. Um utilizador manifesta interesse em um móvel/anúncio no catálogo público do Marketplace.
2. A API `/api/v1/marketplace/interests.php` verifica se já existe um lead ativo com o mesmo email ou telefone.
   - **Caso exista**: O lead existente é atualizado, anexando o novo interesse e mensagem às notas do lead, evitando a criação de registros duplicados redundantes.
   - **Caso não exista**: Um novo lead é inserido na tabela `crm_leads` com a origem configurada para `Marketplace`.
3. O score do lead é automaticamente recalculado e atualizado usando o modelo `Lead->updateLeadScore()`.
