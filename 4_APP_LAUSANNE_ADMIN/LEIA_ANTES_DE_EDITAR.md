# ⚠️ LEIA ANTES DE EDITAR — APP LAUSANNE ADMIN [MÓDULO 4]

> Protocolo ativo. Qualquer edição nesta pasta deve respeitar estas regras.
> Protocolo completo: `docs/PROTOCOLO_CONSULTA_PADRAO.md`
> Constituição do sistema: `IDENTIDADE_SISTEMA.md` (LEITURA OBRIGATÓRIA)

---

## 📍 IDENTIDADE DESTE MÓDULO

| Campo | Valor |
|---|---|
| **Filial** | CES Lausanne (projeto PIONEIRO) |
| **Tipo** | App interno administrativo |
| **Firebase Project** | `catedral-connect-6c55e` |
| **Hosting Target** | `admin-lausanne` |
| **Deploy** | `firebase deploy --only hosting:admin-lausanne` |
| **DB Prefix** | `''` (raiz — sem prefixo) |
| **Auth** | Contas `@catedral.ch` exclusivamente |

## 🎨 TEMA VISUAL — CLÁUSULA PÉTREA

| Regra | Detalhe |
|---|---|
| **Tema** | ⚪🏅 White & Gold Premium |
| **PROIBIDO** | Dark Mode, fundos escuros, gradientes pretos |
| **PROIBIDO** | Codinome "Diamond Phoenix" |
| **Logo** | `assets/logo.png` — moldura dourada circular, sempre em destaque |

## 📄 PÁGINAS DESTE MÓDULO

| Arquivo | Função |
|---|---|
| `admin.html` | Painel administrativo principal |
| `recepcao_v2.html` | Módulo de recepção |
| `gabinete.html` | Gabinete pastoral |
| `visitante.html` | Cadastro de visitantes |
| `integracao.html` | Módulo de integração |
| `checkin.html` | Check-in de membros |
| `followup.html` | Acompanhamento pastoral |
| `altar_final.html` | Módulo do altar |
| `acolhimento.html` | Módulo de acolhimento |
| `connect.html` | Formulário de conexão |
| `kids.html` | Módulo infantil |

## 🔒 REGRAS DESTE MÓDULO

1. **NUNCA** usar Dark Mode — White & Gold é inviolável
2. **NUNCA** usar o Project ID do Bulle (`catedral-connect-bf717`) aqui
3. **NUNCA** misturar arquivos com o Módulo 1 (App Bulle) sem filtrar todo o branding
4. **NUNCA** alterar autenticação para aceitar emails fora de `@catedral.ch`
5. **SEMPRE** deploy apenas para o target `admin-lausanne`
6. **SEMPRE** verificar `firebase use lausanne` antes de deploy

## 💾 BACKUP

Antes de grandes mudanças, salvar versão em:
`3_LAUSANNE_ARQUIVO_HISTORICO/REPOSITORIO_DE_RESTAURACAO_E_VERSOES/`

---
*Protocolo v1.0 | CES Ecossistema Digital*
