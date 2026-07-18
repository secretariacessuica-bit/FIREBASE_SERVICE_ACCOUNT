# LIMA Solutions ERP — Central de Templates de E-mails Automáticos

Este documento detalha os templates de e-mail implementados para a comunicação automatizada (em modo simulado) do ecossistema digital da **Lima Déménagement**.

---

## 1. Diretrizes de Design e Identidade Visual

Todos os e-mails HTML seguem uma estrutura visual unificada e responsiva, alinhada com a identidade de marca do ERP e do Site Institucional:

*   **Tipografia:** Fonte `Inter` (com fallbacks `Helvetica, Arial, sans-serif`).
*   **Esquema de Cores:**
    *   `Primary Teal` (Teal corporativo): `#007a87` (usado em bordas de destaque, títulos importantes, badges de novos estados e botões de chamada para ação).
    *   `Primary Teal Light` (Fundo de destaque): `#f4f9fa` (usado em caixas de resumo e tabelas de informações).
    *   `Text Dark` (Texto principal): `#333333`
    *   `Background Light` (Fundo do e-mail): `#f4f9fa`
    *   `Card Background` (Corpo principal): `#ffffff` (com cantos arredondados `6px` e sombra suave `0 4px 12px rgba(0,0,0,0.08)`).
*   **Estrutura do Layout:**
    *   **Header:** Logotipo em texto estilizado: `Lima Déménagement`.
    *   **Content:** Corpo da mensagem com padding adequado (`30px 20px`).
    *   **Footer:** Assinatura do ERP com aviso legal de que se trata de uma simulação funcional e a data atual dinamicamente gerada.

---

## 2. Catálogo de Templates e Placeholders

### 2.1. Confirmation de Devis (`lead_confirmation`)
*   **Destinatário:** Cliente (Lead)
*   **Assunto:** `[LIMA Déménagement] Confirmation de votre demande de devis`
*   **Idioma:** Francês (FR)
*   **Descrição:** Enviado imediatamente após o preenchimento do formulário público de orçamento.
*   **Placeholders:**
    *   `{name}`: Nome completo do lead.
    *   `{email}`: Endereço de e-mail do lead.
    *   `{phone}`: Telefone para contacto.
    *   `{service_date}`: Data desejada para o serviço de mudança.
    *   `{volume_m3}`: Volume estimado em metros cúbicos (\(m^3\)).
    *   `{origin_address}`: Endereço completo de partida.
    *   `{destination_address}`: Endereço completo de chegada.

---

### 2.2. Alerte Interne Lead (`internal_lead_alert`)
*   **Destinatário:** Equipa comercial / Administrativa (`info@limasolutions.ch`)
*   **Assunto:** `[CRM Alerte] Nouvelle lead commerciale reçue - {name}`
*   **Idioma:** Francês (FR)
*   **Descrição:** Alerta instantâneo para a equipa sobre a entrada de uma nova oportunidade comercial com rastreabilidade completa.
*   **Placeholders:**
    *   `{lead_id}`: ID autoincremental gerado no banco de dados.
    *   `{name}`: Nome do lead.
    *   `{email}`: E-mail para contacto rápido.
    *   `{phone}`: Telefone.
    *   `{service_date}`: Data desejada.
    *   `{volume_m3}`: Volume (\(m^3\)).
    *   `{origin_address}`: Endereço de partida em formato texto.
    *   `{origin_maps_link}`: Link do Google Maps para a morada de partida.
    *   `{destination_address}`: Endereço de chegada em formato texto.
    *   `{destination_maps_link}`: Link do Google Maps para a morada de chegada.
    *   `{notes}`: Observações de texto livre.
    *   `{utm_source}`, `{utm_medium}`, `{utm_campaign}`: Dados de rastreabilidade de campanha (Google Ads, Facebook Ads, etc.).
    *   `{referer_url}`: URL de proveniência do visitante.
    *   `{ip_address}`: Endereço IP do visitante.

> [!TIP]
> Os links do Google Maps (`origin_maps_link` e `destination_maps_link`) são injetados automaticamente no corpo HTML do e-mail simulado de acordo com o formato: `https://www.google.com/maps/search/?api=1&query={encoded_address}`.

---

### 2.3. Bienvenue Client (`client_welcome`)
*   **Destinatário:** Cliente convertido
*   **Assunto:** `[LIMA Déménagement] Bienvenue chez nous - Création de votre compte client`
*   **Idioma:** Francês (FR)
*   **Descrição:** Mensagem de boas-vindas contendo o código de identificação do cliente gerado pelo ERP.
*   **Placeholders:**
    *   `{name}`: Nome do cliente.
    *   `{customer_code}`: Código exclusivo do cliente gerado pelo sequenciador interno (ex: `CLI-00045`).
    *   `{company_name}`: Nome da empresa emitente (Padrão: `Lima Déménagement`).

---

### 2.4. Alerte de Conversion (`internal_conversion_alert`)
*   **Destinatário:** Equipa comercial / Administrativa (`info@limasolutions.ch`)
*   **Assunto:** `[CRM Conversion] Lead convertie en client avec succès - {lead_name}`
*   **Idioma:** Francês (FR)
*   **Descrição:** Notifica a equipa de que um lead foi convertido num cliente.
*   **Placeholders:**
    *   `{lead_name}`: Nome do lead original.
    *   `{lead_email}`: E-mail do lead original.
    *   `{customer_code}`: Código do cliente gerado.
    *   `{client_id}`: ID do registo na tabela `clients`.
    *   `{is_duplicate}`: Flag indicativa de duplicação ("Client existant (Doublon associé)" ou "Nouveau dossier client unique").
    *   `{converted_by_user_id}`: ID do utilizador administrativo que efetuou a conversão.

---

### 2.5. Changement de Statut Pipeline (`pipeline_status_change`)
*   **Destinatário:** Equipa comercial / Administrativa (`info@limasolutions.ch`)
*   **Assunto:** `[CRM Pipeline] Mise à jour du statut du lead - {lead_name}`
*   **Idioma:** Francês (FR)
*   **Descrição:** Disparado apenas quando um lead muda efetivamente de estado no pipeline comercial.
*   **Placeholders:**
    *   `{lead_id}`: ID do lead.
    *   `{lead_name}`: Nome do lead.
    *   `{old_status}`: Estado anterior no pipeline.
    *   `{new_status}`: Novo estado no pipeline.

---

## 3. Prevenção de Placeholders Crus

Para garantir a qualidade e auditoria das mensagens geradas, o renderizador executa uma sanitização obrigatória final:
*   Qualquer placeholder dinâmico cujo valor correspondente seja nulo ou vazio é substituído por um hífen (`-`).
*   Qualquer placeholder em formato de chaves `{placeholder}` não preenchido é eliminado e substituído por `-` usando expressões regulares antes do envio simulado.
