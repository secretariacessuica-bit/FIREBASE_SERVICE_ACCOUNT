# OPERATIONAL_APP_MVP — Documentação do Aplicativo Operacional MVP

Esta documentação descreve o desenvolvimento e funcionamento do **App Operacional MVP (Fase 3)** da **Lima Déménagement**, projetado como uma SPA (Single Page Application) mobile-first e PWA instalável.

---

## 1. Telas Implementadas

A aplicação web foi desenvolvida de forma a se adaptar perfeitamente a telas de smartphones.

1.  **Tela de Login**:
    *   Formulário de credenciais que consulta `POST /api/v1/mobile/team.php?action=login`.
    *   Guarda o token Bearer retornado no `localStorage`.
    *   Controla sessões ativas e redireciona automaticamente caso o token esteja presente.
2.  **Painel de Serviços (Dashboard)**:
    *   Lista todos os serviços (projetos) operacionais atribuídos ao utilizador no dia.
    *   Possibilidade de desconexão (Logout) limpando o cache local.
3.  **Detalhe do Serviço**:
    *   Dados de contato, endereços e data.
    *   Botões de Timesheet dinâmicos ("Démarrer journée" / "Fin de service").
    *   Checklist de itens de inventário interativa com seletor de status (Pendente, Conforme, Endommagé, Manquant).
    *   Módulo de upload de Fotos de ocorrências/inventário acionando a câmera nativa do dispositivo móvel.
    *   Painel de Assinatura Eletrônica desenhável na tela tátil para encerramento de serviço com nome completo do cliente.

---

## 2. Fluxo e Arquitetura Offline-First (IndexedDB)

Para contornar quedas de sinal ou falta de rede em regiões de montanha na Suíça:

1.  **IndexedDB Stores**:
    *   `projects`: Armazena a listagem e o detalhe dos projetos atribuídos para exibição offline.
    *   `checklists`: Armazena cópias das checklists de bens e seus status modificados.
    *   `sync_queue`: Fila de sincronização offline que registra batidas de ponto, logs GPS, fotos em Base64 e assinaturas digitais com timestamps locais (`created_offline_at`) e UUIDs exclusivos (`client_uuid`).
2.  **Background Sincronizador**:
    *   Utiliza a propriedade `navigator.onLine` do navegador para identificar se há rede.
    *   A cada 30 segundos, caso haja conexão ativa, lê todos os itens pendentes na `sync_queue` do IndexedDB, faz as requisições HTTP respectivas enviando os tokens e remove da fila local após a confirmação de sucesso pelo servidor.
3.  **Barra de Status (Footer)**:
    *   Exibe de forma persistente um indicador de sinal (Online/Offline) e o total de registros pendentes acumulados na fila aguardando sincronização.

---

## 3. Endpoints Consumidos da Operational API Foundation

*   `POST /api/v1/mobile/team.php?action=login` (Login e geração de token)
*   `GET /api/v1/mobile/team.php?action=assignments` (Listagem de atribuições)
*   `GET /api/v1/mobile/projects.php?id={id}` (Detalhes do projeto)
*   `POST /api/v1/mobile/timesheets.php` (Pontos e horas de trabalho)
*   `POST /api/v1/mobile/location.php` (Histórico de logs GPS in batch)
*   `GET /api/v1/mobile/checklists.php?project_id={id}` (Obtenção de checklist)
*   `POST /api/v1/mobile/checklists.php?action=save` (Salvamento de respostas)
*   `POST /api/v1/mobile/photos.php` (Multipart upload de imagens de bens)
*   `POST /api/v1/mobile/signatures.php` (Gravação de imagem de assinatura em base64)

---

## 4. Limitações Conhecidas e Próximos Passos

1.  **Restrição do Navegador (Câmera)**: Em alguns navegadores iOS/Safari, a câmera requer permissão explícita por clique. O app utiliza botões nativos integrados a inputs para contornar qualquer restrição.
2.  **Sincronização de Imagens Grandes**: Para fotos de alta resolução tiradas com câmeras modernas, a conversão para Base64 no IndexedDB pode consumir memória considerável. Sugere-se comprimir a imagem via Canvas JS antes de enfileirar offline.
3.  **Rastreamento em Background**: O navegador suspende a execução do script caso o utilizador feche a aba ou bloqueie o ecrã. O app armazena a localização capturada de forma imediata quando o ecrã está ativo e o serviço foi iniciado.
