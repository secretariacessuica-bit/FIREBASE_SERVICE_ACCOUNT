# OPERATIONAL_API_FOUNDATION — Documentação de Backend

Esta documentação descreve a infraestrutura de backend da **Fase 3** do ecossistema **Lima Solutions ERP**, projetada sob a arquitetura **API-first** para suportar de forma segura e escalável o futuro App Mobile Operacional da Lima Déménagement.

---

## 1. Arquitetura de Autenticação Mobile

A autenticação mobile é isolada de sessões web baseadas em cookies/CSRF para garantir total compatibilidade com requisições nativas de dispositivos móveis.

### Cabeçalho HTTP
Todas as requisições para a API em `/api/v1/mobile/` devem enviar o token de autorização Bearer:
`Authorization: Bearer <token_puro>`

### Segurança do Token
*   **Não Armazenamento de Token Puro:** O token enviado pelo dispositivo é imediatamente verificado no backend após ser convertido utilizando o algoritmo de hashing `SHA-256`.
*   **Tabela `mobile_tokens`:**
    *   `token_hash` (VARCHAR 64, UNIQUE) – Armazena a versão segura hasheada do token.
    *   `expires_at` – Define o tempo de expiração limite do token (30 dias por padrão).
    *   `revoked_at` – Permite invalidação remota do dispositivo.

---

## 2. Modelo de Banco de Dados (Novas Tabelas)

Para suportar o sincronismo **Offline-First**, todos os registros mutáveis contam com campos de controle local (`client_uuid`, `created_offline_at`, `synced_at`, `sync_status`).

### Tabelas Criadas:
1.  **`mobile_tokens`**: Gerenciamento de sessões ativas por dispositivo/utilizador.
2.  **`operational_assignments`**: Atribuição de motoristas e ajudantes de campo aos serviços operacionais.
3.  **`gps_tracking`**: Logs periódicos e históricos de coordenadas geográficas.
4.  **`project_photos`**: Metadados de fotos pré-mudança, pós-mudança e incidentes.
5.  **`project_checklists`**: Listas de inventário físico com marcação de conformidade.
6.  **`project_signatures`**: Metadados e assinaturas eletrônicas táteis para validação de entrega.

---

## 3. Estruturas e Segurança de Storage

*   **Armazenamento Privado:** As fotos e assinaturas são gravadas fora do diretório público do servidor em:
    *   `/private_lima/storage/project_photos/`
    *   `/private_lima/storage/project_signatures/`
*   **Downloads Seguros Indiretos:** Não há links públicos diretos para arquivos físicos no servidor. Para visualizar ou fazer o download, deve-se chamar o endpoint respectivo enviando um ID de recurso válido e autenticado (Ex: `/api/v1/mobile/photos.php?action=download&id=123`).
*   **Upload Hardening:**
    *   Validação estrita de extensões permitidas (`jpg`, `jpeg`, `png`, `webp`).
    *   Validação do tipo MIME real usando a biblioteca `fileinfo` do PHP.
    *   Bloqueio absoluto de extensões executáveis (`.php`, `.exe`, `.sh`).
    *   Nomes de arquivos físicos internos gerados de forma randômica (`bin2hex(random_bytes(16))`) para evitar ataques de colisão ou paths maliciosos.

---

## 4. Endpoints da API e Contratos JSON

Todos os endpoints respondem em JSON estrito seguindo os formatos de sucesso e erro:

### Formato de Sucesso
```json
{
  "success": true,
  "data": {},
  "error": null
}
```

### Formato de Erro
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensagem detalhada para depuração"
  }
}
```

---

## 5. Resumo de Endpoints Mapeados

### 5.1 Team (`/api/v1/mobile/team.php`)
*   `POST /api/v1/mobile/team.php?action=login` — Recebe `email`, `password` e devolve o token Bearer.
*   `GET /api/v1/mobile/team.php?action=profile` — Obtém perfil do usuário logado.
*   `GET /api/v1/mobile/team.php?action=assignments` — Lista projetos atribuídos.

### 5.2 Projects (`/api/v1/mobile/projects.php`)
*   `GET /api/v1/mobile/projects.php` — Lista projetos e seus estados operacionais.
*   `GET /api/v1/mobile/projects.php?id={project_id}` — Detalhe operacional do projeto.
*   `POST /api/v1/mobile/projects.php` — Atualiza o status do projeto (Ex: "In Transit", "Completed").

### 5.3 Timesheets (`/api/v1/mobile/timesheets.php`)
*   `GET /api/v1/mobile/timesheets.php` — Lista logs de tempos do motorista.
*   `POST /api/v1/mobile/timesheets.php` — Envia registros de ponto de forma pontual ou offline.

### 5.4 Location (`/api/v1/mobile/location.php`)
*   `POST /api/v1/mobile/location.php` — Envia uma ou mais coordenadas (em lote/batch) de GPS.
*   `GET /api/v1/mobile/location.php?project_id={project_id}` — Devolve o histórico de rota do projeto.

### 5.5 Photos (`/api/v1/mobile/photos.php`)
*   `POST /api/v1/mobile/photos.php` — Upload (Multipart form-data) de fotos de inventário.
*   `GET /api/v1/mobile/photos.php?project_id={project_id}` — Obtém lista de fotos anexadas.
*   `GET /api/v1/mobile/photos.php?action=download&id={id}` — Exibe/Baixa arquivo seguro.

### 5.6 Checklists (`/api/v1/mobile/checklists.php`)
*   `GET /api/v1/mobile/checklists.php?project_id={project_id}` — Obtém checklist do projeto (inicializa com itens default se vazia).
*   `POST /api/v1/mobile/checklists.php?action=save` — Atualiza status de múltiplos itens da lista.
*   `POST /api/v1/mobile/checklists.php?action=finalize` — Finaliza a checklist em lote.

### 5.7 Signatures (`/api/v1/mobile/signatures.php`)
*   `POST /api/v1/mobile/signatures.php` — Recebe assinatura base64 da tela tátil (`data:image/png;base64,...`) e grava imagem interna.
*   `GET /api/v1/mobile/signatures.php?project_id={project_id}` — Obtém metadados da assinatura.
*   `GET /api/v1/mobile/signatures.php?action=download&id={id}` — Exibe/Baixa imagem segura da assinatura.

---

## 6. Fluxo de Integração ERP ↔ Mobile

```text
1. ERP Admin ---------------------------------------------> App Mobile
   (Cria Projeto & Atribui Staff)                            (Faz download dos projetos offline)
                                                                    |
2. App Mobile (offline) <-------------------------------------------+
   (Check-in, fotos iniciais, checklist de carga)
                                                                    |
3. App Mobile (online) --------------------------------------------> ERP API (location.php, timesheets.php)
   (Dispara coordenadas GPS periódicas e logs de tempo)
                                                                    |
4. App Mobile (no destino) ----------------------------------------> ERP API (signatures.php, photos.php)
   (Colhe assinatura tátil do cliente e fotos pós-mudança)
```

---

## 7. Riscos Técnicos e Mitigações

1.  **Sincronização de Conflitos (UUIDs):** Para evitar chaves duplicadas no banco ao sincronizar dados gerados em modo offline por múltiplos dispositivos ao mesmo tempo, as tabelas operacionais utilizam `client_uuid` gerado no dispositivo como chave de correspondência imutável.
2.  **Limites de Carga em Montanha (Suíça):** Os endpoints de localização (`location.php`) e checklists (`checklists.php`) aceitam envio estruturado de dados em lotes (arrays). Isso minimiza conexões HTTP e economiza bateria em rotas com sinal móvel intermitente.
