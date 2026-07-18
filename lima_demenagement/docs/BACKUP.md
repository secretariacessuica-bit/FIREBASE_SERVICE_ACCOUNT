# LIMA Solutions ERP – Backup & Recovery Guide

> Versão: RC 1.0 | Ambiente de referência: Infomaniak / cPanel

---

## 1. Estratégia de Backup

| Tipo | Frequência recomendada | Retenção |
|---|---|---|
| Base de dados completa | Diária (off-peak, ex: 02h00) | 30 dias |
| Base de dados incremental | A cada 6 horas | 7 dias |
| Diretório `private/` | Semanal | 90 dias |
| Schema e migrações | A cada deploy | Indefinida (versionada no Git) |

---

## 2. Backup da Base de Dados

### 2.1 Via SSH / CLI (recomendado)

```bash
# Backup completo com compressão
mysqldump \
  --host=localhost \
  --user=lima_user \
  --password=SENHA \
  --single-transaction \
  --routines \
  --triggers \
  --add-drop-table \
  lima_solutions \
  | gzip > /home/<user>/backups/lima_solutions_$(date +%Y%m%d_%H%M%S).sql.gz

# Verificar integridade do backup
ls -lh /home/<user>/backups/
gunzip -t /home/<user>/backups/lima_solutions_*.sql.gz
```

**Opções usadas:**
- `--single-transaction`: Backup consistente sem bloquear tabelas (InnoDB)
- `--routines`: Inclui stored procedures
- `--triggers`: Inclui triggers
- `--add-drop-table`: Facilita restauração em BD existente

### 2.2 Automação via Cron (SSH)

```bash
# Editar crontab
crontab -e

# Backup diário às 02h00
0 2 * * * /usr/bin/mysqldump --host=localhost --user=lima_user --password=SENHA --single-transaction --routines lima_solutions | gzip > /home/<user>/backups/db_$(date +\%Y\%m\%d).sql.gz 2>/dev/null

# Limpar backups com mais de 30 dias
0 3 * * * find /home/<user>/backups/ -name "*.sql.gz" -mtime +30 -delete
```

### 2.3 Via phpMyAdmin (Infomaniak)

1. Abrir phpMyAdmin → Selecionar `lima_solutions`
2. Aba **Exportar** → Método: **Personalizado**
3. Selecionar todas as tabelas
4. Formato: **SQL**
5. Marcar: "Adicionar DROP TABLE" e "Usar transação"
6. Comprimir: **gzip**
7. **Executar** e guardar o ficheiro

### 2.4 Via Painel Infomaniak

O Infomaniak oferece **backups automáticos diários** da base de dados no painel:
- Hosting → Base de dados → **Backups**
- Disponível para restauração com 1 clique (retenção de 14 dias)

---

## 3. Backup do Diretório `private/`

```bash
# Backup do diretório private (credenciais e storage)
tar -czf /home/<user>/backups/private_$(date +%Y%m%d).tar.gz \
    /home/<user>/private/

# Verificar conteúdo sem descompactar
tar -tzf /home/<user>/backups/private_$(date +%Y%m%d).tar.gz
```

> ⚠️ **Atenção**: Os backups de `private/` contêm credenciais de base de dados. **Nunca armazenar em locais públicos** (Git, S3 público, Dropbox partilhado, etc.).

---

## 4. Backup dos Ficheiros da Aplicação

O código-fonte deve estar versionado em **Git**. Para backup completo do `public_html/`:

```bash
# Backup do diretório público
tar -czf /home/<user>/backups/public_$(date +%Y%m%d).tar.gz \
    /home/<user>/public_html/

# Excluindo uploads e cache (opcional)
tar -czf /home/<user>/backups/code_$(date +%Y%m%d).tar.gz \
    --exclude='*/private/storage/*' \
    /home/<user>/public_html/
```

---

## 5. Procedimento de Restauração

### 5.1 Restaurar Base de Dados

```bash
# Opção A: A partir de dump comprimido
gunzip -c /home/<user>/backups/lima_solutions_20260616.sql.gz \
    | mysql --host=localhost --user=lima_user --password=SENHA lima_solutions

# Opção B: A partir de dump não comprimido
mysql --host=localhost --user=lima_user --password=SENHA \
    lima_solutions < /home/<user>/backups/lima_solutions_20260616.sql

# Verificar restauração
mysql --host=localhost --user=lima_user --password=SENHA \
    -e "SELECT COUNT(*) FROM lima_solutions.invoices;"
```

> ⚠️ **Em BD existente com dados**: O dump com `--add-drop-table` apaga e recria todas as tabelas. Certifique-se de que pretende substituir todos os dados.

### 5.2 Restaurar `private/`

```bash
# Restaurar credenciais e storage
tar -xzf /home/<user>/backups/private_20260616.tar.gz \
    -C /home/<user>/

# Verificar permissões após restauração
chmod 750 /home/<user>/private/
chmod 640 /home/<user>/private/config.php
```

### 5.3 Restaurar Código da Aplicação

```bash
# Via Git (recomendado)
git -C /home/<user>/public_html/ pull origin main

# Via arquivo tar
tar -xzf /home/<user>/backups/public_20260616.tar.gz \
    -C /home/<user>/
```

---

## 6. Ponto de Restauração (Disaster Recovery)

Em caso de falha catastrófica, o procedimento completo de recuperação é:

```
1. [ ] Provisionar novo ambiente (servidor / conta Infomaniak)
2. [ ] Criar base de dados lima_solutions e utilizador MySQL
3. [ ] Restaurar schema: importar backup .sql via phpMyAdmin ou CLI
4. [ ] Fazer upload do código da aplicação (Git pull ou tar)
5. [ ] Restaurar private/config.php com credenciais do novo servidor
6. [ ] Ajustar permissões de ficheiros (chmod 640 config.php)
7. [ ] Verificar acesso ao painel: https://dominio.com/admin/login.php
8. [ ] Verificar integridade: criar cliente de teste e verificar sequências
9. [ ] Ativar SSL e redirecionamento HTTPS
10.[ ] Notificar utilizadores do restabelecimento do serviço
```

---

## 7. Teste de Restauração

**Recomendação**: Testar a restauração mensalmente num ambiente separado:

```bash
# Criar BD de teste
mysql -u root -p -e "CREATE DATABASE lima_solutions_test;"

# Restaurar backup para BD de teste
gunzip -c backup.sql.gz | mysql -u root -p lima_solutions_test

# Verificar integridade básica
mysql -u root -p lima_solutions_test -e "
  SELECT 'companies' as t, COUNT(*) FROM companies UNION
  SELECT 'clients', COUNT(*) FROM clients UNION
  SELECT 'invoices', COUNT(*) FROM invoices UNION
  SELECT 'timesheets', COUNT(*) FROM timesheets;
"

# Limpar
mysql -u root -p -e "DROP DATABASE lima_solutions_test;"
```
