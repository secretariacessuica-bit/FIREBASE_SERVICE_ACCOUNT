# Smart Project Assignment Engine

This document details the design, scoring criteria, and operational logic for the Smart Project Assignment Engine.

---

## 1. Conceito Principal: Human-in-the-Loop

A atribuição de colaboradores a projetos de mudança permanece **100% manual e sob decisão humana**. O sistema apenas atua como assistente recomendador, gerando scores e ordenando as melhores sugestões de equipas.
Nenhuma alteração de equipa é efetuada automaticamente pelo motor sem que o gestor clique no botão **Atribuir Equipa** e confirme a ação.

---

## 2. Critérios de Recomendação & Pesos

Cada recomendação avalia equipas de 2 colaboradores (geralmente combinando funções operacionais) com base nos seguintes 4 critérios. Cada critério possui peso igual de **25% (25 pontos de 100)**:

### A. Disponibilidade (Availability) — 25%
* **Regra**: Verifica conflitos de agenda na data de início do projeto.
* **Pontuação**:
  * Sem outros projetos ou atribuições operacionais ativas na data do projeto: **25 pontos**.
  * Conflito de agenda (já alocado em outro projeto na mesma data): **0 pontos**.

### B. Proximação por NPA (Proximity) — 25%
* **Regra**: Calcula a distância aproximada em quilómetros entre a morada do colaborador e o local do projeto utilizando como proxy a diferença matemática entre os códigos postais suíços (NPA - Numéro Postal d'Acheminement).
* **Pontuação**:
  * Distância estimada $\le$ 5 km: **25 pontos**.
  * Distância estimada $\le$ 15 km: **20 pontos**.
  * Distância estimada $\le$ 30 km: **15 pontos**.
  * Distância estimada $\le$ 60 km: **10 pontos**.
  * Distância estimada > 60 km: **5 pontos**.

### C. Experiência (Experience) — 25%
* **Regra**: Avalia o número de projetos operacionais concluídos com sucesso no ecossistema ERP pelo colaborador.
* **Pontuação**:
  * Calculado como: $\text{Min}(25, 5 \times N)$ onde $N$ é o número de projetos concluídos.
  * Máximo de 25 pontos atingido com 5 ou mais projetos concluídos.

### D. Carga de Trabalho (Workload) — 25%
* **Regra**: Prioriza colaboradores com menor ocupação de horas registadas no Timesheet nos últimos 7 dias para equilibrar a carga de trabalho.
* **Pontuação**:
  * $\le$ 8 horas de trabalho nos últimos 7 dias: **25 pontos**.
  * $\le$ 20 horas de trabalho nos últimos 7 dias: **20 pontos**.
  * $\le$ 40 horas de trabalho nos últimos 7 dias: **15 pontos**.
  * > 40 horas de trabalho nos últimos 7 dias: **5 pontos**.

---

## 3. Limitações de Distância por NPA (Swiss Postal Code Proxy)

> [!IMPORTANT]
> A distância calculada pelo motor é baseada na diferença matemática entre os códigos postais suíços (NPAs) e serve apenas como uma **aproximação geográfica de raio**.
>
> Não representa rotas reais de estrada, trânsito ou trajetos de navegação GPS realísticos. É um proxy determinístico rápido concebido para evitar chamadas pesadas a APIs de mapas.

---

## 4. Salvaguardas Ambientais (Sem Dados Mock em Produção)

* **Regra Absoluta**: Colaboradores fictícios/mock **nunca** devem ser inseridos em bases de dados de staging ou produção.
* **Proteção**: A execução de seeds de utilizadores fictícios no ficheiro de migração está protegida pela flag explícita `APP_ENV=local`.
* **Mecanismo de Fallback em Produção**: Se a base de dados de produção contiver menos de 3 colaboradores operacionais reais, o motor de recomendação deteta automaticamente a insuficiência e serve uma lista em memória de equipas simuladas estruturadas de forma fixa, garantindo o funcionamento estético e teste da interface sem poluir a tabela de utilizadores.

---

## 5. Estrutura do Banco de Dados

Os dados de morada e custo operacional foram adicionados à tabela `users`:
* `address`: VARCHAR(255) contendo a morada padrão do colaborador (ex: `Avenue de Cour 60, 1007 Lausanne`).
* `hourly_cost`: DECIMAL(10,2) representando o custo por hora para fins de margem operacional.

As atribuições reais são gravadas na tabela relacional `operational_assignments` com estados `Approved`, `Pending` ou `Cancelled`.
