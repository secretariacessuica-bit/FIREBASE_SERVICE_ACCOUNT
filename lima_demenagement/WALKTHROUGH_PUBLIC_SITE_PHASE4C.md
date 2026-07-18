# Walkthrough — Etapa 4C: Páginas Institucionais Complementares

Este documento descreve a entrega da **Etapa 4C**, que expande a presença institucional da **Lima Déménagement** e integra o site público ao ecossistema comercial do LIMA Solutions ERP.

---

## 1. Ficheiros Criados e Atualizados

O escopo do projeto foi estruturado com as seguintes entregas em `public_site/`:

*   **[NEW] [faq.html](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/faq.html):** Página de perguntas frequentes implementando acordeões interativos nativos (`<details>` e `<summary>`) de acordo com as especificações de design.
*   **[NEW] [contact.html](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/contact.html):** Página de contactos integrando o formulário Request Quote e informações corporativas da Lima.
*   **[MODIFY] [style.css](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/style.css):** Inclui classes adicionais para suporte a acordeões de FAQ, grelhas de cobertura, cartões de serviço e layouts responsivos para dispositivos móveis.
*   **[MODIFY] [index.html](file:///c:/Users/Wande/Documents/ia/lima_demenagement/public_site/index.html):** Header de navegação unificado e links atualizados apontando para as novas subpáginas.

---

## 2. Integrações Obrigatórias Validadas

*   **CRM Leads & API Leads:** O formulário da página `contact.html` envia os dados via AJAX (via `app.js`) para a API unificada em `POST /api/v1/leads/leads.php`.
*   **UTM Tracking & Referer:** Persistência automática em `sessionStorage` através de qualquer página institucional. Quando o utilizador acede a `contact.html`, as UTMs e o URL de referer são injetados de forma invisível no formulário.
*   **Honeypot Anti-Spam:** Proteção integrada com o campo oculto `fax_number_alt`. Se preenchido por robôs, a submissão é aceite falsamente mas descartada no backend para evitar lixo no CRM.
*   **Simulated Emails:** Notificações automáticas de lead gerado registadas com sucesso na tabela `simulated_emails` e no ficheiro físico de auditoria `/private_lima/logs/emails.log`.

---

## 3. SEO e Melhores Práticas

*   **SEO semântico:** Uso exclusivo de tags semânticas do HTML5 (`<header>`, `<nav>`, `<main>`, `<section>`, `<footer>`).
*   **Estrutura de Heading:** Exatamente um título `<h1>` único por página.
*   **Metadados Únicos:** Cada página possui tags `<title>` e `<meta name="description">` exclusivas e ricas em termos chave locais da Suíça Romande.
