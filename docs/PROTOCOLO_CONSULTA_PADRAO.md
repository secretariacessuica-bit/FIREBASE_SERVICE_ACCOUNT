# 📋 PROTOCOLO PADRÃO DE CONSULTA — ECOSSISTEMA DIGITAL CES
**Versão:** 1.3 | **Última atualização:** 29/05/2026 | **Mantido por:** Antigravity

> ⚠️ Este documento é a FONTE DA VERDADE sobre o ecossistema. Consultar SEMPRE antes de qualquer sessão de trabalho.

---

## 🏛️ ESTRUTURA ORGANIZACIONAL

```
CES — Catedral da Esperança (organização-mãe)
├── Centenas de filiais ao redor do mundo
├── 📍 Lausanne (filial — projeto PIONEIRO)
│   ├── App Lausanne Admin       [Módulo 4]  ✅ Produção
│   └── App ES Diaconia          [Módulo 5]  🧪 Em testes (Lausanne)
├── 📍 Bulle (filial — expandida a partir de Lausanne)
│   ├── Site Institucional       [Módulo 1]  ✅ Produção → cesbulle.ch
│   └── App Bulle Operacional    [Módulo 2]  ✅ Produção
├── 💾 Arquivo Histórico/Backup  [Módulo 3]  ← backup de QUALQUER app do ecossistema
└── 🔮 Futuras filiais (potencial expansão do ecossistema)
```

### Contexto histórico
- O projeto nasceu em **Lausanne**, depois se expandiu para **Bulle**
- **Lausanne** está focada em **2 apps de trabalho interno** (sem site público)
- **Bulle** criou um **site institucional público** + **1 app de trabalho interno**
- A arquitetura foi pensada para escalar para outras filiais da CES no futuro

### Identidade técnica
| Campo | Valor |
|---|---|
| **Organização-mãe** | CES — Catedral da Esperança |
| **Repositório GitHub** | `wandersonrossini-boop/cesbulle-ch` |
| **Raiz local** | `c:\Users\Wande\Documents\ia` |
| **Stack** | HTML5 + CSS3 + JavaScript ES6+ (sem framework) |
| **Backend** | Firebase (Hosting + Firestore) |

---

## 🗂️ MAPA DE MÓDULOS

### 📍 BULLE — MÓDULO 1 — Site Institucional (público)
| Campo | Valor |
|---|---|
| **Pasta** | `1_SITE_CATEDRAL_INSTITUCIONAL/` |
| **Firebase Project** | `catedral-connect-bf717` (projeto DEFAULT) |
| **Hosting Target** | `site` |
| **Deploy** | `firebase deploy --only hosting:site` |
| **Domínio público** | https://cesbulle.ch |
| **Páginas principais** | `index.html`, `obreiros.html` |
| **SEO** | Sitemap + robots.txt + Google Search Console verificado ✅ |
| **Rota especial** | `/connect` redireciona → `catedral-connect-bf717.web.app/connect.html` |

### 📍 BULLE — MÓDULO 2 — App Bulle Operacional
| Campo | Valor |
|---|---|
| **Pasta** | `2_APP_BULLE_OPERACIONAL/` |
| **Firebase Project** | `catedral-connect-bf717` (projeto DEFAULT) |
| **Hosting Target** | `app` |
| **Deploy** | `firebase deploy --only hosting:app` |
| **Tema visual** | Dark / Premium Mobile-First |
| **Páginas principais** | `admin.html`, `recepcao.html`, `altar.html`, `kids.html`, `connect.html`, `portal.html`, `mobile.html` |
| **PWA** | Sim — `manifest.json` + `sw-v4.js` |

### 💾 MÓDULO 3 — Arquivo Histórico / Backup Geral
| Campo | Valor |
|---|---|
| **Pasta** | `3_LAUSANNE_ARQUIVO_HISTORICO/` |
| **Status** | 💾 BACKUP — sem deploy ativo |
| **Escopo** | **Qualquer aplicativo do ecossistema** pode ser salvo aqui |
| **Uso** | Guardar versões antigas, snapshots antes de grandes mudanças, arquivos legados de qualquer módulo |
| **Regra** | Nunca editar daqui direto — é somente leitura/referência |

### 📍 LAUSANNE — MÓDULO 4 — App Admin (interno)
| Campo | Valor |
|---|---|
| **Pasta** | `4_APP_LAUSANNE_ADMIN/` |
| **Firebase Project** | `catedral-connect-6c55e` (projeto LAUSANNE) |
| **Hosting Target** | `admin-lausanne` |
| **Deploy** | `firebase deploy --only hosting:admin-lausanne` |
| **DB Prefix** | `''` (raiz do banco — Lausanne opera sem prefixo) |
| **Auth** | Contas `@catedral.ch` exclusivamente |
| **Tema visual** | ⚪🏅 White & Gold Premium Theme — NUNCA Dark Mode |
| **Logo** | `assets/logo.png` com moldura dourada circular |
| **Páginas principais** | `admin.html`, `recepcao_v2.html`, `gabinete.html`, `visitante.html`, `integracao.html`, `checkin.html`, `followup.html`, `altar_final.html`, `acolhimento.html`, `connect.html`, `kids.html` |

