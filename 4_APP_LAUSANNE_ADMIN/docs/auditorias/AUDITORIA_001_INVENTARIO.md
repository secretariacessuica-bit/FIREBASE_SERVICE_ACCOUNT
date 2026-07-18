# 📋 INVENTÁRIO DE PROJETO: Módulo 4 (App Lausanne Admin)

**Modo:** Auditoria Passiva
**Data da Auditoria:** 06 de Julho de 2026
**Objetivo:** Mapeamento completo para base de refatoração futura.

---

## 1. Estrutura Completa de Pastas

*   **`4_APP_LAUSANNE_ADMIN/` (Raiz)**
    *   Arquivos principais do aplicativo (cerca de 19 arquivos, incluindo páginas HTML e documentações Markdown).
*   **`assets/`**
    *   Imagens, logotipos e vídeos (6 arquivos).
*   **`css/`**
    *   Folhas de estilo padronizadas (3 arquivos).
*   **`js/`**
    *   Lógicas globais de negócio e configurações (5 arquivos).

---

## 2. Principais Páginas HTML

| Arquivo | Função Principal | Dependências CSS/JS Locais |
| :--- | :--- | :--- |
| `index.html` | Portal de entrada e "Casca" (Shell) de navegação em iframes. | `firebase-config.js`, `auth-manager.js` |
| `admin.html` | Painel central do Secretário/Líder. Gere banco de dados, relatórios, configurações e permissões de usuários. | `firebase-config.js`, `auth-manager.js`, `reception_kids_logic.js`, `style-v2.css` |
| `recepcao_v2.html` | App para totens de entrada, check-in, cadastro rápido. | `firebase-config.js`, `auth-manager.js`, `reception_kids_logic.js` |
| `integracao.html` | App do CRM para liderança da Integração (contatos, jornada, resgates). | `firebase-config.js`, `auth-manager.js`, `reception_kids_logic.js` |
| `gabinete.html` | App pastoral para aconselhamentos e relatórios sigilosos. | `firebase-config.js`, `auth-manager.js`, `reception_kids_logic.js` |
| `visitante.html` | Formulário externo público/interno para auto-cadastro. | `firebase-config.js`, `reception_kids_logic.js` |
| `checkin.html` | Painel simplificado para eventos pontuais. | `firebase-config.js`, `auth-manager.js` |
| `altar_final.html` | Painel de monitoramento do telão (comunicação em tempo real). | `firebase-config.js`, `auth-manager.js` |
| `ferramenta_senhas_lausanne.html` | Utilitário isolado para reset/criação em lote de contas no Firebase Auth. | `firebase-config.js` |
| `kids.html` / `acolhimento.html` / `connect.html` | Módulos setorizados para controle de crianças, voluntários e conexão. | (Variáveis locais) |

---

## 3. Arquivos JavaScript (`js/`)

| Arquivo | Finalidade | Tamanho Aprox. | Observações |
| :--- | :--- | :--- | :--- |
| `reception_kids_logic.js` | Engine principal de check-in, criação de perfil, regras de crianças e promoções de status. | ~60 KB | Arquivo denso, concentra regras de negócio muito complexas. |
| `auth-manager.js` | Gerencia o login de sessão, verificação de e-mails (`@catedral.ch`) e autorização de iframes. | ~9 KB | Roteia a interface baseado no tipo de login. |
| `firebase-config.js` | Inicialização do App do Firebase e desativação de persistência offline. | ~3 KB | Possui *hardcodes* do `catedral-connect-6c55e`. |
| `firebase-config.bulle.js` | Backup/Referência do projeto legível do Bulle. | ~2.6 KB | Arquivo morto no ecossistema Lausanne. |

> **Nota Crítica sobre JS Embutido:** A maior parte da lógica da aplicação (Milhares de linhas) não está na pasta `js/`, mas sim escrita diretamente dentro das tags `<script>` nos arquivos HTML (principalmente no `admin.html`).

---

## 4. Arquivos CSS (`css/`)

