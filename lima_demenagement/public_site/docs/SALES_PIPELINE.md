# CRM Pipeline Comercial – Guia de Processos

Este documento estabelece o fluxo de trabalho e as regras de negócio para a gestão de leads e conversão de negócios no **LIMA Solutions ERP**.

---

## 1. Os Estágios do Pipeline de Vendas

O pipeline é visualizado em colunas Kanban na interface administrativa e reflete a jornada de qualificação comercial da lead:

| Estágio Interno | Nome Exibido | Descrição e Ações |
| :--- | :--- | :--- |
| `New` | Nouveau / Novo | Leads recém-criadas a partir do formulário público. Requer primeira análise da equipa. |
| `Contacted` | En contact / Em contacto | Primeiro contacto efetuado com o cliente (por e-mail, telefone ou WhatsApp). |
| `Visit Scheduled` | Visite planifiée / Visita marcada| Agendamento de visita técnica para estimativa física de volumes e acessos da mudança. |
| `Proposal Sent` | Proposition envoyée / Proposta | Envio do Orçamento formalizado (`Devis`) a partir do painel administrativo. |
| `Negotiation` | Négociation / Negociação | Discussão de valores, ajustes de inventário ou negociação de datas com o cliente. |
| `Won` | Gagné / Ganho | Negócio fechado. Lead convertida em Cliente e associada ao projeto. |
| `Lost` | Perdu / Perdido | Proposta rejeitada ou lead sem interesse. Fim do fluxo de qualificação. |

---

## 2. Regras de Conversão (Lead ➔ Cliente)

Ao qualificar positivamente uma lead, o utilizador pode aceder aos detalhes e clicar em **"Convertir en Cliente"**. Esta operação realiza o seguinte fluxo transacional na base de dados:

1.  **Deteção de Duplicados**: O sistema verifica se o e-mail ou o telefone do lead já estão registados como um cliente ativo na base de dados.
    *   **Se Duplicado**: A lead é associada diretamente a este cliente existente (`converted_client_id`). O sistema informa o utilizador e evita a criação de perfis duplicados no CRM.
    *   **Se Único**: É gerado um novo código de cliente sequencial (ex: `CLI-000045`) de forma segura contra concorrência e o dossier do cliente é criado com os dados da lead.
2.  **Imutabilidade do Lead**: Os metadados de marketing do lead (como UTMs, data de origem) permanecem inalterados para fins de auditoria e BI.
3.  **Fecho do Pipeline**: O status do lead é automaticamente atualizado para `Won` (Gagné).

---

## 3. Rastreabilidade de Origem (Atribuição de Canais)

Para apoiar as futuras decisões de investimento em marketing, cada lead capturada armazena:
*   **utm_source**: Identifica a origem do tráfego (ex: `google`, `facebook`, `newsletter`).
*   **utm_medium**: Identifica o tipo de canal (ex: `cpc` para Google Ads, `organic` para busca, `email`).
*   **utm_campaign**: Nome da campanha promocional ativa que levou à conversão.
*   **referer_url**: O URL da página externa que encaminhou o visitante para o site da Lima.

Estes dados são exibidos de forma clara na ficha de detalhes do lead, permitindo calcular o Custo de Aquisição de Clientes (CAC) e o retorno sobre o investimento publicitário.
