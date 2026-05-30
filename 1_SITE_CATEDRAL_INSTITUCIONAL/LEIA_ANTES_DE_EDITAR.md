# ⚠️ LEIA ANTES DE EDITAR — SITE INSTITUCIONAL BULLE [MÓDULO 1]

> Protocolo ativo. Qualquer edição nesta pasta deve respeitar estas regras.
> Protocolo completo: `docs/PROTOCOLO_CONSULTA_PADRAO.md`

---

## 📍 IDENTIDADE DESTE MÓDULO

| Campo | Valor |
|---|---|
| **Filial** | CES Bulle |
| **Tipo** | Site público institucional |
| **Domínio** | https://cesbulle.ch |
| **Firebase Project** | `catedral-connect-bf717` |
| **Hosting Target** | `site` |
| **Deploy** | `firebase deploy --only hosting:site` |
| **Tema visual** | Institucional leve / público |

## 📄 PÁGINAS DESTE MÓDULO

| Arquivo | Função |
|---|---|
| `index.html` | Página principal pública |
| `obreiros.html` | Painel do obreiro (senha: `Ces124578`) |

## 🌐 ROTAS ESPECIAIS

| Rota | Comportamento |
|---|---|
| `/connect` | Redireciona → `catedral-connect-bf717.web.app/connect.html` |
| `/obreiros` | Serve `obreiros.html` (cleanUrl ativo) |

## 🔍 SEO — NÃO REMOVER

- `sitemap.xml` — indexação Google
- `robots.txt` — controle de rastreamento
- `google04794a1794ce27a8.html` — verificação Google Search Console ✅

## 🔒 REGRAS DESTE MÓDULO

1. **NÃO** remover arquivos de SEO (`sitemap`, `robots`, verificação Google)
2. **NÃO** alterar o redirect de `/connect` sem atualizar também o `firebase.json`
3. **SEMPRE** manter compatibilidade com domínio `cesbulle.ch`
4. **SEMPRE** testar em mobile — é um site público acessado por qualquer pessoa
5. **SEMPRE** deploy apenas para o target `site`

## 💾 BACKUP

Antes de grandes mudanças, salvar versão em:
`3_LAUSANNE_ARQUIVO_HISTORICO/REPOSITORIO_DE_RESTAURACAO_E_VERSOES/`

---
*Protocolo v1.0 | CES Ecossistema Digital*
