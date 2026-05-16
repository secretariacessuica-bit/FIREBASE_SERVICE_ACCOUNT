# 🛡️ CONSTITUIÇÃO DO SISTEMA: CATEDRAL LAUSANNE

Este documento é a autoridade suprema sobre este diretório. Qualquer modificação deve respeitar estas cláusulas pétreas.

## 1. SOBERANIA VISUAL (LAUSANNE IDENTITY)
* **CLÁUSULA OBRIGATÓRIA**: O sistema DEVE manter o "White & Gold Premium Theme".
* **PROIBIÇÃO**: É terminantemente proibido o uso de fundos escuros (Dark Mode), gradientes pretos ou o codinome "Diamond Phoenix".
* **LOGO**: O logo oficial é `assets/logo.png`, que deve estar sempre em destaque circular com moldura dourada.

## 2. ISOLAMENTO DE DADOS (ANTI-CONTAMINAÇÃO)
* **DATABASE**: O `projectId` deve ser sempre `catedral-connect-bf717`.
* **PREFIXO**: O `DB_PREFIX` deve ser sempre vazio `''`. Lausanne opera na raiz do banco.
* **EMAILS**: O `auth-manager.js` deve usar exclusivamente contas `@catedral.ch`.

## 3. SEGURANÇA DE DEPLOY
* **PROIBIÇÃO DE CÓPIA**: Nenhum arquivo da pasta `ia_ces_bulle` pode ser copiado ou adaptado para este diretório sem filtragem total de branding.
* **HOSTING**: O deploy deve ser exclusivo para o target `admin-lausanne`.

---
*Assinado: Antigravity 2.4 - Protocolo de Proteção de Ativos*
*Data da Última Auditoria: 09/05/2026*
