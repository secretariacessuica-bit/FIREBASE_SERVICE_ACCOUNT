# HORIZON LAB — ENGINEERING HANDOVER & AUDIT REPORT
**Confidencial | Avaliação Arquitetural e de Engenharia**

Como Principal Software Architect, minha primeira ação não é escrever código, mas garantir que o código que escrevemos nos leve ao MVP sem colapsar sob o próprio peso conceitual.

Avaliei profundamente o contexto do Horizon, suas premissas arquiteturais, decisões homologadas e o fluxo de trabalho atual com o Gemini.

Abaixo apresento a auditoria crítica. Minha visão é pragmática: **dogmas não entregam software; software rodando em produção entrega.**

---

## 1. Avaliação Geral do Projeto

A filosofia do Horizon é conceitualmente brilhante: a separação estrita entre Ciência (HKB), Arquitetura/Computação (HAD) e Execução (Código) é o estado da arte para sistemas guiados a dados (Data-Driven/Ontology-Driven Architecture). 

No entanto, **a execução dessa filosofia está falhando**. O projeto está sofrendo de paralisia por análise e superengenharia (Over-engineering). Estamos construindo uma "Torre de Marfim" teórica: a aplicação, o pipeline e o domínio foram construídos no vácuo, sem que a persistência, o bootstrap e a integração fossem testados. Isso é uma receita para o desastre conhecido como "Big Bang Integration". 

Além disso, o processo de engenharia focado em microgerenciar a IA (via OT-A, OT-I, OT-R) é um antipadrão. Tratar uma IA estocástica como um operário de linha de montagem determinístico explica exatamente por que os contratos são quebrados e alucinações ocorrem.

## 2. Pontos Fortes

* **Agnosticismo Científico no Código:** A premissa de que o código não sabe ciência garante que o software não precise ser reescrito a cada nova descoberta.
* **Fronteiras Claras:** A distinção entre HKB, HAD e Código previne o acoplamento catastrófico comum em plataformas biomédicas/científicas.
* **Vocação para a Rastreabilidade:** A exigência de que nenhuma heurística seja criada pelo software forma a base perfeita para um sistema compliance/auditável.

## 3. Pontos Fracos

* **Integração Tardia (Risco Crítico):** Ter 100% do Domínio, Application e Pipeline sem Bootstrap e Persistência significa que nada foi provado. O código atual é apenas um rascunho caro.
* **Microgerenciamento da IA:** O fluxo de OTs está focado em prever o que a IA vai fazer, gerando uma burocracia imensa sem impedir as falhas (alucinações).
* **Ausência de Rede de Segurança (Testes Automatizados Executáveis):** A IA quebra contratos porque ninguém está compilando ou rodando testes automatizados contra o código dela *durante* a geração.

## 4. Riscos Arquiteturais

* **O Antagonismo da Tipagem (Stringly-Typed Domain):** 
  Decidiu-se que `OntologyKey` e `CommunicationChannel` não são enums. Entendo o motivo (não engessar a ciência no código). Porém, encapsular `Map<OntologyKey, OntologyValue>` significa transferir **todos os erros de sintaxe para tempo de execução (runtime)**. 
  *Risco:* Um erro de digitação no HKB fará a aplicação falhar silenciosamente no servidor. Sem um contrato forte, a aplicação não tem como se defender de dados malformados vindo do HAD/HKB.
* **O Dogma do `eventId` fora do Domínio:** 
  A regra "eventId pertence exclusivamente à infraestrutura" é perigosa. Se a rastreabilidade e a reprodutibilidade são pilares do Horizon, o Domínio **precisa** conhecer um identificador de contexto (seja um `TraceId`, `CorrelationId` ou `InferenceId`). Deixar o Domínio "cego" em relação à identidade da execução impede que ele emita eventos de domínio rastreáveis.
* **Inconsistência no `InferenceContext`:** 
  Conter apenas `CommunicationChannel` e `ObservedContext` parece insuficiente para um motor de inferência robusto, ignorando metadados críticos como versão do HAD/HKB processada ou timestamps lógicos.

## 5. Riscos de Engenharia

* **Sindrome do "Big Bang Integration":** Quando ligarmos a Persistência ao Pipeline e à Application, as abstrações puristas inevitavelmente vazarão. Descobriremos que transações, paginação, resiliência (retries) ou limites de memória não foram pensados, forçando refatorações massivas nas camadas já "100% concluídas".
* **Código Baseado em Suposições:** Como não há infraestrutura, a Application atual supõe que o I/O é instantâneo e livre de falhas (happy path).

## 6. Problemas de Governança

* **O Arquiteto como Gargalo:** O Chief Architect precisando homologar cada bloco individualmente (Milestone -> Revisão -> Homologação) estrangula a vazão do projeto.
* **Documentação excessiva vs. Validação executável:** Estamos tentando garantir a qualidade lendo código gerado pela IA (OT-R) ao invés de rodar testes automatizados rígidos contra o código gerado. Humanos são péssimos em ler código para achar quebras de contrato; compiladores e testes são perfeitos nisso.

## 7. Problemas do Processo Atual (A relação com a IA)

O Gemini cria exceções novas, altera assinaturas e assume contratos porque o processo é **Open-Loop** (Malha aberta).
Nós enviamos um documento (OT-I) e esperamos o código perfeito. A IA, por natureza, preenche lacunas ("hallucination"). Quando ela não entende algo, ela inventa para poder entregar. 
O processo de OT-A -> OT-I -> OT-R é uma tentativa de impor um controle Waterfall a uma ferramenta que funciona melhor com ciclos iterativos e feedback técnico instantâneo (Test-Driven).

