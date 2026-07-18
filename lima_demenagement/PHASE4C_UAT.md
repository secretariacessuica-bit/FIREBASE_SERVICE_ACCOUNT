# Relatório de Testes UAT — Etapa 4C: Páginas Institucionais Complementares

Este relatório resume os resultados dos testes de aceitação do utilizador (UAT) para a **Etapa 4C** da Lima Déménagement.

---

## 1. UAT Automatizado

O conjunto de testes UAT automatizado em `public_site/db/run_uat_tests.php` foi executado diretamente no ambiente de produção (Infomaniak), validando as integrações e regras de negócio:

```text
=== LIMA solutions ERP - Automated UAT Test Suite ===

Test 1: Criando Lead de Teste e enviando e-mails de criação... [OK] ID: 7
Test 2: Alterando Status da Lead e enviando status change... [OK]
Test 3: Convertendo Lead para Novo Cliente e enviando e-mails... [OK] Cliente criado com código: CLI-000005
Test 4: Validando Deteção de Duplicados e associação... [OK] Duplicado detetado e associado ao cliente ID 6 corretamente.
Test 5: Validando Estado dos Leads pós-conversão... [OK]
Test 6: Validando Integridade do Conteúdo dos E-mails e Ficheiro de Log...
   [OK] Nenhum placeholder cru encontrado no assunto ou corpo de nenhum e-mail simulado.
   [OK] Links Google Maps gerados e codificados corretamente no alerta interno.
   [OK] O ficheiro emails.log contém todos os blocos estruturados de teste UAT.
Limpando massa de dados de teste... [OK]

=== RESULTADO FINAL: TUDO APROVADO [PASSED] ===
```

---

## 2. UAT Manual (Fluxo Ponta a Ponta)

### Cenário 1: Navegação e Otimização SEO
*   **Ações:** Aceder às páginas `faq.html` e `contact.html`.
*   **Resultados:**
    *   Ambas as páginas carregam instantaneamente com layout responsivo e navegação funcional.
    *   Títulos únicos e meta descriptions verificados na tag `<head>`.
    *   Verificado a presença de exatamente um elemento `<h1>` por página.
    *   Interface de acordeão da FAQ funciona de forma interativa por meio de cliques.

### Cenário 2: Captação de Lead com Tracking UTM
*   **Ações:** Aceder ao site através da URL:
    `https://limasolutions.ch/index.html?utm_source=google_ads&utm_medium=cpc&utm_campaign=mudancas_2026`
    Navegar pelas páginas e enviar o formulário em `contact.html`.
*   **Resultados:**
    *   O lead foi guardado corretamente na tabela `crm_leads`.
    *   Parâmetros `utm_source`, `utm_medium` e `utm_campaign` persistidos corretamente no banco.
    *   Os e-mails simulados de confirmação do lead e de alerta de equipe comercial foram guardados no banco de dados e adicionados ao log local em `/private_lima/logs/emails.log`.
