# Catedral da Esperança Bulle - Ecossistema Digital

Este repositório contém o código-fonte integral do ecossistema digital da Catedral da Esperança em Bulle, Suíça.

## 📁 Estrutura do Projeto
- **`1_SITE_CATEDRAL_INSTITUCIONAL`**: Site oficial público ([cesbulle.ch](https://cesbulle.ch)).
- **`2_APP_BULLE_OPERACIONAL`**: Aplicativo interno (Recepção, Kids, Altar e Secretaria).
- **`3_LAUSANNE_ARQUIVO_HISTORICO`**: Backup e arquivos históricos de Lausanne.
- **`4_APP_LAUSANNE_ADMIN`**: Sistema administrativo da congregação Lausanne.
- **`5_APP_ES_DIACONIA`**: Sistema de controle de escalas e serviços diaconais (autônomo).

## 🚀 Tecnologias
- **Frontend:** HTML5, CSS3 (Premium Mobile-First & Dynamic Themes), JavaScript (ES6+).
- **Backend/Hosting:** Firebase (Hosting, Firestore).

## 🛠️ Como dar manutenção
1. Certifique-se de ter o **Firebase CLI** instalado.
2. Para subir mudanças no site: `firebase deploy --only hosting:site`.
3. Para subir mudanças no app: `firebase deploy --only hosting:app`.
4. Para subir mudanças na Diaconia: `firebase deploy --only hosting:diaconia`.

---
© 2026 Catedral da Esperança Bulle | Desenvolvido via Antigravity 2.4 Core
Última Auditoria Global de Limpeza e Segurança: 03/06/2026

