# LIMA Solutions Marketplace MVP — Especificações e Fluxo (MARKETPLACE_MVP)

O **Marketplace LIMA** permite que clientes publiquem móveis usados, seminovos ou doações durante o seu processo de mudança, gerando leads qualificados no CRM e notificando a administração.

---

## 1. Esquema de Banco de Dados (Tabelas)

As tabelas foram geradas através da migração direta executada no servidor:

*   **`marketplace_categories`**: Armazena as categorias fixas (*Móveis Usados*, *Móveis Seminovos*, *Doações*).
*   **`marketplace_items`**: Guarda os anúncios dos clientes com campos para título, descrição, preço (nulo para doações), localização e status de moderação (`Draft`, `Pending`, `Approved`, `Rejected`, `Archived`).
*   **`marketplace_photos`**: Armazena os arquivos de imagens associados aos itens no armazenamento privado.
*   **`marketplace_interests`**: Regista os manifestos de interesse ("Tenho Interesse") submetidos por potenciais compradores.

---

## 2. Endpoints de API Implementados

*   `GET /api/v1/marketplace/items.php` — Devolve a lista de anúncios aprovados no catálogo público (com links de fotos indiretos).
*   `GET /api/v1/marketplace/items.php?scope=my_items` — *(Requer login)* Devolve anúncios publicados pelo cliente autenticado.
*   `POST /api/v1/marketplace/items.php` — *(Requer login)* Cria anúncio com upload de fotos associadas.
*   `PUT /api/v1/marketplace/items.php` — *(Requer login)* Atualiza dados ou altera status (ex: arquivar/reativar).
*   `GET /api/v1/marketplace/items.php?action=download&photo_id=X` — Download seguro de imagem sem expor diretórios físicos.
*   `POST /api/v1/marketplace/interests.php` — Grava manifestação de interesse, notifica o vendedor por email e gera um Lead no CRM.

---

## 3. Segurança e Moderação

*   **Privacidade**: Caminhos físicos de ficheiros de fotos são ocultados através do download autenticado da API.
*   **Controle de Acesso**: Clientes apenas editam e alteram anúncios de sua autoria.
*   **Moderação**: Apenas itens com status `Approved` são listados no catálogo público de oportunidades.

---

## 4. Integração com CRM & Leads

*   Sempre que um comprador clica em **"Tenho Interesse"** e preenche o formulário:
    1. É criado um Lead no CRM com a origem `Marketplace`.
    2. A descrição do Lead inclui o ID e o título do meuble pretendido.
    3. O vendedor original recebe uma notificação por e-mail com a mensagem do comprador.
