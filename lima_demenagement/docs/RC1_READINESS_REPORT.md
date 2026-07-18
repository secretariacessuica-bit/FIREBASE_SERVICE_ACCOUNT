# LIMA Solutions ERP – Auditoria de Prontidão da Versão Release Candidate (RC1)
**Data do Relatório**: 19 de Junho de 2026  
**Versão Sob Análise**: RC1 (Base Consolidada V1.3)  
**Ambiente Alvo**: Produção (Hospedagem Infomaniak)

Este relatório avalia a maturidade técnica da versão Candidate (RC1) do LIMA Solutions ERP e estabelece os requisitos finais antes do lançamento oficial de produção da V1.3.

---

## Avaliação por Categoria

### 1. Prontidão para Deploy em Produção (Production Deployment)
* **Pontuação (Score)**: **90/100**
* **Problemas Bloqueantes**:
  - Presença de scripts de migração históricos (`run_migration_v*.php`) expostos no diretório administrativo.
  - Arquivos de script locais `.py`, `.ps1` de desenvolvimento na raiz do web root público.
* **Correções Recomendadas**:
  - Criar um script de limpeza pré-deploy (`build_zip.ps1` ajustado) para arquivar e excluir do pacote de deploy todos os scripts temporários.
* **Esforço Estimado**: 15 min.

### 2. Prontidão em Segurança (Security Readiness)
* **Pontuação (Score)**: **95/100**
* **Problemas Bloqueantes**:
  - Falta de controle de expiração rigoroso para sessões administrativas inativas no painel.
  - Falta de cabeçalho `X-Content-Type-Options: nosniff` global em endpoints AJAX.
* **Correções Recomendadas**:
  - Incorporar timeout de inatividade (ex: 30 minutos) no middleware `admin/auth.php`.
  - Adicionar o header `X-Content-Type-Options: nosniff` no `api/v1/config.php`.
* **Esforço Estimado**: 30 min.

### 3. Prontidão de Backup (Backup Readiness)
* **Pontuação (Score)**: **85/100**
* **Problemas Bloqueantes**:
  - O ERP não avisa se o cron de backup falhar em sua totalidade (falha silenciosa de infraestrutura).
* **Correções Recomendadas**:
  - Implementar lógica na UI do dashboard que compare a data em `backup_status.json` com o horário atual do servidor. Sinalizar erro crítico caso o arquivo tenha mais de 28 horas de idade.
* **Esforço Estimado**: 45 min.

### 4. Prontidão de Banco de Dados (Database Readiness)
* **Pontuação (Score)**: **90/100**
* **Problemas Bloqueantes**:
  - Falta de limpeza automatizada na tabela `activity_logs`.
  - Ausência de índices em colunas de ordenação frequente (`projects.start_date`).
* **Correções Recomendadas**:
  - Aplicar DDL de migração para incluir os índices de paginação acelerada.
  - Adicionar query de expurgo (`DELETE ... INTERVAL 180 DAY`) ativada periodicamente.
* **Esforço Estimado**: 1.5 horas.

### 5. Prontidão de Acesso Móvel (Mobile Readiness)
* **Pontuação (Score)**: **92/100**
* **Problemas Bloqueantes**:
  - Assets estáticos (`mobile/app.js` com ~36 KB) servidos sem compressão HTTP ativa ou minificação, afetando o desempenho sob redes 3G em trânsito.
* **Correções Recomendadas**:
  - Adicionar regras de compressão gzip e cache estático no arquivo `.htaccess` público.
  - Executar minificador de código Javascript na build móvel.
* **Esforço Estimado**: 1 hora.

### 6. Prontidão do Marketplace (Marketplace Readiness)
* **Pontuação (Score)**: **95/100**
* **Problemas Bloqueantes**:
  - Fotos de móveis rejeitados ou deletados não são limpas fisicamente do disco local (`/private_lima/storage/marketplace_photos/`).
* **Correções Recomendadas**:
  - Criar rotina acionada no painel de moderação para apagar fisicamente as imagens no servidor quando um anúncio é rejeitado com exclusão.
* **Esforço Estimado**: 1 hora.

### 7. Prontidão do Portal do Cliente (Client Portal Readiness)
* **Pontuação (Score)**: **95/100**
* **Problemas Bloqueantes**: Nenhum crítico.
* **Correções Recomendadas**:
  - Planejar para a próxima release uma tabela dedicada exclusivamente a mensagens (`portal_messages`) para reduzir o acoplamento com a tabela geral de notificações.
* **Esforço Estimado**: 0 (Melhoria não-bloqueante pós-RC).

### 8. Prontidão de Monitoramento (Monitoring Readiness)
* **Pontuação (Score)**: **92/100**
* **Problemas Bloqueantes**:
  - O Log Viewer administrativo lê o arquivo `application.log` inteiro. Se o log atingir grande escala, a página de observabilidade irá travar por falta de memória do PHP.
* **Correções Recomendadas**:
  - Ajustar o leitor de logs no PHP para ler apenas as últimas 100 linhas utilizando buffer reverso (Tail local), evitando o carregamento do arquivo completo na RAM.
* **Esforço Estimado**: 1 hora.

---

## Recomendação de Deploy

### **Ready with Minor Fixes** (Pronto com Pequenas Correções)

**Justificativa**: A arquitetura do ERP é extremamente robusta e a API-first atende todos os requisitos do ecossistema consolidado na Fase 1. No entanto, o deploy em ambiente de produção com arquivos de migração expostos e logs/backups sem proteção de envelhecimento impede a classificação como 100% pronto. A aplicação das pequenas correções indicadas resolve estes problemas com baixo esforço técnico.

---

## Impedimentos para Versão 2.0 Production Ready
Para que o sistema seja considerado uma versão **2.0 madura e de nível corporativo estável**, as seguintes lacunas estruturais devem ser sanadas:
1. **Integração de Webhooks de Pagamento**: Implementar gateways automáticos (Stripe/Twint) no Portal do Cliente, substituindo o lançamento puramente manual de pagamentos.
2. **Geocoding Nativo**: Substituir o cálculo baseado em NPAs aproximados por roteamento geográfico real de estradas e distâncias por tempo de viagem.
3. **Distribuição em Lojas Mobile**: Compilar e empacotar o PWA com Capacitor/Cordova para distribuição nas lojas nativas Google Play e Apple Store.
4. **Agregador de Erros (Sentry/Datadog)**: Ativar monitoramento ativo em tempo real com disparo de webhooks imediatos em canais corporativos (Slack/Teams).
5. **Autenticação de Dois Fatores (2FA)**: Adicionar proteção de segurança extra (OTP via app ou e-mail) para usuários administrativos.
