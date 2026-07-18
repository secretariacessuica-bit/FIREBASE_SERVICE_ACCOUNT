# 🏛️ ARQUITETURA.MD — Documento Mestre
**Módulo:** 4 (App Lausanne Admin)
**Organização:** CES — Catedral da Esperança
**Atualizado em:** 06 de Julho de 2026

---

## 1. Visão Geral do Sistema
O "App Lausanne Admin" é o sistema interno de operação e gestão da filial de Lausanne. Ele atende de forma descentralizada a Recepção, Integração, Gabinete Pastoral, Kids, Eventos e Altar, tudo consolidado em uma interface unificada baseada na nuvem.

## 2. Objetivo do Projeto
Fornecer controle absoluto sobre a entrada de visitantes, jornada de membros, segurança infantil e métricas da igreja. O projeto será transformado em um modelo "White Label" (Multi-Instância) futuramente para ser licenciado para outras igrejas.

## 3. Arquitetura Atual
**Monolítica Descentralizada (HTML + CSS + JS Puro).**
O sistema roda inteiramente no cliente (navegador). Não existe um backend Node.js customizado, ele se comunica diretamente com o **Firebase Firestore** e **Firebase Authentication** usando chamadas da SDK v8 no próprio Javascript do navegador. A navegação ocorre por uma casca (`index.html`) que carrega os outros aplicativos através de `iframes`.

## 4. Estrutura de Pastas
```
4_APP_LAUSANNE_ADMIN/
├── assets/          # Logos e mídias
├── css/             # Estilos compartilhados
├── docs/            # Documentação Oficial (Você está aqui)
├── js/              # Lógica core e configurações
└── archive/         # Ferramentas inativas e histórico
```

## 5. Domínios Funcionais
O sistema divide-se logicamente nos seguintes domínios (apesar de ainda estarem acoplados fisicamente no código):
*   **Autenticação & Permissões** (Master, Recepção, Missões, Infantil, etc.)
*   **People Core** (Busca centralizada e perfis de pessoas)
*   **Visitors** (Visitantes e sua jornada)
*   **Membership** (Classificação, evolução e congregação)
*   **Kids** (Segurança infantil)
*   **CRM Pastoral** (Decisões, gabinetes, visitas)
*   **UI Core** (Interface e Interações transversais)

## 6. Fluxo entre módulos
O coração do fluxo é a **Recepção**. Onde o visitante entra, e então passa para a **Integração (CRM)** para acompanhamento. As evoluções de Visitante para Congregado são registradas e refletidas no banco, impactando como a pessoa aparece no **Gabinete Pastoral** ou no **Check-in**.

## 7. Arquivos principais
*   `admin.html` (Onde mora 70% da lógica e visão consolidada)
*   `recepcao_v2.html` (A ponta de lança do cadastro)
*   `integracao.html` (O motor de crescimento)
*   `auth-manager.js` (O cofre de rotas)

## 8. Configuração Firebase
*   **Project ID:** `catedral-connect-6c55e`
*   **Hosting Target:** `admin-lausanne`
*   **Coleções Raiz Principais:** `people`, `attendance`, `leaders`, `pending`, `schedule_requests`, `decisions`.

## 9. Identidade Visual
**⚪🏅 White & Gold Premium Theme**.
*Regra Absoluta:* Proibido o uso de Dark Mode neste módulo, ao contrário do app operacional de Bulle.

## 10. Padrões adotados
*   **Autenticação restrita:** O sistema restringe forçadamente logons apenas para provedores `@catedral.ch`.
*   **Cache Busters:** Uso de controle manual de versão nas strings (`?v=70.3.85`) para forçar o navegador a recarregar scripts.

## 11. Próximos passos
A próxima fase da engenharia foca na **Refatoração Guiada por Evidências**.
1. Extração cirúrgica de funções transversais de UI para diminuir a repetição de código.
2. Formação dos módulos conceituais independentes (`ui.js`, `people.js`, `auth.js`).
3. Preparação do sistema para implantação multi-institucional (White Label).
