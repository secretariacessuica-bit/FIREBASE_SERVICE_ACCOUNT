# LIMA Solutions ERP – Plano de Fechamento da Versão Release Candidate (RC1)
**Data do Plano**: 19 de Junho de 2026  
**Versão**: 1.0  
**Escopo**: Resolução de bloqueadores identificados na auditoria RC1

Este documento estabelece o cronograma de tarefas, estratégias de reversão (rollback) e o checklist de validação para a execução da **RC1 Closure Sprint**, visando a aprovação final do ERP para deploy estável de produção.

---

## 1. Lista de Tarefas (Task List) & Estimativas

| ID | Componente / Tarefa | Descrição Técnica | Esforço Est. |
| :--- | :--- | :--- | :--- |
| **T01** | Cleanup de Scripts de Migração | Mover arquivos `run_migration_v*.php` do diretório público `public_site/admin/` para pasta de arquivos históricos seguros em `/private_lima/history/` ou repositório Git local. | 15 min |
| **T02** | Cleanup de Arquivos de Debug | Excluir scripts temporários de depuração na raiz e no painel admin (`test_scoring.php`, `dbg_kpi.php`, `audit_helper.php`, `*.py`, `*.ps1` utilitários soltos). | 15 min |
| **T03** | Timeout de Sessão Admin | Adicionar validação de timestamp de última atividade no middleware de sessão `public_site/admin/auth.php`. Se inativo por mais de 30 minutos, destrói a sessão e redireciona para o login com mensagem informando a expiração. | 30 min |
| **T04** | Header `X-Content-Type-Options` | Inserir a diretiva `header('X-Content-Type-Options: nosniff');` no arquivo central de bootstrap do PHP de APIs `public_site/api/v1/config.php` para impedir ataques de Mime Sniffing. | 15 min |
| **T05** | Alerta de Backup Obsoleto | Ajustar o dashboard administrativo (`admin/index.php`) para decodificar o arquivo seguro `backup_status.json`. Se o campo `last_run` estiver ausente ou indicar data superior a 28 horas, renderizar o badge de backup em cor vermelha piscante com o texto `Alerta: Backup Desatualizado/Pendente`. | 45 min |
| **T06** | Índices de Performance da BD | Executar scripts de DDL adicionando índices na base de dados:<br>`CREATE INDEX idx_leads_dashboard ON crm_leads(company_id, status, created_at);`<br>`CREATE INDEX idx_projects_kanban ON projects(company_id, start_date, status);`<br>`CREATE INDEX idx_timesheets_mobile ON timesheets(company_id, user_id, status, work_date);` | 30 min |
| **T07** | Política de Retenção `activity_logs` | Implementar script utilitário rodando em background na base de dados para execução de limpeza:<br>`DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);` | 30 min |
| **T08** | Otimização do Leitor de Logs (Tail) | Modificar a leitura do arquivo `application.log` na rota administrativa de observability (`observability.php`). Substituir a leitura nativa completa de arquivo por uma leitura baseada em buffer reverso (lendo apenas as últimas 100 linhas a partir do fim do arquivo). | 1 hora |
| **T09** | Minificação e Compressão Móvel | Configurar compressão Gzip e Cache-Control no `.htaccess` público para os assets móveis (`mobile/app.js` e `mobile/style.css`). Adicionar script local automatizado para minificação do `app.js` móvel antes do deploy. | 1 hora |

---

## 2. Dependências
1. **D01**: A aplicação dos índices do banco de dados (**T06**) e a remoção dos scripts de migração (**T01**) devem ocorrer de forma coordenada. O script de banco deve rodar localmente no desenvolvimento e em staging antes da remoção definitiva dos helpers de migração do servidor de produção.
2. **D02**: A compressão de assets móveis (**T09**) depende do suporte e ativação do módulo `mod_deflate` ou `mod_gzip` na hospedagem Apache/LiteSpeed da Infomaniak.

---

## 3. Estratégia de Reversão (Rollback Strategy)
* **Reversão de Código**: Criação de tag Git prévia (`rc1-pre-hardening-vault`) antes de iniciar a sprint. Em caso de falha crítica nos arquivos compartilhados (`config.php`, `auth.php` ou `index.php`), realizar rollback imediato com o comando:
  ```bash
  git checkout rc1-pre-hardening-vault -- public_site/
  ```
* **Reversão de Banco de Dados**: Antes de executar a criação de novos índices (**T06**), gerar dump completo das tabelas afetadas. Em caso de lentidão ou lock do banco durante a criação do índice, descartar os índices criados via DDL:
  ```sql
  DROP INDEX idx_leads_dashboard ON crm_leads;
  DROP INDEX idx_projects_kanban ON projects;
  DROP INDEX idx_timesheets_mobile ON timesheets;
  ```

---

## 4. Checklist de Validação (Validation Checklist)

Antes do deploy em produção, o analista deve validar e marcar cada ponto:
- [ ] **Limpeza**: Verificar se ao acessar `http://domain.com/admin/test_scoring.php` o servidor retorna o código HTTP `404 Not Found`.
- [ ] **Timeout**: Logar no ERP, aguardar 30 minutos sem atividade no browser, clicar em qualquer link interno e garantir que a página é redirecionada para a tela de login.
- [ ] **Segurança**: Acessar um endpoint de API REST e verificar nos cabeçalhos da resposta se a chave `X-Content-Type-Options: nosniff` está presente.
- [ ] **Backup**: Renomear temporariamente o arquivo `backup_status.json` no storage para forçar a ausência do arquivo e validar se o dashboard admin exibe imediatamente o badge crítico de erro.
- [ ] **Logs**: Abrir a página `admin/observability.php` e validar se a renderização do histórico de logs é carregada em menos de 100 milissegundos sem timeout de execução PHP.
- [ ] **Rede**: Garantir no painel de ferramentas de desenvolvedor do navegador (F12, aba Network) que o arquivo `app.js` móvel é carregado compactado e que o cabeçalho `Content-Encoding: gzip` é retornado.

---

## Matriz de Decisão de Lançamento (Release Decision Matrix)

```text
       [ RC1 Audit Completed ]
                  ↓
       [ Closure Sprint Tasks ]
      (T01-T09: Cleanup & Hardening)
                  ↓
       [ Validation Checklist ] ──(Falha)──> [ Rollback to Tag ]
                  ↓ (Sucesso)
       [ Production Release V1.3 ]
                  ↓
   [ Version 2.0 Enterprise Readiness ]
     (Webhooks + Real Route GPS + 2FA)
```
* **Status Atual**: Aprovado para início da Closure Sprint.
* **Ação Bloqueada**: Nenhuma alteração de código ou deploy deve ser realizado até que o usuário autorize explicitamente o início dos trabalhos de implementação.
