# LIMA Solutions ERP – Relatório de Implementação Stripe Sandbox
**Data de Emissão**: 20 de Junho de 2026  
**Status**: Implementado e Homologado (Test Mode / Sandbox)  
**Versão Base**: V1.3-Hardened

Este relatório documenta os arquivos criados e modificados para a integração completa do gateway Stripe em modo Sandbox no ecossistema ERP da **Lima Déménagement**.

---

## 1. Arquivos Criados

1. **[migrate_v17_stripe.php](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/db/migrate_v17_stripe.php)**:
   - Script de migração restrito a execução em CLI/SSH (bloqueado via HTTP `PHP_SAPI !== 'cli'`). Cria as tabelas `payment_transactions`, `payment_webhooks` e `payment_refunds`.
2. **[create-session.php](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/api/v1/payments/create-session.php)**:
   - API-First de checkout. Implementa a geração do hash SHA-256 de idempotência, validação de segurança do `company_id` e redirecionamento dinâmico.
3. **[webhook.php](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/api/v1/payments/webhook.php)**:
   - Handler de eventos do provedor. Implementa assinatura nativa, replay protection de 5 minutos, desduplicação de logs de eventos e integração contábil com o modelo de pagamentos.
4. **[status.php](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/api/v1/payments/status.php)**:
   - Endpoint público de consulta de status de transações de faturas.
5. **[STRIPE_SANDBOX_TEST_PLAN.md](file:///c:/Users/Wande/Documents/ia/lima_demenagement/docs/STRIPE_SANDBOX_TEST_PLAN.md)**:
   - Especificação dos cenários de teste da Sandbox.

---

## 2. Arquivos Modificados

1. **[config.php (Private)](file:///c:/Users/Wande/Documents/ia/lima_demenagement/private/config.php)**:
   - Adicionadas definições das chaves `STRIPE_TEST_SECRET_KEY`, `STRIPE_TEST_WEBHOOK_SECRET` e a constante `APP_ENV = 'local'` protegida.
2. **[invoices.php (Portal)](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/portal/invoices.php)**:
   - Modificada a interface do portal do cliente para apresentar o botão "Pagar Online" e injetada a lógica JS de integração com a API e o simulador local de checkout.
3. **[schema.sql](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/db/schema.sql)**:
   - Inseridas as definições das tabelas Stripe ao esquema oficial consolidado do ERP.

---

## 3. Segurança e Regras Fiscais Preservadas

* **Não-Exposição de Chaves**: As chaves Stripe são carregadas estritamente da pasta privada `/private_lima/`, sem presença física em diretórios públicos.
* **Isolamento de Tenant**: Todo o processamento de API valida a sessão e o `company_id` ativo antes de interagir com as faturas.
* **Auditabilidade Contábil**: O webhook Stripe não manipula faturas diretamente; ele invoca o método `$paymentModel->create()` herdando todas as regras fiscais de conciliação, geração de PDF de recibos e logs do módulo administrativo.
