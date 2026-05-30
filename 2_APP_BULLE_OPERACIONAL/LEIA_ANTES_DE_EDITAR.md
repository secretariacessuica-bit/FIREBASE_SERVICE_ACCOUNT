# ⚠️ LEIA ANTES DE EDITAR — APP BULLE OPERACIONAL [MÓDULO 2]

> Protocolo ativo. Qualquer edição nesta pasta deve respeitar estas regras.
> Protocolo completo: `docs/PROTOCOLO_CONSULTA_PADRAO.md`

---

## 📍 IDENTIDADE DESTE MÓDULO

| Campo | Valor |
|---|---|
| **Filial** | CES Bulle |
| **Tipo** | App interno operacional |
| **Firebase Project** | `catedral-connect-bf717` |
| **Hosting Target** | `app` |
| **Deploy** | `firebase deploy --only hosting:app` |
| **Tema visual** | Dark Premium / Mobile-First |

## 📄 PÁGINAS DESTE MÓDULO

| Arquivo | Função |
|---|---|
| `admin.html` | Painel administrativo |
| `recepcao.html` | Módulo de recepção |
| `altar.html` | Módulo do altar/mídia |
| `kids.html` | Módulo infantil |
| `connect.html` | Formulário de conexão |
| `portal.html` | Portal de entrada |
| `mobile.html` | Versão mobile |
| `app.html` | Ponto de entrada do app |

## 🔒 REGRAS DESTE MÓDULO

1. **NÃO** usar o Project ID de Lausanne (`catedral-connect-6c55e`) aqui
2. **NÃO** misturar arquivos ou lógica do Módulo 4 (Lausanne Admin)
3. **NÃO** aplicar tema White & Gold (esse tema é exclusivo de Lausanne)
4. **SEMPRE** manter identidade Dark Premium
5. **SEMPRE** fazer deploy apenas para o target `app`
6. Coleções Firestore deste módulo usam prefixo `bulle_*`

## 💾 BACKUP

Antes de grandes mudanças, salvar versão em:
`3_LAUSANNE_ARQUIVO_HISTORICO/REPOSITORIO_DE_RESTAURACAO_E_VERSOES/`

---
*Protocolo v1.0 | CES Ecossistema Digital*
