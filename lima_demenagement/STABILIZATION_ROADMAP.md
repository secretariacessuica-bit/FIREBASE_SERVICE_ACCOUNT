# Roadmap de Estabilização & Proteção Operacional

Este documento detalha o planeamento estratégico para a **fase de estabilização operacional** do ecossistema LIMA Solutions ERP. O objetivo principal é suspender a introdução de novos módulos e focar no endurecimento de segurança, monitorização de erros, otimização de performance e resiliência em campo.

---

## 1. Monitoramento de Erros (Error Monitoring)

*   **Logs Centrais de API**: Configurar um manipulador global de exceções em `public_site/api/v1/config.php` para canalizar erros críticos e falhas de conexão PDO para um ficheiro rotativo seguro (ex: `private_lima/storage/logs/api_errors.log`).
*   **Falhas do PWA Mobile**: Implementar captura automática de falhas de sincronização na `sync_queue` do PWA. Se uma transação falhar por dados corrompidos ou erros 500 consecutivos, o payload deve ser guardado localmente e sinalizado para envio opcional de relatórios de debug para o administrador.
*   **Integração de Sentry (Fase 2 de Estabilização)**: Avaliar a inclusão do SDK do Sentry (PHP e JS) para monitorização ativa de erros em tempo real no servidor de produção.

---

## 2. Backups e Recuperação de Desastres

*   **Backup Automatizado do MySQL**: Configurar uma tarefa Cron (via painel da Infomaniak) para executar o `mysqldump` diariamente às 02:00. Os dumps devem ser guardados comprimidos em `private_lima/backups/db/` com política de retenção de 30 dias.
*   **Backup de Mídia Segura**: Copiar periodicamente (semanalmente) os uploads de fotos operacionais, inventários e faturas PDF contidos em `private_lima/storage/` para um local secundário de armazenamento a frio.

---

## 3. Logs de Atividades e Limpeza

*   **Tabela `activity_logs`**:
    *   Estabelecer uma política de rotação. Ficheiros ou linhas de logs com mais de 180 dias devem ser arquivados ou limpos automaticamente para evitar o crescimento descontrolado do banco de dados.
*   **Activity Logs no PWA**: Garantir que logs operacionais locais no IndexedDB sejam limpos periodicamente após a confirmação de sincronização bem-sucedida pelo servidor.

---

## 4. Auditoria de Permissões e Segurança

*   **Hardening de Diretórios**:
    *   Revisar o ficheiro `public_site/db/.htaccess` para garantir que o acesso direto a ficheiros `.sql` ou `.php` de migração é restrito por IP ou totalmente negado na web.
    *   Assegurar permissões de escrita adequadas: pastas em `private_lima/storage` devem ter permissões estritas `750` ou `755`, nunca `777`.
*   **Role-Based Access Control (RBAC)**: Auditar acessos aos módulos confidenciais (Margem de Projeto, Lead Scoring, Faturamento) garantindo que apenas perfis `super_admin` e `admin` visualizem dados financeiros.

---

## 5. Limpeza de Scripts Temporários e Testes

Durante a fase de desenvolvimento ágil, diversos scripts utilitários e de verificação rápida foram criados. Para iniciar a estabilização, os seguintes ficheiros devem ser recolhidos e removidos do servidor de produção:

*   **Scripts de Migração/Correção Soltos**:
    *   `public_site/admin/run_migration_v11.php`
    *   `public_site/admin/run_migration_v12.php`
    *   `public_site/admin/run_migration_v13.php`
    *   `public_site/admin/run_migration_v14.php`
*   **Scripts de Teste e Debug**:
    *   `public_site/admin/test_scoring.php`
    *   `public_site/admin/dbg_kpi.php`
    *   `public_site/admin/audit_helper.php`
    *   `public_site/db_diagnostic.php`
    *   `public_site/debug_api.py`
    *   `public_site/fix_*.py`
    *   `public_site/migrate*.py`
    *   `public_site/upload*.py`
    *   `public_site/sftp_test.py`
    *   `public_site/cleanup*.py`

---

## 6. Otimização de Performance

*   **Caching OPcache**: Verificar se o OPcache está ativado no PHP 8.4 da Infomaniak para acelerar a compilação de scripts de rotas e helpers repetitivos.
*   **Indexação da Base de Dados**: Analisar consultas SQL lentas. Adicionar índices compostos nas colunas mais pesquisadas (ex: `projects.start_date`, `timesheets.work_date`, `crm_leads.created_at`).
*   **Minificação de Assets**: Minificar arquivos CSS e JS (`public_site/mobile/app.js`, `public_site/modules/*/assets/*`) para diminuir o consumo de rede no telemóvel dos motoristas em trânsito.

---

## 7. Treinamento de Utilizadores

*   **Manual do Motorista (PWA)**: Fornecer um guia rápido de 1 página explicando como usar o aplicativo offline, como gerenciar e limpar a fila de sincronização manual, e como responder ao banner de alerta de check-in geofenced.
*   **Painel Administrativo para Gestores**: Explicar a lógica de Lead Scoring comercial e regras de proximidade por NPA na sugestão de equipas para alinhar expectativas operacionais.

---

## 8. Operação Assistida em Campo

*   **Acompanhamento Técnico Inicial**: Durante as primeiras duas semanas, monitorizar logs de GPS e integridade de fotos convertidas por Canvas para validar o consumo de bateria e limite de armazenamento local IndexedDB no telemóvel de motoristas com aparelhos de baixa performance.