---

## 8. O que Manter

* A separação conceitual HKB (Ciência) -> HAD (Arquitetura Lógica) -> Código.
* A ausência absoluta de heurísticas de negócios/ciência hardcoreadas no código-fonte.
* A estrutura base da Clean Architecture (mas com limites mais porosos e pragmáticos).

## 9. O que Eliminar

* **O fluxo OT-A -> OT-I -> OT-R em cascata.** Ele atrasa o projeto e não resolve o problema das alucinações.
* **O dogma de que o Domínio não conhece identidade de execução.** O `eventId` (ou renomeado para `InferenceTraceId`) DEVE entrar no Domínio para fins de log, auditoria e emissão de Domain Events.
* **A restrição de que o código "não assume tipagem".** Substituir por **"o código assume tipagem dinâmica validada por Schema ou gerada no Build"**.

## 10. O que Simplificar

* **Abstrações Prematuras:** Se um DTO ou contrato da Application não foi validado conectando REST e Persistência, simplifique-o. Não defina interfaces para problemas que ainda não surgiram.

## 11. O que Reorganizar

* **Parar o desenvolvimento de novos domínios ou fluxos REST Imediatamente.**
* O próximo passo não é terminar o REST. É o **Bootstrap e a Integração (Walking Skeleton)**.
* Ligar o que existe hoje: um endpoint REST, passando pela Application, Domain, Pipeline e salvando/lendo em uma Persistência Mockada (in-memory) ou banco real básico.

---

## 12. Novo Processo de Engenharia Recomendado: Test-Driven AI (TDAI)

O erro não está no Gemini, está em como pedimos as coisas para ele. Não passaremos mais documentos textuais exigindo que ele não altere contratos. **Nós criaremos o cinto de castidade no código.**

1. **Testes e Interfaces Primeiro (Pelo Arquiteto/Engenheiro):** Definimos a interface do contrato exato e os testes unitários/integração que validam esse contrato.
2. **Delegação para a IA:** O Gemini recebe o ambiente com os testes falhando. A missão dele é APENAS fazer os testes passarem.
3. **Feedback Loop (Auto-healing):** Se a IA inventar um DTO ou alterar a interface, o código não compilará ou os testes falharão. A própria IA analisa o erro do compilador e se corrige, ANTES de devolver para o humano.
4. **Homologação Contínua:** O humano só revisa PRs que já passaram na pipeline de CI.

## 13. Novo Fluxo de Trabalho (Humano-IA)

* **Design (Humano):** Define o Contrato (Interface Java/TypeScript/C# etc) e o Teste de Aceitação.
* **Prompt (Humano):** *"Implemente esta interface para fazer esses testes passarem. Não altere a interface. Se o teste não passar, corrija."*
* **Agentic Execution (IA):** A IA codifica, roda os testes locais, percebe que quebrou um contrato, refatora, roda de novo, até dar verde (Green).
* **Review (Humano):** Revisão estrita de segurança, complexidade ciclomática e semântica (não mais caça a erros de sintaxe ou alucinações de DTO).

## 14. Roadmap Recomendado para o MVP

* **Semana 1: Operação Walking Skeleton (Prioridade Máxima)**
  * Suspender novas features.
  * Implementar o Bootstrap.
  * Implementar um adaptador de Persistência básico.
  * Fazer uma requisição REST atravessar todas as camadas e gravar algo.
  * *Objetivo: Descobrir onde a Clean Architecture desenhada quebra no mundo real.*

* **Semana 2: Resiliência de Contratos e Tipagem**
  * Resolver a fragilidade do `Map<OntologyKey, OntologyValue>`.
  * Introduzir um "Schema Validator" no momento da injeção de dependência ou entrada do request, garantindo que o que chega da HKB tem a estrutura correta ANTES de entrar no Domínio.
  * Introduzir o `InferenceTraceId` (`eventId`) como cidadão de primeira classe no Domínio.

* **Semana 3: Replataforma do Processo IA**
  * Substituir o modelo OT-A/I/R pelo modelo TDAI (Test-Driven AI).
  * Criar a suíte de testes de borda para que a IA possa iterar autonomamente.

* **Semana 4+: Expansão Controlada rumo ao MVP**
  * Com o esqueleto de pé e o CI garantindo que a IA não quebra contratos, iniciar a expansão das regras do pipeline.

## 15. Conclusão

Vocês construíram um modelo mental espetacular para evitar o maior problema de softwares científicos (o acoplamento código-ciência). No entanto, criaram um processo de engenharia burocrático, frágil no runtime e lento. 

A arquitetura atual é um castelo de cartas pronto para cair na primeira integração real porque estamos microgerenciando a IA em papel e ignorando o compilador.

Precisamos trocar o rigor dos documentos (OTs) pelo rigor do código (Testes e Integração Contínua). Ao implementarmos o **Walking Skeleton** agora, e adotarmos a IA orientada a testes, vamos acelerar radicalmente a entrega e construir a confiança de que o Horizon realmente suportará cargas e auditorias em produção.

Estou pronto para conduzir essa mudança estrutural a partir de hoje.

— **Principal Software Architect, Horizon Lab.**