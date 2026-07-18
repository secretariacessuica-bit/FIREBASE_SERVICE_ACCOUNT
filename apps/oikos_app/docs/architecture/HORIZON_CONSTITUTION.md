# HORIZON — CONSTITUIÇÃO DO KERNEL

> Documento permanente. Não editar sem deliberação formal da equipe de arquitetura.
> Versão 1.0 — Julho 2026

---

## O que é o Horizon

O Horizon é o ativo tecnológico mais importante do projeto.

Ele não pertence ao Oikos.

Ele é um **Kernel Constitucional de Domínio**.

Sua missão é transformar interações em fatos legitimados.

---

## O que o Horizon NÃO conhece

O Horizon nunca conhecerá regras específicas de produtos.

Ele não conhece:

- idiomas
- famílias
- avatares
- pets
- IA conversacional
- Flutter
- banco de dados
- interface
- comunidades

Esses conceitos pertencem aos produtos construídos sobre ele.

---

## Missão do Horizon

O Horizon existe para garantir que qualquer fato criado por um sistema seja:

- **legítimo** — passou por validação de domínio
- **rastreável** — toda origem é conhecida
- **reproduzível** — o mesmo input sempre gera o mesmo resultado
- **auditável** — qualquer decisão pode ser inspecionada
- **coerente** — nunca contradiz seus próprios contratos
- **consistente** — comportamento previsível no tempo

---

## Fluxo Constitucional

Todo fato criado em qualquer produto obrigatoriamente percorre:

```
Interação
    ↓
Pretensão
    ↓
Jurisdição
    ↓
Legitimidade
    ↓
Identidade
    ↓
Gênese
    ↓
Trajetória
```

**Nenhum fato nasce diretamente.**

Toda criação deve passar por esse fluxo.

---

## O Horizon não interpreta domínio

Ele não sabe o significado dos dados.

Exemplo: ele não sabe o que é `Apple`.

Ele apenas recebe uma interação e a processa segundo seus contratos.

Quem interpreta o significado é o domínio do produto.

---

## Estabilidade

Produtos evoluem.

O Horizon evolui lentamente.

Um novo recurso nunca deve alterar o kernel apenas para atender um produto.

**Regra de promoção:** somente se vários domínios independentes necessitarem da mesma abstração, ela poderá entrar no Horizon.

---

## Arquitetura em Camadas

```
┌─────────────────────────────────────────┐
│            HORIZON KERNEL               │
│                                         │
│  Interação · Pretensão · Jurisdição     │
│  Legitimidade · Identidade              │
│  Gênese · Trajetória                    │
└────────────────┬────────────────────────┘
                 │ contratos imutáveis
     ┌───────────┴──────────┐
     │                      │
  OIKOS                [Produto N]
  (produto 1)          (futuro)
```

---

## Regra de Separação

| Pertence ao Horizon | Pertence ao Domínio |
|---|---|
| Validação de legitimidade | Regras de idioma |
| Rastreabilidade de fatos | Lógica de gamificação |
| Contratos de identidade | Definição de avatares |
| Fluxo de gênese | Algoritmos de IA |
| Trajetória de eventos | Estrutura de família |

---

## Checklist Obrigatório (antes de qualquer código)

Antes de implementar qualquer funcionalidade, responda:

1. Isso pertence ao kernel universal?
2. Isso pertence ao domínio do produto?
3. Estou acoplando o produto ao Horizon?
4. Estou tornando o Horizon dependente do Oikos?

Se qualquer resposta indicar acoplamento indevido, **refatore antes de implementar**.

---

## Objetivo de Longo Prazo

O Horizon deve continuar sendo um **Kernel Constitucional reutilizável**.

O Oikos deve ser apenas o primeiro de muitos produtos construídos sobre ele.

Toda decisão deve preservar essa visão.

---

## Estado de Implementação

> Decisão registrada em Julho 2026.

### Hoje: Horizon como Camada Conceitual

O Horizon existe atualmente como **arquitetura de pensamento**, não como pacote instalável.

O Oikos é o único produto ativo. Criar `packages/horizon/` agora seria engenharia prematura — adicionaria complexidade de monorepo, versionamento semântico e publicação de pacote sem nenhum segundo produto para justificar.

As decisões tomadas no código do Oikos já respeitam o Horizon conceitualmente.

### Como o Fluxo Constitucional se Manifesta no Oikos Hoje

| Etapa do Horizon | Manifestação no Oikos |
|---|---|
| **Interação** | Toque em objeto da cena (`SceneObject.onTap`) |
| **Pretensão** | `LearningBrain.decide()` interpreta o que o usuário quer |
| **Jurisdição** | `ToolRegistry` valida se a ferramenta existe e está implementada |
| **Legitimidade** | `LearnerSnapshot` confirma: esta ferramenta é adequada para este usuário agora? |
| **Identidade** | `userId` + `AgeExperienceMode` + `ProfileTheme` identificam o agente |
| **Gênese** | `LearningDecision` é criada — ferramenta + tema + duração + objetos da cena |
| **Trajetória** | XP registrado, sessão salva, ciclo de adaptação continua |

O fluxo já existe no código. Ele só não está numa biblioteca separada.

### Critério de Promoção para Pacote Independente

Extrair `packages/horizon/` **somente quando**:

1. Um segundo produto começar a ser desenvolvido sobre o Horizon
2. Uma abstração precisar ser compartilhada entre dois produtos distintos
3. O fluxo constitucional precisar ser testado em isolamento do Oikos

Enquanto isso não ocorrer, o Horizon permanece como lei arquitetural documentada aqui.
