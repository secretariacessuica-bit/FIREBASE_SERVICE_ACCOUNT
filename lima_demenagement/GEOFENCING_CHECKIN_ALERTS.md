# Geofencing Check-in Alerts

This document details the architecture, distance calculation algorithms, privacy compliance, and Swiss NPA fallback mechanisms implemented for Geofencing Check-in Alerts.

---

## 1. Regra de Proximidade (100 Metros)

O objetivo principal desta funcionalidade é alertar o motorista quando este estiver próximo do local de serviço (morada de carga/origem ou descarga/destino) e ainda não tiver efetuado o check-in diário (botão "Démarrer journée").

* **Gatilho (Trigger)**: Se a distância entre a posição do motorista e o ponto de serviço for $\le$ **100 metros**, o PWA exibe um banner de alerta.
* **Mensagem**: `"Está próximo do serviço. Deseja iniciar o check-in?"`.
* **Ação**: Botão `"Fazer Check-in agora"` que realiza a ação de check-in imediata, parando as verificações de proximidade subsequentes.
* **Frequência**: O alerta não é repetido continuamente. Se o utilizador fechar o alerta ou iniciar o serviço, a verificação para. O estado de alerta é reiniciado apenas ao trocar de projeto.

---

## 2. Métodos de Cálculo de Distância

### A. Fórmula de Haversine (Coordenadas Reais)
Quando o projeto ou o cliente contêm coordenadas de latitude e longitude reais (ex: vindas de dados pré-computados), a distância é calculada em metros através da **Fórmula de Haversine**:

\[
a = \sin^2\left(\frac{\Delta\text{lat}}{2}\right) + \cos(\text{lat}_1) \cdot \cos(\text{lat}_2) \cdot \sin^2\left(\frac{\Delta\text{lon}}{2}\right)
\]
\[
c = 2 \cdot \arctan2(\sqrt{a}, \sqrt{1-a})
\]
\[
d = R \cdot c
\]

*Onde $R = 6.371.000$ metros (raio da Terra).*

### B. Fallback por Código Postal Suíço (NPA)
Se o projeto não possuir coordenadas reais, o sistema extrai o código postal suíço (NPA) de 4 dígitos das moradas.
* **Coordenadas do NPA**: Uma tabela leve de referência geográfica em memória associa os prefixos de NPA suíços (Lausanne, Genebra, Vevey, Sion, Bulle, etc.) a coordenadas centrais padrão.
* **Aproximação**: Devido à extensão das zonas de código postal, a distância utilizando NPA é uma **aproximação de raio**. O limiar de disparo é expandido localmente de 100m para **1500m** a partir do centro geográfico do NPA para garantir cobertura prática na chegada ao setor postal.
* A interface exibe claramente uma mensagem indicando a aproximação: `"Nota: Proximidade estimada por código postal (NPA aproximado)."`

---

## 3. Ausência de Geocoding Externo

Para garantir a **compatibilidade offline** completa, velocidade e controle de custos, o sistema **não realiza chamadas a APIs de mapas externas** (Google Maps, Mapbox, OpenStreetMap, etc.) no dispositivo ou servidor nesta fase. Todo o cálculo é matemático e local.

---

## 4. Privacidade e Segurança

1. **Sem Banco de Dados Duplicado**: O sistema não cria novas tabelas no banco de dados para guardar o geofence. As coordenadas capturadas para o cálculo de proximidade são processadas estritamente em memória do navegador. O histórico físico de localização continua a ser guardado apenas na tabela `gps_tracking` já existente se o utilizador ativar o tracking.
2. **Contexto Operacional Restrito**: O GPS/geofence de check-in só é acionado localmente quando:
   * O utilizador está autenticado no PWA.
   * O serviço/projeto está atribuído ao utilizador.
   * A janela de detalhes do projeto está aberta na tela.
   * O serviço ainda não foi iniciado (sem check-in realizado).
3. **Não Exposição**: A localização exata em tempo real do motorista **nunca é exposta ao Portal do Cliente** em conformidade com as regras de privacidade empresarial.
4. **Respeito a Permissões**: Caso o utilizador recuse o acesso à localização, a falha do navegador exibe um aviso simples e não bloqueia a utilização offline e manual do restante das funcionalidades da aplicação.
