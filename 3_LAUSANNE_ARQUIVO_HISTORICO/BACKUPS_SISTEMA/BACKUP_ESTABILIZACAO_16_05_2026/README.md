# Catedral da Esperança Bulle - Ecossistema Digital

Este repositório contém o código-fonte integral do ecossistema digital da Catedral da Esperança em Bulle, Suíça.

## 📁 Estrutura do Projeto
- **`1_APP_BULLE_OPERACIONAL`**: Aplicativo interno (Recepção, Kids, Altar e Secretaria).
- **`2_SITE_CATEDRAL_INSTITUCIONAL`**: Site oficial público ([cesbulle.ch](https://cesbulle.ch)).
- **`3_LAUSANNE_ARQUIVO_HISTORICO`**: Backup e arquivos históricos de Lausanne.
- **`4_APP_LAUSANNE_ADMIN`**: Sistema administrativo da congregação Lausanne.

## 🚀 Tecnologias
- **Frontend:** HTML5, CSS3 (Premium Minimalism), JavaScript (ES6+).
- **Backend/Hosting:** Firebase (Hosting, Firestore, Auth).
- **PWA:** Suporte a modo offline e instalação mobile via Service Workers.

## 🛠️ Como dar manutenção
1. Certifique-se de ter o **Firebase CLI** instalado.
2. Para subir mudanças no site: `firebase deploy --only hosting:site`.
3. Para subir mudanças no app: `firebase deploy --only hosting:app`.

---
© 2026 Catedral da Esperança Bulle | Desenvolvido via Antigravity 2.4 Core
