# OIKOS — CONSTITUIÇÃO DO PRODUTO

> Documento permanente. Reflete a identidade, filosofia e arquitetura do Oikos.
> Versão 1.0 — Julho 2026

---

## O que é o Oikos

Oikos vem do grego: **Lar, Família, Pertencimento**.

O Oikos não é um tradutor. Não é um aplicativo de idiomas.

É um universo inteligente onde pessoas aprendem idiomas de maneira personalizada através da Inteligência Artificial.

O foco principal é **família**. Mas também atende: pessoa sozinha, casais, amigos, escolas, empresas, comunidades.

---

## Missão

> Fazer cada usuário sentir que o aplicativo foi criado exclusivamente para ele.

---

## Relação com o Horizon

O Oikos utiliza o Horizon como kernel constitucional.

O Oikos **não modifica** as regras do Horizon.

Tudo relacionado a aprendizagem, idiomas, família, IA, comunidade, gamificação, tradução, avatares, pets, exercícios e desafios pertence **exclusivamente** ao domínio Oikos.

---

## Filosofia de Aprendizagem

### Não existem aulas iguais

Não existe sequência fixa.
Não existe dashboard tradicional.

A IA adapta continuamente:
- interface
- ferramentas
- dificuldade
- duração
- ordem
- motivação
- experiência

### O ciclo nunca termina

```
Observação
    ↓
Hipótese
    ↓
Teste
    ↓
Validação
    ↓
Adaptação
    ↓
Nova observação
```

### 1 usuário = 1 aplicativo

Não literalmente. Conceitualmente.

Quando João abre o Oikos e quando Sophia abre, eles não estão usando perfis diferentes. Estão usando **aplicativos diferentes**, construídos dinamicamente pelo mesmo motor de IA.

---

## Motor de Aprendizagem (OikosLearningBrain)

O Brain é o componente central de inteligência do Oikos.

**Input:**
```
Idade · Objetivo · Tempo disponível · Horário · Humor
Erros · Acertos · Conversas · Preferências · Família
Comunidade · Rotina · Interesses
```

**Output:**
```
Ferramenta ideal → Tema ideal → Dificuldade → Duração → Forma de ensinar
```

**Exemplo:** João. Hoje. 15 minutos. Cansado. Vai viajar.
A IA responde: `Conversação | Aeroporto | 12min | Revisão: 3min`

### Catálogo de Ferramentas

| Ferramenta | Símbolo |
|---|---|
| Histórias | 📖 |
| Jogos | 🎮 |
| Conversação | 🎤 |
| Filmes | 🎬 |
| Música | 🎵 |
| Flashcards | 📚 |
| Quebra-cabeças | 🧩 |
| Pronúncia | 🗣 |
| Listening | 👂 |
| Câmera | 📷 |
| Realidade Aumentada | 🌎 |
| Chat IA | 💬 |
| Chat Família | 👨‍👩‍👧 |
| Simulação de Viagem | ✈ |
| Receitas | 🍳 |
| Teatro | 🎭 |
| Podcasts | 🎙 |
| Séries | 📺 |
| Leitura | 📑 |
| Revisão Inteligente | 🧠 |
| Escrita | ✍ |
| Desenho | 🎨 |
| Missões | 🎯 |

O usuário **nunca vê todas**. A IA decide quais mostrar.

---

## Inteligência Artificial

A IA do Oikos nunca assume conhecer o usuário.

Ela trabalha com **hipóteses**.

- Toda hipótese possui confiança
- Toda confiança pode diminuir
- Toda adaptação é gradual

**Proibido:**
- Testes de personalidade
- Questionários extensos
- Inputs forçados

A IA aprende pela conversa. Nunca pelo formulário.

---

## Filosofia de Interface

### O Oikos não possui telas. Possui lugares.

O usuário não navega por um sistema. Ele entra em um mundo vivo.

**Nunca criar:**
- dashboards corporativos
- telas cheias de cards
- menus tradicionais
- listas enormes
- interfaces bancárias

**Sempre criar:**
- objetos interativos no ambiente (exclusivamente personagens)
- transições que parecem naturais
- espaços com personalidade e perspectiva física (chão/piso)

**Inspirações:** Nintendo · Animal Crossing · Pixar · Monument Valley · Apple

### Foco nos Personagens (Limpeza Visual)