### 🧪 LAUSANNE — MÓDULO 5 — App ES Diaconia (híbrido/transição)
| Campo | Valor |
|---|---|
| **Pasta** | `5_APP_ES_DIACONIA/` |
| **Status** | 🟢 **CONCLUÍDO COM SUCESSO / HOMOLOGADO EM PRODUÇÃO** |
| **Firebase Project** | `diaconia-a38f1` (Produção), `ces-diaconia-dev` (Dev), `catedral-connect-267b2` (Legado) |
| **Hosting Target** | `diaconia` |
| **Deploy** | `firebase deploy --only hosting:diaconia --project diaconia-prod` |
| **Páginas principais** | `index.html` |
| **Push Notifications** | Sim — `sw-notifications.js` |
| **Nota** | Servido de forma híbrida: hospedado no domínio legado, conectando no Firestore de Produção Novo |

---

## 🔥 FIREBASE — PROJETOS E AMBIENTES

| Alias | Project ID | Módulos |
|---|---|---|
| `default` | `catedral-connect-bf717` | Módulo 1 (Site Bulle) + Módulo 2 (App Bulle) |
| `lausanne` | `catedral-connect-6c55e` | Módulo 4 (App Lausanne Admin) |
| `diaconia-prod` | `diaconia-a38f1` | Módulo 5 (App Diaconia - Novo Produção) |
| `diaconia-dev` | `ces-diaconia-dev` | Módulo 5 (App Diaconia - Desenvolvimento) |
| `diaconia-project` | `catedral-connect-267b2` | Módulo 5 (App Diaconia - Legado Transição) |

> ⚠️ **CRÍTICO**: Cada módulo usa seu próprio Firebase project. Nunca misturar dados entre projetos.

---

## 🚀 COMANDOS DE DEPLOY

```bash
# Site institucional (cesbulle.ch)
firebase deploy --only hosting:site

# App operacional Bulle
firebase deploy --only hosting:app

# App Admin Lausanne
firebase deploy --only hosting:admin-lausanne

# App Diaconia
firebase deploy --only hosting:diaconia

# Regras do Firestore (afeta o projeto DEFAULT)
firebase deploy --only firestore:rules

# Deploy completo (CUIDADO — todos os módulos)
firebase deploy
```

---

## 🔒 REGRAS ABSOLUTAS (ANTI-CONTAMINAÇÃO)

1. **NUNCA** misturar arquivos entre módulos sem filtrar branding/lógica
2. **NUNCA** aplicar Dark Mode no Módulo 4 (Lausanne — White & Gold é inviolável)
3. **NUNCA** usar o `projectId` de Lausanne (`catedral-connect-6c55e`) em código do Bulle e vice-versa
4. **NUNCA** copiar `admin.html` ou `recepcao.html` entre Módulo 1 e Módulo 4 diretamente
5. **SEMPRE** confirmar em qual módulo se está trabalhando antes de editar
6. **SEMPRE** fazer `firebase use <alias>` antes de deploy se mudou de projeto

---

## 🗃️ FIRESTORE — COLEÇÕES E PREFIXOS

| Prefixo/Coleção | Projeto | Acesso |
|---|---|---|
| `bulle_*` | `catedral-connect-bf717` | Liberado (nuclear thaw) |
| `diaconia_*` (Legado) / limpas (Novo) | `catedral-connect-267b2` (Legado) / `diaconia-a38f1` (Novo) | Liberado / Base limpa |
| `people`, `kids`, `integracao`, `decisions` | `catedral-connect-6c55e` | Role-based (Lausanne) |
| `users`, `volunteers`, `attendance`, `leaders`, `birthdays` | root | Aberto (diagnóstico) |

---

## 📐 IDENTIDADE VISUAL POR MÓDULO

| Módulo | Tema | Observação |
|---|---|---|
| Site Bulle (1) | Institucional leve | Compatível com identidade pública |
| App Bulle (2) | Dark Premium / Mobile-First | Gradientes escuros, cores vibrantes |
| App Lausanne (4) | ⚪🏅 White & Gold | CLÁUSULA PÉTREA — proibido alterar |
| App Diaconia (5) | 🧪 Em definição | Em testes em Lausanne — confirmar antes de qualquer mudança visual |

---

## 📚 DOCUMENTOS DE REFERÊNCIA

| Documento | Localização |
|---|---|
| Constituição do Sistema Lausanne | `4_APP_LAUSANNE_ADMIN/IDENTIDADE_SISTEMA.md` |
| Protocolo de Segurança App | `docs/PROTOCOLO_SEGURANCA_APP.md` |
| Protocolo de Segurança SFP | `docs/PROTOCOLO_SEGURANCA_SFP.md` |
| Relatório de Estabilização Mai/2026 | `docs/RELATORIO_ESTABILIZACAO_MAIO_2026.md` |
| Este protocolo | `docs/PROTOCOLO_CONSULTA_PADRAO.md` |

---

## ✅ CHECKLIST DE INÍCIO DE SESSÃO

Antes de qualquer trabalho, confirmar:

- [ ] Qual módulo será trabalhado hoje?
- [ ] O Firebase está apontando para o projeto correto? (`firebase use`)
- [ ] O arquivo a editar pertence exclusivamente àquele módulo?
- [ ] A identidade visual do módulo está sendo respeitada?
- [ ] O deploy será apenas para o target correto?

---

*Mantido por: Antigravity | Protocolo gerado em: 29/05/2026*
