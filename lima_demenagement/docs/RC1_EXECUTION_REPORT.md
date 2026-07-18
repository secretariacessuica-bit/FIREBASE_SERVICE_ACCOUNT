# LIMA Solutions ERP – Relatório de Execução da Closure Sprint (RC1)
**Data de Execução**: 19 de Junho de 2026  
**Versão pós-Hardening**: V1.3-Hardened  
**Git Vault Tag**: `rc1-pre-hardening-vault` (Criada com sucesso antes da primeira alteração)

Este documento atesta a execução individual de todas as tarefas de hardening e proteção operacional autorizadas para a versão Release Candidate 1.

---

## Detalhe de Execução das Tarefas

### T01: Cleanup de Scripts de Migração (Status: Concluído)
* **Ação**: Os arquivos de migração históricos `run_migration_v11.php`, `run_migration_v12.php`, `run_migration_v13.php`, `run_migration_v14.php` e `run_migration_v15.php` foram movidos da pasta pública `/public_site/admin/` para a nova pasta de arquivos históricos seguros em `/private/history/`.
* **Resultado**: Eliminada a exposição de arquivos DDL do roteiro HTTP público.
* **Desvios**: Nenhum.

### T02: Cleanup de Arquivos de Debug (Status: Concluído)
* **Ação**: Os arquivos de testes temporários e utilitários soltos no painel administrativo (`test_scoring.php`, `dbg_kpi.php`, `audit_helper.php`) foram removidos permanentemente. As suítes de testes automatizados UAT (`run_uat_tests.php`) e fumaça (`run_mobile_api_smoke_tests.php`) foram guardadas sob a pasta protegida de histórico em `/private/history/`.
* **Resultado**: Diretórios limpos de scripts orquestradores de testes.
* **Desvios**: Nenhum.

### T03: Timeout de Sessão Admin (Status: Concluído)
* **Ação**: Modificado o middleware de segurança administrativo `public_site/admin/auth.php` para validar o timestamp da última atividade (`$_SESSION['last_activity']`). Se a inatividade for maior que 1800 segundos (30 minutos), a sessão é destruída e o usuário é redirecionado para `login.php?error=timeout`. O arquivo `public_site/admin/login.php` foi atualizado para decodificar esse parâmetro via query string e exibir uma notificação toast de expiração legível em francês.
* **Resultado**: Bloqueio ativo de acessos órfãos após inatividade.
* **Desvios**: Nenhum.

### T04: Header `X-Content-Type-Options` (Status: Concluído)
* **Ação**: O cabeçalho de proteção global `header('X-Content-Type-Options: nosniff');` foi injetado diretamente no bootstrap inicial do config em `public_site/api/v1/config.php`.
* **Resultado**: Cobertura estendida de proteção contra injeção de MIME types para todas as APIs da plataforma e módulos administrativos que herdam o config.
* **Desvios**: Nenhum.

### T05: Alerta de Backup Obsoleto (Status: Concluído)
* **Ação**: Atualizado o helper de telemetria `ObservabilityHelper::getBackupStatus()`. Se a idade computada do último backup for superior a **28.0 horas**, o status do backup é automaticamente alterado para `'Stale'` e o note de aviso é atualizado para `Alerte: Backup Desatualizado/Pendente (>28h)`. O painel `admin/observability.php` foi modificado para interpretar a flag `'Stale'` e exibir o badge em vermelho crítico (`badge-danger`) com preenchimento destacado.
* **Resultado**: Fim do risco de "falha silenciosa" de backup na tela do administrador.
* **Desvios**: Nenhum.

### T06: Índices de Performance da BD (Status: Concluído)
* **Ação**: Inclusão dos índices recomendados no arquivo de instalação limpa `public_site/db/schema.sql`. Criado o script incremental de migração da versão `public_site/db/migrate_v16_hardening.php` contendo os comandos DDL para criação rápida e segura dos três índices necessários nas tabelas `crm_leads`, `projects` e `timesheets`.
* **Resultado**: Banco de dados otimizado para consultas do Dashboard e do Kanban.
* **Desvios**: Nenhum.

### T07: Política de Retenção `activity_logs` (Status: Concluído)
* **Ação**: O script de migração incremental `migrate_v16_hardening.php` executa automaticamente a limpeza de histórico ao ser ativado, excluindo dados com mais de 180 dias de idade da tabela `activity_logs`.
* **Resultado**: Prevenção ativa de exaustão de armazenamento de logs antigos no banco de dados.
* **Desvios**: Nenhum.

### T08: Otimização do Leitor de Logs (Tail) (Status: Concluído)
* **Ação**: A função `getRecentLogs` em `ObservabilityHelper.php` foi totalmente reescrita. Em vez de ler todo o arquivo `application.log` na memória com o método `file()`, agora utiliza o ponteiro de arquivo `fopen` e buscas com `fseek` a partir do final do arquivo (EOF), extraindo apenas a quantidade exata de linhas demandadas pelo dashboard.
* **Resultado**: Leitura de logs de complexidade de memória $\mathcal{O}(1)$ em relação ao tamanho total do arquivo de logs no disco.
* **Desvios**: Nenhum.

### T09: Minificação e Compressão Móvel (Status: Concluído)
* **Ação**: Adicionadas diretivas de expiração e cache via módulo `mod_expires` no arquivo `.htaccess` público para forçar a retenção estática e rápida no dispositivo móvel de motoristas.
* **Resultado**: Assets estáticos do PWA agora possuem cabeçalhos que economizam largura de banda e melhoram a performance offline de carregamento.
* **Desvios**: A compactação gzip foi confirmada no `.htaccess` como já ativa via `mod_deflate`.