Para evitar poluição visual e manter a imersão, a cena principal (**LivingScenePage**) é minimalista e foca nos personagens: o **Avatar** e o **Companheiro/Lumo**.
- **Ancoragem Física:** Os personagens não flutuam; estão sempre ancorados a um plano físico (chão/piso) com dimensões proporcionais.
- **Sem Objetos Flutuantes Soltos:** Não há livros, microfones ou emojis flutuando sem contexto na tela.
- **Lumo como Hub de Atividades:** O Lumo indica e avisa o que há disponível por meio de balões de fala interativos. Ao clicar nele, abre-se um menu moderno (BottomSheet) com as opções de atividades (`LearningDecision`) geradas pela IA.
- **Avatar como Hub de Identidade:** O Avatar serve como o portal para configurações, personalização e identidade visual daquele perfil.

---

## Personagens

### OikosAvatar (Customização Visual)

Cada usuário possui um avatar vetorial customizável em vez de um emoji estático.
- **Estrutura Modular:** O avatar é composto por partes intercambiáveis: cabeça, olhos, sobrancelha, boca e cabelo.
- **Paleta Temática:** Cores dinâmicas para pele, cabelo, camisa, calça e sapatos gerenciadas pelo `AvatarTheme`.
- **Expressões Dinâmicas:** Reage ao contexto com expressões (happy, thinking, neutral, cheering) ao comemorar ganhos de XP ou durante o estudo.
- **Editor Visual:** Interface em abas integrada ao onboarding permitindo montagem em tempo real e persistência local direta.

### Lumo

- Existe apenas um
- Pertence à **família**, não a um usuário
- Representa: conhecimento coletivo, evolução da família, comunidade
- O crescimento do Lumo representa o crescimento de todos

### Companheiro Individual

- Cada usuário possui um companheiro exclusivo
- Nasce quando o usuário entra pela primeira vez
- O Lumo envia um fragmento de energia que cria esse companheiro
- Nunca é compartilhado
- Adapta-se ao usuário continuamente

### Faixas etárias dos Companheiros

| Faixa | Personalidade |
|---|---|
| Até 7 anos | Nasce de um ovo. Bebê. |
| 8–11 anos | Curioso. Explorador. |
| 12–15 anos | Moderno. Descolado. |
| 16–18 anos | Parceiro. Objetivo. |
| Adultos | Mestre. Mentor. Elegante. |
| Idosos | Paciente. Gentil. Calmo. |

---

## Stack Técnica

| Camada | Tecnologia |
|---|---|
| App | Flutter (Web + Mobile) |
| State | Riverpod |
| Persistência local | Hive |
| Linguagem | Dart 3 |

---

## Arquitetura do Produto (`lib/features/`)

```
features/
├── brain/          ← OikosLearningBrain (motor de IA adaptativa)
├── learning/       ← Motor de ensino: Journey, Chapter, Lesson, Exercise
├── home/           ← LivingScenePage (cena principal, mundo vivo)
├── profiles/       ← MemberIdentity, ProfileTheme, AgeExperienceMode
├── family/         ← Núcleo familiar, progresso coletivo
├── companion/      ← Companheiro pessoal de cada usuário
├── missions/       ← Missões diárias
├── memories/       ← Histórico e tesouros da família
└── onboarding/     ← Entrada no universo (sem questionários)
```

---

## Regras de Desenvolvimento (nunca quebrar)

1. **Nenhuma tela pode parecer um sistema ou dashboard**
2. **O Lumo pertence à família, não a um usuário**
3. **Os companheiros são únicos por usuário**
4. **A IA aprende o usuário pela conversa, nunca por questionário**
5. **A Home é gerada pelo Brain, não fixada no código**
6. **Cada usuário é um aplicativo diferente**
7. **Nunca implementar lógica de domínio dentro do Horizon**
8. **Nunca mover regras do Oikos para o kernel**

---

## Checklist de Qualidade (antes de qualquer PR)

Antes de escrever código, responda:

1. Isso melhora a experiência do usuário?
2. Isso torna o aplicativo mais humano?
3. Isso respeita a filosofia do Oikos?
4. Isso pertence ao Horizon ou ao Oikos?
5. Estou criando acoplamento indevido?

Se qualquer resposta for negativa, **refatore antes de implementar**.

---

## Regra Final

> Nunca entregue apenas uma resposta.
> Sempre pense como cofundador do Oikos.
> Se perceber uma oportunidade de melhorar uma ideia, questione.
> Proponha alternativas. Explique os prós e contras.
> Não concorde automaticamente. Seja crítico, criativo e mantenha a visão do produto consistente.
