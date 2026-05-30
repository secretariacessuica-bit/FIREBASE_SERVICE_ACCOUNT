# ⚠️ LEIA ANTES DE EDITAR — ARQUIVO HISTÓRICO / BACKUP GERAL [MÓDULO 3]

> Protocolo ativo. Esta pasta é somente para backup e referência.
> Protocolo completo: `docs/PROTOCOLO_CONSULTA_PADRAO.md`

---

## 📍 IDENTIDADE DESTE MÓDULO

| Campo | Valor |
|---|---|
| **Tipo** | Arquivo de backup — sem deploy ativo |
| **Escopo** | Qualquer app do ecossistema CES |
| **Status** | 💾 Somente leitura / referência |

## 📂 ESTRUTURA DO ARQUIVO

| Pasta | Conteúdo |
|---|---|
| `REPOSITORIO_DE_RESTAURACAO_E_VERSOES/` | Snapshots estáveis de produção de todos os módulos |
| `BACKUPS_SISTEMA/` | Backups datados de estabilizações |
| `LEGADO_BULLE_APP/` | Arquivos legados do App Bulle Operacional |
| `LEGADO_LAUSANNE_ADMIN/` | Arquivos legados do App Lausanne Admin |
| `ATIVOS_ANTIGOS_BULLE/` | Assets e mídias antigas do Bulle |

## 🔒 REGRAS DESTA PASTA

1. **NUNCA** editar arquivos daqui para colocar direto em produção — sempre copiar para o módulo correto primeiro
2. **NUNCA** fazer deploy a partir desta pasta
3. **SEMPRE** nomear backups com data: ex. `BACKUP_MODULO1_v2_20260529`
4. Esta pasta **não tem Firebase Project** associado — é local apenas

## ✅ COMO FAZER BACKUP CORRETO

Antes de qualquer grande mudança em qualquer módulo:
```
1. Copiar os arquivos relevantes para REPOSITORIO_DE_RESTAURACAO_E_VERSOES/
2. Nomear a pasta com: BACKUP_[MODULO]_[VERSAO]_[DATA]
3. Registrar no protocolo principal se for um marco importante
```

---
*Protocolo v1.0 | CES Ecossistema Digital*
