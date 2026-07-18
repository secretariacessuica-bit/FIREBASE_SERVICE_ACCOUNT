# Cenários de Teste de Aceitação (UAT) – Leads & CRM

Este roteiro descreve as etapas para verificar e homologar os fluxos de captação de leads, segurança, rastreabilidade e conversão no **LIMA Solutions ERP**.

---

## 1. Cenário 1: Submissão de Orçamento com Parâmetros UTM

**Objetivo**: Validar a captação correta de dados e parâmetros de marketing pelo formulário público.

1.  Aceda ao site com parâmetros UTM simulados no URL:
    `http://localhost/index.html?utm_source=google_ads&utm_medium=cpc&utm_campaign=mudancas_verao`
2.  Preencha o formulário **"Demander un Devis Gratuit"**:
    *   **Nome**: *Marie Dupont*
    *   **Email**: *marie.dupont@example.com*
    *   **Téléphone**: *078 999 88 77*
    *   **Départ**: *1000 Lausanne*
    *   **Arrivée**: *1200 Genève*
    *   **Date**: *2026-08-01*
    *   **Volume**: *25*
    *   **Notes**: *Besoin de cartons.*
3.  Submeta o formulário.
4.  **Verificações**:
    *   Aparece um toast de sucesso verde com a mensagem *"Votre demande d'offre a été soumise avec succès."*
    *   O formulário é reiniciado automaticamente.

---

## 2. Cenário 2: Proteção Anti-Spam (Honeypot)

**Objetivo**: Assegurar que robôs automáticos de spam sejam ignorados sem perturbar a base de dados.

1.  Aceda ao site com as ferramentas do programador abertas (F12).
2.  Remova a propriedade `display: none` do campo invisível `<input type="text" name="fax_number_alt">`.
3.  Preencha este campo com qualquer texto (ex: *spambot_test*).
4.  Preencha os restantes campos obrigatórios e submeta o formulário.
5.  **Verificações**:
    *   Aparece a mensagem de sucesso falsa: *"Votre demande d'offre a été soumise avec succès (honeypot)."*
    *   Verifique na base de dados (tabela `crm_leads`) ou no painel: **NÃO** deve ter sido criado nenhum registo de lead para esta submissão.

---

## 3. Cenário 3: Rate Limiting por IP

**Objetivo**: Evitar ataques de negação de serviço (DDoS) ou inundação de leads falsas.

1.  Tente submeter o formulário público sucessivamente mais de 5 vezes no espaço de 1 minuto a partir do mesmo IP.
2.  **Verificações**:
    *   Na 6ª tentativa, a submissão é bloqueada.
    *   O sistema retorna o status HTTP `429 Too Many Requests` e exibe o toast vermelho de erro: *"Trop de requêtes soumises. Veuillez réessayer plus tard."*

---

## 4. Cenário 4: Pipeline Kanban no Painel Admin

**Objetivo**: Validar a visualização e arrastamento/modificação de status do lead.

1.  Faça login no painel administrativo (`/admin/login.php`).
2.  No menu esquerdo ou no widget do Dashboard, clique em **"Pipeline Leads"**.
3.  **Verificações**:
    *   O prospecto *Marie Dupont* deve constar na coluna **"Nouveau / Novo"** com contagem correta no badge.
    *   Clique no cartão de *Marie Dupont* para abrir a janela modal de detalhes.
    *   Valide se todos os dados inseridos (incluindo UTMs) estão corretamente mapeados.
    *   Altere o status no dropdown do rodapé para **"En contact / Em contacto"**.
    *   Feche o modal e verifique se o cartão mudou automaticamente de coluna no pipeline Kanban.

---

## 5. Cenário 5: Conversão em Cliente com Deteção de Duplicados

**Objetivo**: Validar o fluxo de fecho de negócio e prevenção de duplicações na base de clientes.

### Caso A: Novo Cliente
1.  No modal de *Marie Dupont*, clique em **"Convertir en Cliente"**. Confirme no popup.
2.  **Verificações**:
    *   Aparece a mensagem de sucesso *"Lead converti en nouveau client avec succès."*
    *   No painel Kanban, o cartão do lead passa para a coluna **"Gagné / Ganho"** e o botão de conversão fica desativado.
    *   Aceda a **"Clientes"** no menu esquerdo e verifique se a ficha de *Marie Dupont* existe com um novo código sequencial (ex: `CLI-000001`).

### Caso B: Cliente Duplicado Existente
1.  Submeta uma nova lead com o e-mail: *marie.dupont@example.com*.
2.  Aceda ao modal deste novo lead no pipeline.
3.  Clique em **"Convertir en Cliente"** e confirme.
4.  **Verificações**:
    *   O sistema deteta o e-mail duplicado.
    *   Aparece a mensagem de sucesso *"Lead associado ao cliente duplicado existente."*
    *   Não é criada uma nova linha de cliente na tabela `clients` (o contador de clientes não muda).
    *   O lead é atualizado para `Won` (Gagné) e passa a apontar para o ID do cliente já existente.

---

## 6. Cenário 6: Auditoria de E-mails Simulados

**Objetivo**: Validar o fluxo de notificações sem envio SMTP real.

1.  Aceda ao servidor via FTP ou gestor de ficheiros.
2.  Abra o ficheiro `/sites/private_lima/logs/emails.log` (ou `/private/logs/emails.log`).
3.  **Verificações**:
    *   Verifique se existem dois blocos de e-mail registados para a submissão de *Marie Dupont*:
        *   Um e-mail enviado para `marie.dupont@example.com` (boas-vindas).
        *   Um e-mail enviado para o endereço administrativo da empresa (alerta de nova lead).
    *   Verifique se os dados da tabela `simulated_emails` no banco de dados contêm exatamente os mesmos registos de e-mail.
