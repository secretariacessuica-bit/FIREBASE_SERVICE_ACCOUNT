# LIMA Solutions ERP – Relatório de Teste Stripe Sandbox
**Data de Emissão**: 20 de Junho de 2026  
**Ambiente de Testes**: Local Sandbox / Test Mode  
**Versão Base**: V1.3-Hardened

Este relatório consolida os resultados dos testes executados com base no plano de validação da Sandbox Stripe.

---

## Resultados dos Casos de Teste

### CT1: Pagamento Bem-sucedido (Successful Payment Scenario)
* **Resultado Esperado**: Fatura atualizada para `Paid`, saldo zerado e recibo gerado.
* **Resultado Obtido**: **Passou**.
  - O simulador local de checkout executou o webhook com sucesso.
  - A fatura teve seu status alterado de `Sent` para `Paid` de forma transacional.
  - O recibo sequencial `PAY-XXXX` foi criado e vinculado ao ID do Stripe.

### CT2: Falha no Pagamento (Failed Payment Scenario)
* **Resultado Esperado**: Fatura inalterada, status de erro registrado.
* **Resultado Obtido**: **Passou**.
  - O retorno de cancelamento redirecionou o usuário de volta para o portal exibindo o toast vermelho correspondente.
  - A integridade contábil da fatura foi totalmente preservada.

### CT3: Evitar Pagamentos Duplicados (Duplicate Payment Scenario)
* **Resultado Esperado**: Mesma URL de checkout retornada ao clicar duas vezes.
* **Resultado Obtido**: **Passou**.
  - A chave de idempotência SHA-256 interceptou a segunda chamada retornando o ID e URL pendentes anteriores, impedindo a criação de registros redundantes.

### CT4: Retentativas de Webhook (Webhook Retry Scenario)
* **Resultado Esperado**: Webhook redundante ignorado com retorno HTTP `200`.
* **Resultado Obtido**: **Passou**.
  - A tabela `payment_webhooks` com índice único `(provider, event_id)` evitou duplicação, respondendo de forma idempotente sem criar pagamentos fantasmas.

### CT5: Pagamento Parcial (Partial Payment Scenario)
* **Resultado Esperado**: Abatimento parcial no saldo e status atualizado para `Partially Paid`.
* **Resultado Obtido**: **Passou**.
  - A recalculação base de faturas computou o pagamento, reduziu o saldo e alterou o status conforme as regras de negócio pré-existentes.

---

## Configurações Pendentes Requeridas pelo Usuário

Para ativar a integração em ambiente de homologação remoto (Staging) ou Produção, o administrador da **Lima Déménagement** deve realizar as seguintes configurações:
1. **Configuração das Credenciais Privadas**:
   - Inserir as credenciais reais no arquivo privado `/sites/private_lima/config.php` na hospedagem:
     ```php
     define('STRIPE_TEST_SECRET_KEY', 'sk_test_...');
     define('STRIPE_TEST_WEBHOOK_SECRET', 'whsec_...');
     ```
2. **Definição de URL de Webhook no Painel Stripe**:
   - Acessar o Dashboard da Stripe em Modo Teste ➔ Developers ➔ Webhooks.
   - Adicionar o endpoint público de recepção de eventos:
     `https://limasolutions.ch/api/v1/payments/webhook.php`
   - Configurar o webhook para escutar exclusivamente o evento:
     `checkout.session.completed`.
