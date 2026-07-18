# Planeamento Técnico — App Mobile Operacional (Fase 3)

Este documento estabelece o planeamento estratégico, a arquitetura de integrações e as definições de API-first para o desenvolvimento da **App Mobile Operacional** do ecossistema **LIMA Solutions ERP**.

---

## 1. Objetivos da Aplicação
*   **Papel Zero:** Eliminar totalmente ordens de serviço impressas nas mãos da equipa de campo.
*   **Controlo em Tempo Real:** Capturar check-ins, check-outs, localização em rota e fotos operacionais.
*   **Proteção de Sinistros:** Fornecer comprovação física através de fotos e checklists digitais de inventário antes e após o serviço.
*   **Assinatura Digital de Adjudicação:** Recolher a aprovação de conformidade do cliente no local da entrega.

---

## 2. Tecnologias Recomendadas
*   **Framework Mobile:** React Native ou Flutter (para compilação nativa multiplataforma iOS/Android).
*   **Autenticação:** Baseada em Tokens Seguros de curta duração (Bearer JWT) no endpoint `/api/v1/driver/login.php`.
*   **Armazenamento Offline:** SQLite local (para guardar dados de rotas e fotos em zonas suíças de montanha sem cobertura de dados móveis).

---

## 3. Requisitos Funcionais Detalhados

### 3.1 Timesheets Mobile & Check-in / Check-out
*   O operador pressiona **"Démarrer la journée"** para marcar o início de expediente.
*   Ao chegar à morada do cliente, pressiona **"Arrivé sur place"** (Check-in).
*   Ao concluir, pressiona **"Fin de service"** (Check-out).
*   **Regra técnica:** Cada um destes eventos envia a coordenada de GPS e o timestamp para a tabela `timesheets` no ERP.

### 3.2 GPS e Rotas
*   Integração direta com a API nativa do dispositivo para acionar o Google Maps ou Waze a partir da morada de origem e de destino especificadas no projeto do CRM.

### 3.3 Fotografias de Bens (Proteção contra sinistros)
*   **Inventário Inicial:** Fotos obrigatórias de objetos frágeis ou pré-danificados antes de carregar o camião.
*   **Inventário Final:** Fotos dos móveis montados no destino.
*   **Upload assíncrono:** Em caso de falta de sinal móvel, as imagens são armazenadas localmente e carregadas em segundo plano (background worker) assim que a ligação for reestabelecida.

### 3.4 Checklist e Inventário Digital
*   Listagem de móveis e caixas gerada no orçamento do ERP.
*   O operador deve marcar "Conforme" ou "Danificado/Em falta" para cada item no momento de carga e descarga.

### 3.5 Assinatura Digital
*   Painel para colheita da assinatura tátil do cliente no ecrã do telemóvel no encerramento do serviço.

---

## 4. Contratos de API-First (/api/v1/driver/)

Todos os novos endpoints devem responder em JSON estruturado e validar o cabeçalho `Authorization: Bearer <token>`.

### 4.1 Check-in / Check-out do Serviço
*   **Endpoint:** `POST /api/v1/driver/service_checkpoint.php`
*   **Payload do Pedido:**
```json
{
  "project_id": 142,
  "checkpoint_type": "check_in", // 'check_in', 'check_out'
  "timestamp": "2026-06-19 14:30:00",
  "latitude": 46.519962,
  "longitude": 6.633597
}
```
*   **Resposta (Sucesso 201):**
```json
{
  "success": true,
  "message": "Checkpoint de check_in enregistré.",
  "data": {
    "timesheet_id": 8752
  }
}
```

### 4.2 Carregamento de Relatórios Fotográficos e Assinaturas
*   **Endpoint:** `POST /api/v1/driver/upload_attachment.php`
*   **Payload (Multipart Form-Data):**
    *   `project_id`: `142`
    *   `type`: `pre_move` (ou `post_move`, `signature`)
    *   `file`: `[Ficheiro Binário]`
*   **Resposta (Sucesso 201):**
```json
{
  "success": true,
  "message": "Fichier téléversé avec succès.",
  "data": {
    "attachment_id": 4410,
    "file_url": "https://limasolutions.ch/private_lima/storage/attachments/img_4410.jpg"
  }
}
```
