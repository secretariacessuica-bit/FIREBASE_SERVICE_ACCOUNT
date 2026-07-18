# LIMA Solutions ERP — Fluxo de Eventos e Integração de E-mails

Este documento define os gatilhos, o fluxo de execução transacional e a arquitetura de armazenamento do sistema de e-mails em modo simulado.

---

## 1. Mapeamento de Eventos e Gatilhos

Os e-mails simulados são gerados automaticamente através de ações no sistema público e administrativo. A tabela abaixo descreve o acoplamento de eventos:

| Evento de Origem | Gatilho Técnico | Template Disparado | Destinatário | Propósito |
| :--- | :--- | :--- | :--- | :--- |
| **Submissão de Orçamento** | `POST /api/v1/leads` (Público) | `lead_confirmation` | Cliente (Lead) | Confirmar receção do pedido de orçamento e enviar resumo dos dados. |
| **Submissão de Orçamento** | `POST /api/v1/leads` (Público) | `internal_lead_alert` | Equipa Comercial | Notificar a equipa sobre novo lead comercial para rápida qualificação. |
| **Atualização de Estado** | `PUT /api/v1/leads` (Admin) | `pipeline_status_change` | Equipa Comercial | Notificar a equipa sobre movimentação de leads no funil de vendas. |
| **Conversão Concluída** | `POST /api/v1/leads?action=convert` | `client_welcome` | Cliente (Dossier) | Dar as boas-vindas ao cliente com o código exclusivo de conta. |
| **Conversão Concluída** | `POST /api/v1/leads?action=convert` | `internal_conversion_alert` | Equipa Comercial | Confirmar a criação ou associação do dossier de cliente definitivo. |

---

## 2. Regras de Transação e Integridade

Para garantir a coerência do banco de dados e evitar e-mails incorretos, são aplicadas duas regras fundamentais de integridade:

### 2.1. Isolamento na Conversão (Lead &rarr; Cliente)
*   **Regra:** O envio do e-mail de boas-vindas ao cliente (`client_welcome`) e do alerta administrativo (`internal_conversion_alert`) está acoplado ao ciclo da transação do banco de dados.
*   **Funcionamento:** Os e-mails de conversão são disparados apenas **após o commit bem-sucedido** da transação SQL (`$pdo->commit()`) executada em `Lead::convertToClient`.
*   **Prevenção:** Se ocorrer um erro durante a criação do cliente, duplicação ou atualização da lead, a transação é revertida (`$pdo->rollBack()`) e **nenhum e-mail de conversão é gerado**.

### 2.2. Mudança Efetiva de Estado no Pipeline
*   **Regra:** O template `pipeline_status_change` só é disparado se o novo estado do pipeline comercial for realmente diferente do estado anterior.
*   **Funcionamento:** A API compara `$oldLead['status']` com a variável `$status` recebida na requisição PUT. Se forem idênticos, o envio do e-mail simulado é ignorado.

---

## 3. Geração de Links Google Maps para Moradas

Para auxiliar a equipa de planeamento logístico, os e-mails internos do tipo `internal_lead_alert` incluem automaticamente links diretos de mapas para as moradas fornecidas.

*   **Independência de APIs:** Não há qualquer dependência da Google Maps API oficial para evitar custos ou chaves de acesso.
*   **Geração:** O helper `EmailHelper` codifica os endereços de partida e chegada usando a função `urlencode()` do PHP e monta uma URL simples de pesquisa.
*   **Fórmula:**
    `https://www.google.com/maps/search/?api=1&query={encoded_address}`
*   **Aparência no Corpo HTML:**
    `<a href="https://www.google.com/maps/search/?api=1&query=..." target="_blank" style="color: #007a87; text-decoration: underline;">(Voir sur Google Maps)</a>`
*   **Integridade dos Dados:** O link é gerado dinamicamente **apenas no corpo do e-mail simulado**. As moradas originais no banco de dados (`crm_leads`) continuam guardadas como texto normal, sem poluição de URLs HTML.

---

## 4. Arquitetura de Auditoria (Modo Simulado Rigoroso)

Para garantir segurança, conformidade e auditoria sem enviar e-mails reais nesta etapa, todas as comunicações automáticas gravam em dois destinos:

### 4.1. Tabela `simulated_emails`
Cada e-mail simulado gera uma inserção no banco de dados contendo:
*   `company_id` (vinculação da empresa ativa)
*   `recipient` (e-mail do destinatário)
*   `subject` (assunto processado)
*   `body` (corpo HTML final renderizado com placeholders substituídos)
*   `headers` (cabeçalhos de e-mail estruturados em JSON)
*   `created_at` (carimbo de data/hora)

### 4.2. Log Físico `private_lima/logs/emails.log`
Os e-mails gerados são anexados fisicamente a um ficheiro de logs localizado na pasta privada segura `/private_lima/logs/emails.log`. Cada bloco de e-mail é demarcado de forma estruturada:

```text
=================================================================
TIMESTAMP:   YYYY-MM-DD HH:MM:SS
EMAIL ID:    [ID da Tabela]
COMPANY ID:  [ID da Empresa]
RECIPIENT:   [Endereço de E-mail]
SUBJECT:     [Assunto Renderizado]
HEADERS:     {"Content-Type":"text\/html; charset=UTF-8"}
CONTENT:
<!DOCTYPE html>
<html>
... [Corpo HTML do E-mail Completo] ...
</html>
=================================================================
```
