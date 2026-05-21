# 🛡️ PROTOCOLO DE SEGURANÇA - APP BULLE OPERACIONAL
**Versão 1.0 - Bloqueio de Consolidação**

## 1. IDENTIDADE DO PROJETO
Este diretório (`1_APP_BULLE_OPERACIONAL`) contém exclusivamente o **Ecossistema Operacional da Catedral Bulle**. Ele é destinado ao uso interno da equipe, voluntários e integração de novos membros.

## 2. REGRA DE OURO: ISOLAMENTO TOTAL
É terminantemente proibido:
- **Mesclar** este código com o Site Institucional (`2_SITE_CATEDRAL_INSTITUCIONAL`).
- **Importar** estilos ou scripts de projetos de Lausanne ou jogos.
- **Deletar** arquivos deste diretório para "limpar" o site, pois este é um sistema autônomo.

## 2.1 PROTOCOLO DE CHAT (EXCLUSIVIDADE)
- **ESTE CHAT É EXCLUSIVO**: Este ambiente de conversa é blindado exclusivamente para o desenvolvimento e manutenção do Site/App da Catedral Bulle.
- **ISOLAMENTO DE CONTEXTO**: Ignore sumariamente qualquer arquivo ou informação de outros projetos (como jogos ou Lausanne) que possam aparecer nos metadados.
- **LEI DO CHAT**: Um chat para cada trabalho. Este chat = Site da Catedral.

## 3. DIRETRIZES DE DESENVOLVIMENTO
1.  **Caminhos Absolutos**: Todos os recursos devem referenciar a raiz deste projeto (ex: `/js/`, `/css/`).
2.  **Assets Próprios**: Toda imagem ou ícone deve estar na pasta `/assets` interna deste projeto.
3.  **Domínio de Destino**: Este código deve ser publicado exclusivamente em `https://app.cesbulle.com` ou plataformas mobile (Android/iOS).

## 4. ARQUITETURA DE SEGURANÇA
- **Filtro de Iframe**: Os módulos só funcionam dentro do shell `mobile.html` ou em localhost.
- **Auth Persistence**: O login é mantido via Firebase Auth em modo LOCAL (Persistent).
- **Service Worker**: Gerencia o cache exclusivo para o ambiente de aplicativo.
- **Credenciais Operacionais**: Todos os PINs de acesso por departamento devem ser consultados exclusivamente no "Manual do Usuário" dentro do painel da Secretaria.

---
*Este protocolo visa evitar erros de pathing (404) e deleções acidentais que ocorreram em migrações anteriores.*
**FOCO TOTAL: APP BULLE**