| Arquivo | Tipo | Descrição / Duplicação |
| :--- | :--- | :--- |
| `style-v2.css` | Interno | CSS principal (~89 KB). Gigantesco. Contém estilos de botões, tabelas, modais. Possui repetições legadas de classes de versões "v1". |
| `admin-v2.css` | Interno | Estilos exclusivos para tabelas, painéis do Secretário e gráficos. |
| `mobile-v2.css` | Interno | *Media queries* específicas para adaptar visões administrativas para celulares e tablets. |

> **Nota:** Há centenas de estilos definidos diretamente na linha (`style="color: red"`) dentro das tags HTML (CSS *inline*), dificultando temas globais.

---

## 5. Assets (Imagens e Mídias)

*   `logo_3.png` (~1.3 MB) — Logo predominante, circular com fundo e letras.
*   `logo.png` / `logo_lausanne_v70.png` / `logo 2.png` (~2.1 MB) — Possíveis duplicações do mesmo arquivo pesado de logotipo original.
*   `logo_catedral_3d.mp4` (~23.3 MB) — Vídeo em loop provavelmente usado na tela de check-in ou standby.

---

## 6. Firebase (Backend)

*   **Configuração Encontrada:** Projeto `catedral-connect-6c55e`.
*   **Autenticação:** Baseada em e-mail e senha. Permite apenas persistência em Memória/Sessão. A aplicação em si força validações no frontend para só permitir `@catedral.ch`.
*   **Firestore:** Banco principal. Opera nas coleções raiz: `people`, `attendance`, `leaders`, `pending`, `schedule_requests`. As regras de segurança limitam a manipulação mas muita da lógica de acesso e categorização é confiada ao frontend (client-side).
*   **Storage / Functions:** Não foram encontradas evidências de uso de Storage (arquivos/fotos) ou Serverless Functions (Cloud Functions) ativos na lógica front-end base deste diretório.

---

## 7. Bibliotecas Externas Carregadas (via CDN)

1.  **Firebase SDK (v8.10.1):** `firebase-app.js`, `firebase-auth.js`, `firebase-firestore.js`. (Versão defasada, a atual da Google é v10+).
2.  **FontAwesome:** Ícones vetoriais.
3.  **QRCode.js:** Geração de códigos QR para credenciais de crianças e membros.
4.  **Chart.js:** Utilizada no `admin.html` para exibição dos gráficos analíticos.
5.  **Google Fonts:** Utilização das fontes "Inter", "Outfit", e "Playfair Display".

---

## 8. Resultado Final e Classificação

### 📊 Diagnóstico de Engenharia

| Métrica | Status | Análise |
| :--- | :--- | :--- |
| **Arquitetura** | Monolítica / Vanilla | O projeto não usa frameworks (React/Vue/Angular) ou empacotadores (Vite/Webpack). Depende de injetar *iframes* e scripts seriais, o que onera a memória do navegador. |
| **Organização** | Regular | Existe separação de `js` e `css`, mas a vasta maioria das lógicas cruciais de rotas, banco de dados e interface estão acopladas diretamente dentro dos arquivos HTML gigantes (ex: `admin.html` com ~7.000 linhas). |
| **Legibilidade** | Baixa a Média | Por não estar componentizado (quebrado em arquivos menores), debugar erros ou fazer leituras rápidas exige profunda familiaridade com o arquivo inteiro e buscas longas (`Ctrl+F`). |
| **Escalabilidade** | Baixa | Muito "Hardcode". Nomes, cores e e-mails estão cravados no meio de funções Javascript. Para clonar o app para outra igreja (White Label), há alto risco de esquecer de alterar uma variável escondida. |
| **Manutenção** | Complexa | Não existem testes automatizados. Atualizar o sistema ou bibliotecas (como migrar o Firebase da versão 8 para a 10) exigiria refatorar manualmente todos os 19 arquivos e testar clicando em cada botão. A técnica do "Cache Buster" via URL (`v=70...`) é propensa a falhas de rede se esquecida. |

**Veredito:** O sistema é um caso clássico de *"Software Artesanal Operacional"*. Ele funciona perfeitamente para o usuário final, é rápido e entrega o valor necessário. Contudo, em termos de engenharia, já ultrapassou o tamanho em que deveria ter sido migrado para uma arquitetura baseada em Componentes (Node.js/React/Vite). Refatorá-lo será uma tarefa de grande porte, mas indispensável se o objetivo for licenciamento comercial ou Multi-Instância.
