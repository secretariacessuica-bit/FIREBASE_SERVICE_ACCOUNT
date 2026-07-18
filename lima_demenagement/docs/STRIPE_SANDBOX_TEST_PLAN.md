# LIMA Solutions ERP – Plano de Teste Sandbox Stripe (Fase A)
**Data do Plano**: 20 de Junho de 2026  
**Status**: Homologado para Testes Sandbox

Este plano descreve os cenários de teste necessários para validar a integração do gateway Stripe no LIMA Solutions ERP utilizando o modo Test Mode (Sandbox).

---

## Cenários de Teste

### Cenário 1: Pagamento Bem-sucedido (Successful Payment Scenario)
* **Objetivo**: Garantir que uma transação concluída com cartão de crédito válido no checkout Stripe resulte na quitação correta da fatura.
* **Passos**:
  1. Login no Portal do Cliente.
  2. Acesso à tela de faturas e clique em "Pagar Online" em uma fatura ativa com status `Sent` (ex: saldo CHF 1,850.00).
  3. Redirecionamento para a página de Checkout da Stripe.
  4. Digitar o cartão de testes Stripe (`4242 4242 4242 4242`, expiração futura, CVC `123`).
  5. Clicar em pagar.
  6. Redirecionamento para `invoices.php?status=success`.
  7. Recebimento em background do webhook `checkout.session.completed` contendo o ID do pagamento.
* **Validação Esperada**:
  - A fatura muda seu status para `Paid`.
  - O saldo devedor (`balance_due`) é reduzido a zero.
  - Um registro de recebimento com o número `PAY-XXXX` correspondente é inserido na tabela `payments` vinculando o ID do Stripe.
  - A notificação toast em verde "Votre paiement a été traité avec succès !" é exibida.

### Cenário 2: Falha no Pagamento (Failed Payment Scenario)
* **Objetivo**: Validar que rejeições de cartão na Stripe registram o log de erro e não alteram o estado contábil da fatura.
* **Passos**:
  1. Acessar Checkout Stripe para uma fatura ativa.
  2. Digitar um cartão de testes Stripe com comportamento de declínio (ex: `4000 0000 0000 0002` para declínio por fundos insuficientes).
  3. Provedor sinaliza erro. Clicar em voltar/cancelar.
  4. Redirecionamento para `invoices.php?status=cancelled`.
* **Validação Esperada**:
  - A fatura permanece com o status original (`Sent` ou `Overdue`).
  - O saldo devido permanece o mesmo.
  - A tabela de transações registra o status de falha, e a interface exibe toast em vermelho "Le paiement a été annulé."

### Cenário 3: Evitar Pagamentos Duplicados (Duplicate Payment Scenario)
* **Objetivo**: Impedir que cliques duplos do cliente criem sessões redundantes ou cobrem valores duplicados do mesmo saldo.
* **Passos**:
  1. Cliente clica em "Pagar Online" e a sessão do Stripe abre.
  2. Sem fechar a primeira tela, o cliente abre outra aba e clica novamente em "Pagar Online" na mesma fatura.
* **Validação Esperada**:
  - O ERP intercepta a requisição e detecta a presença da mesma chave de idempotência (`idempotency_key` correspondente ao saldo atual) no estado `Pending`.
  - Em vez de gerar uma nova sessão no Stripe, a API retorna a URL da sessão do Stripe já existente (`is_duplicate: true`).

### Cenário 4: Retentativas de Webhook (Webhook Retry Scenario)
* **Objetivo**: Confirmar que retentativas de envio do mesmo evento pelo servidor da Stripe não gerem créditos duplicados.
* **Passos**:
  1. Simular o envio repetido de um evento `checkout.session.completed` já processado com o mesmo `event_id`.
* **Validação Esperada**:
  - O ERP consulta a tabela `payment_webhooks` e reconhece o `event_id` duplicado.
  - O ERP interrompe o processamento imediatamente e retorna `200 OK` (evitando erro no webhook), sem inserir nenhum registro extra em `payments` ou realizar novos abatimentos.

### Cenário 5: Pagamento Parcial (Partial Payment Scenario)
* **Objetivo**: Garantir o abatimento parcial correto no saldo devedor quando o valor capturado for menor que o total nominal da fatura.
* **Passos**:
  1. Simular recebimento de confirmação de checkout Stripe de um valor menor que o total devido original (ex: pagamento de um sinal de CHF 500.00 de uma fatura de CHF 1,500.00).
* **Validação Esperada**:
  - A fatura atualiza seu status para `Partially Paid`.
  - O saldo devedor é decrementado para CHF 1,000.00.
  - Um pagamento de CHF 500.00 é lançado na tabela `payments`.
