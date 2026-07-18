# LIMA Solutions ERP — Plano de Testes UAT de E-mails Automáticos

Este documento descreve o plano de testes de aceitação do utilizador (UAT) para o sistema de e-mails automáticos em modo simulado.

---

## 1. Critérios de Aceitação Obrigatórios

Todos os testes UAT devem provar a conformidade com as seguintes regras de negócio:

1.  **Modo Simulado Rigoroso:** Nenhum e-mail real deve ser disparado por SMTP. Qualquer e-mail gerado pelo sistema comercial deve ser capturado e registado.
2.  **Templates Estruturados:** Todos os 5 templates (`lead_confirmation`, `internal_lead_alert`, `client_welcome`, `internal_conversion_alert`, `pipeline_status_change`) devem ser processados em HTML e gravados nos destinos de simulação.
3.  **Sanitização de Placeholders:** Nenhum placeholder cru (no formato `{nome_do_campo}`) deve permanecer nos e-mails gravados. Caso um dado seja nulo ou vazio, deve ser substituído por um hífen (`-`).
4.  **Links do Google Maps:** Os alertas internos comercial (`internal_lead_alert`) devem conter links diretos codificados para moradas válidas.
5.  **Isolamento Transacional:** E-mails de conversão de leads devem ser criados unicamente após confirmação (commit) da transação Lead &rarr; Cliente. Em caso de falha ou rollback, nenhum e-mail deve ser registado.
6.  **Gatilho Único de Pipeline:** O e-mail de mudança de estado deve ser gerado apenas se houver diferença real entre o novo e o antigo estado do lead.
7.  **Auditoria Física e de BD:** Os blocos de e-mail devem constar na tabela `simulated_emails` e ser adicionados com demarcadores ao ficheiro `/private_lima/logs/emails.log`.

---

## 2. Execução Automatizada dos Testes UAT

Um script automatizado em PHP (`public_site/db/run_uat_tests.php`) foi desenvolvido e atualizado para testar todos os cenários de ponta a ponta e garantir a integridade técnica do sistema.

### Como Executar Localmente ou via SSH

Para correr a suite de testes UAT automatizada remotamente no servidor de produção (Infomaniak), execute o seguinte comando python a partir da pasta raiz do projeto:

```bash
python scratch/run_uat.py
```

Ou, caso tenha acesso direto à consola SSH do servidor, execute:

```bash
php /home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/db/run_uat_tests.php
```

### Resultados Esperados da Consola UAT

O script deve apresentar uma saída estruturada como o exemplo seguinte, indicando aprovação em todas as etapas:

```text
=== LIMA solutions ERP - Automated UAT Test Suite ===

Test 1: Criando Lead de Teste e enviando e-mails de criação... [OK] ID: 125
Test 2: Alterando Status da Lead e enviando status change... [OK]
Test 3: Convertendo Lead para Novo Cliente e enviando e-mails... [OK] Cliente criado com código: CLI-00104
Test 4: Validando Deteção de Duplicados e associação... [OK] Duplicado detetado e associado ao cliente ID 18 corretamente.
Test 5: Validando Estado dos Leads pós-conversão... [OK]
Test 6: Verify Placeholders, Google Maps, and Log Files...
   [OK] Nenhum placeholder cru encontrado no assunto ou corpo de nenhum e-mail simulado.
   [OK] Links Google Maps gerados e codificados corretamente no alerta interno.
   [OK] O ficheiro emails.log contém todos os blocos estruturados de teste UAT.

Limpando massa de dados de teste... [OK]

=== RESULTADO FINAL: TUDO APROVADO [PASSED] ===
```

---

## 3. Roteiro de Verificação Manual (Opcional)

Se desejar validar o comportamento de forma interativa através do painel de administração e do banco de dados, siga as instruções abaixo:

### Cenário A: Receção de Lead Pública
1. Aceda à página pública de solicitação de orçamento.
2. Preencha o formulário com dados de teste (ex: `John Doe UAT`, morada de partida `1000 Lausanne`, chegada `1200 Genève`).
3. Submeta o formulário.
4. **Verificação:**
   * Aceda ao banco de dados e faça uma consulta na tabela `simulated_emails` onde o destinatário é o e-mail submetido. Verifique o assunto `[LIMA Déménagement] Confirmation de votre demande de devis` e o corpo HTML com os dados preenchidos.
   * Verifique o e-mail interno para a equipa com o assunto `[CRM Alerte] Nouvelle lead commerciale reçue - John Doe UAT`.
   * Verifique se no corpo do alerta interno os botões ou referências de morada contêm links clicáveis como:
     `https://www.google.com/maps/search/?api=1&query=1000+Lausanne`
     `https://www.google.com/maps/search/?api=1&query=1200+Gen%C3%A8ve`

### Cenário B: Alteração de Estado do Pipeline
1. Faça login no CRM administrativo.
2. Aceda ao pipeline comercial de leads.
3. Arraste ou atualize o estado de um lead ativo de `New` para `Contacted`.
4. **Verificação:**
   * Verifique se uma nova linha foi adicionada na tabela `simulated_emails` com o assunto `[CRM Pipeline] Mise à jour du statut du lead - [Nome]`, mostrando o antigo e novo estado.
   * Tente atualizar o estado para o mesmo estado ativo novamente e confirme que **nenhuma** nova linha de e-mail de status foi inserida.

### Cenário C: Conversão de Lead em Cliente
1. Abra o detalhe de um lead não convertido no CRM.
2. Clique em **Convert to Client** (Converter em Cliente).
3. Confirme a operação.
4. **Verificação:**
   * Confirme que o e-mail de boas-vindas (`client_welcome`) e o alerta de conversão comercial (`internal_conversion_alert`) foram gerados na tabela `simulated_emails` e contêm o código do cliente correto (ex: `CLI-XXXXX`).
   * Abra o log `/private_lima/logs/emails.log` no servidor e certifique-se de que os blocos estruturados foram registados com o timestamp correto.
