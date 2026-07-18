# API de Leads V1 – Documentação Técnica

Esta documentação descreve os contratos e o comportamento do endpoint de Leads do **LIMA Solutions ERP**.

---

## 1. Submissão Pública de Leads

Endpoint utilizado para registar pedidos de orçamento provenientes do site público, portal do cliente, app móvel ou integrações externas.

*   **URL**: `/api/v1/leads/leads.php`
*   **Alias de Produção**: `/api/v1/leads` (Reescrito via `.htaccess`)
*   **Método**: `POST`
*   **Autenticação**: Não aplicável (Público)

### Parâmetros da Requisição (JSON Body)

| Campo | Tipo | Obrigatório | Descrição | Limite |
| :--- | :--- | :--- | :--- | :--- |
| `company_id` | INT | Sim | ID da empresa operadora (padrão `1`) | - |
| `name` | String | Sim | Nome completo do prospecto | Max 150 chars |
| `email` | String | Sim | Endereço de e-mail | Max 150 chars, format check |
| `phone` | String | Não | Telefone de contacto | Max 30 chars |
| `origin_address` | String | Sim | Morada de partida da mudança | Max 255 chars |
| `destination_address`| String | Sim | Morada de destino da mudança | Max 255 chars |
| `service_date` | Date | Não | Data prevista da mudança (`YYYY-MM-DD`) | - |
| `volume_m3` | Decimal | Não | Volume estimado em metros cúbicos | - |
| `notes` | String | Não | Descrição dos itens ou inventário | Max 2000 chars |
| `utm_source` | String | Não | Canal de marketing (ex: `google_ads`) | Max 100 chars |
| `utm_medium` | String | Não | Meio de marketing (ex: `cpc`) | Max 100 chars |
| `utm_campaign` | String | Não | Campanha de marketing | Max 100 chars |
| `referer_url` | String | Não | URL de referência original | - |
| `fax_number_alt` | String | Não | **Honeypot**. Se preenchido, a lead é descartada. | - |

### Resposta de Sucesso (`200 OK`)
```json
{
  "success": true,
  "message": "Demande d'offre enregistrée.",
  "id": "42"
}
```

### Respostas de Erro

#### Honeypot Ativado (`200 OK` - Falso Positivo)
Para evitar que spambots saibam que foram bloqueados, o sistema retorna um sucesso falso se o honeypot for preenchido:
```json
{
  "success": true,
  "message": "Votre demande d'offre a été soumise avec succès (honeypot)."
}
```

#### Rate Limit Excedido (`429 Too Many Requests`)
Ocorre quando um IP envia mais de 5 leads no espaço de 1 hora:
```json
{
  "success": false,
  "message": "Trop de requêtes soumises. Veuillez réessayer plus tard."
}
```

#### Erro de Validação (`422 Unprocessable Entity`)
```json
{
  "success": false,
  "message": "Format de courriel invalide. Le nom ne doit pas dépasser 150 caractères."
}
```

---

## 2. Ações Administrativas (Protegido)

Estes endpoints exigem sessão ativa de administrador (`super_admin`, `admin` ou `staff`) e permissões no módulo `crm`.

---

### 2.1 Listagem de Leads
Retorna todas as leads ativas da empresa.

*   **URL**: `/api/v1/leads/leads.php`
*   **Método**: `GET`
*   **Query Params**: `status` (opcional: `New`, `Contacted`, etc.)

#### Resposta (`200 OK`)
```json
{
  "success": true,
  "message": "Liste des leads chargée.",
  "leads": [
    {
      "id": 1,
      "company_id": 1,
      "name": "Jean Dupont",
      "email": "jean.dupont@example.com",
      "phone": "+41 78 123 45 67",
      "origin_address": "1000 Lausanne",
      "destination_address": "1200 Genève",
      "service_date": "2026-07-15",
      "volume_m3": "15.50",
      "status": "New",
      "utm_source": "google",
      "created_at": "2026-06-19 03:00:00"
    }
  ]
}
```

---

### 2.2 Alteração de Estado do Pipeline
Modifica o estado de qualificação comercial da lead.

*   **URL**: `/api/v1/leads/leads.php`
*   **Método**: `PUT`
*   **Headers**: `X-CSRF-Token: <token>`

#### Payload (JSON)
```json
{
  "id": 1,
  "status": "Contacted"
}
```

#### Resposta (`200 OK`)
```json
{
  "success": true,
  "message": "Statut de la lead mis à jour."
}
```

---

### 2.3 Conversão de Lead em Cliente
Gera automaticamente um registo de cliente a partir dos dados da lead.

*   **URL**: `/api/v1/leads/leads.php?action=convert`
*   **Método**: `POST`
*   **Headers**: `X-CSRF-Token: <token>`

#### Payload (JSON)
```json
{
  "id": 1
}
```

#### Resposta (`200 OK` - Novo Cliente Criado)
```json
{
  "success": true,
  "message": "Lead converti en nouveau client avec succès.",
  "client_id": 412,
  "is_duplicate": false
}
```

#### Resposta (`200 OK` - Cliente Duplicado Associado)
Se o e-mail ou telefone já existir na base de dados de clientes, o lead é associado ao cliente existente sem criar duplicações:
```json
{
  "success": true,
  "message": "Lead associado ao cliente duplicado existente.",
  "client_id": 98,
  "is_duplicate": true
}
```
