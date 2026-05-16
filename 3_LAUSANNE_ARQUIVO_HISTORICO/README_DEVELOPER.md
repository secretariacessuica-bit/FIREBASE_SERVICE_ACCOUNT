# CATEDRAL CONNECT - LAUSANNE (SECRETARIA & JORNADA)
## [MODO DESENVOLVEDOR / AI]

Este projeto é a central administrativa da Catedral Lausanne. 
**ID Firebase:** `catedral-connect-bf717`
**Site Hosting:** `catedral-connect-bf717`

### ESTRUTURA DE PASTAS
- `/public`: Contém todos os arquivos web servidos pelo Firebase Hosting.
- `admin.html`: Dashboard Master. Contém a lógica do Radar de Negligência, Gestão de Secretaria e Analytics.
- `recepcao.html`: Ponto de entrada para Check-in de Visitantes e Kids.
- `integracao.html`: Fluxo da Jornada (90 dias) com etapas dinâmicas.
- `checkin.html`: Sistema de Check-in para Eventos (Aberto/Com Lista).
- `js/reception_kids_logic.js`: Lógica core compartilhada entre Recepção e Kids (Triggers de Integração).

### REGRAS CRÍTICAS (IMPLEMENTADAS)
1. **Radar de Negligência**: Alerta automático após 7 dias sem relatório na Jornada. 
   - **Ação "RESOLVIDO"**: Registra nota administrativa no histórico e reseta o contador de 7 dias.
2. **Visitantes de Evento**: Visitantes que se registram via aba de Eventos (ou marcados como 'Evento' no formulário principal) **NÃO** entram no fluxo de Integração. Eles geram apenas um registro na coleção `pending` (Log da Secretaria).
3. **Sincronização Diamond**: Sincronização em tempo real de permissões e estados entre abas.

### CUIDADOS
- **NUNCA** misture os arquivos desta pasta com a pasta `ia_ces_bulle` (Bulle).
- Deploy deve ser sempre via site específico.

### DEPLOY
`firebase deploy --only hosting:catedral-connect-bf717`
