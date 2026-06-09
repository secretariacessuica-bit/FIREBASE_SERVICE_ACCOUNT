# ⚠️ LEIA ANTES DE EDITAR — APP ES DIACONIA [MÓDULO 5]

> Protocolo ativo e centralizador de arquitetura. Qualquer edição nesta pasta ou no sistema associado deve respeitar as regras descritas neste documento.
> Protocolo completo do ecossistema: `docs/PROTOCOLO_CONSULTA_PADRAO.md`

---

## 📍 1. IDENTIDADE E STATUS DO MÓDULO

| Campo | Valor |
|---|---|
| **Congregação** | CES Lausanne (Produção) |
| **Tipo** | Aplicativo PWA Standalone — Controle de escalas e serviços diaconais |
| **Status Geral** | 🟢 **CONCLUÍDO COM SUCESSO / HOMOLOGADO EM PRODUÇÃO** |
| **Modelo Operacional** | **Híbrido de Transição** (Hospedagem no domínio legado apontando para banco de dados novo) |
| **Faturamento** | **Plano Spark (100% Gratuito)**. Cloud Functions e plano Blaze **NÃO** autorizados. |
| **Impacto ao Usuário** | **Zero**. Nenhuma reinstalação do PWA exigida dos obreiros ou supervisores. |

---

## ⚙️ 2. AMBIENTES E DIRETRIZES DO FIREBASE

O mapeamento de projetos e hospedagens no Firebase está estruturado da seguinte forma:

| Ambiente | Projeto Firebase ID | Domínio/URL de Hospedagem | Status Operacional |
| :--- | :--- | :--- | :--- |
| **Legado (Transição)** | `catedral-connect-267b2` | `https://catedral-connect-267b2.web.app` | **ATIVO** (Servindo o PWA híbrido) |
| **Produção Novo** | `diaconia-a38f1` | `https://diaconia-a38f1.web.app` | **ATIVO** (Novo Firestore + Espelho do PWA) |
| **Desenvolvimento** | `ces-diaconia-dev` | `https://ces-diaconia-dev.web.app` | **ATIVO** (Para testes isolados) |

> [!IMPORTANT]
> **Fonte Oficial de Configurações:** As chaves de API, IDs de aplicativo e credenciais de conexão do SDK para os ambientes de produção e desenvolvimento **NÃO** devem ser duplicados neste documento. A única fonte oficial de credenciais é o arquivo [firebase-config.js](file:///c:/Users/Wande/Documents/ia/5_APP_ES_DIACONIA/js/firebase-config.js).

---

## 🔒 3. ARQUITETURA DE SEGURANÇA E BANCO DE DADOS

### Estrutura de Coleções Limpas (Sem Prefixo) no novo Firestore:
O prefixo `diaconia_escala_` foi eliminado na base nova. As coleções operam com nomes limpos:
* `/membros`: Perfis dos obreiros (campo `senha` em texto plano foi 100% excluído).
* `/credenciais`: Protegido. Armazena `passwordHash` (SHA-256) e `passwordSalt` individuais gerados no client-side.
* `/setores`: Configurações de cores, ícones e funções dos setores de atuação.
* `/cultos` e `/escalas`: Planejamento de liturgias e voluntários associados.
* `/produtos` e `/historico_estoque`: Controle de reposição e movimentação de materiais de limpeza/manutenção.
* `/avisos`, `/disponibilidades`, `/mensagens_supervisao`, `/historico_substituicoes` e `/escalas_arquivadas`.

### Regras de Acesso e Autenticação (Spark-friendly):
1. **Autenticação Anônima Obrigatória**: O aplicativo faz login anônimo temporário via Firebase Auth (`signInAnonymously()`) no carregamento. **O provedor de login anônimo deve estar ativado no Firebase Console para que o app funcione.**
2. **Firestore Rules**:
   * Acesso geral permitido apenas para sessões autenticadas (`allow read, write: if request.auth != null`).
   * A coleção `/credenciais` tem bloqueio de listagem global a nível de banco de dados (`allow list: if false`), permitindo apenas a consulta individual por ID (`allow get: if request.auth != null`) para validação do hash.
3. **Criptografia**: Processada inteiramente no cliente via Web Crypto API no navegador, garantindo que hashes e salts nunca circulem de forma vulnerável na rede.

---

## ⚡ 4. BENCHMARK DE DESEMPENHO (LATÊNCIA DO NOVO BANCO)

A latência foi medida disparando consultas paralelas reais via REST API. O novo banco dedicado se provou **111.6 ms mais rápido** do que a infraestrutura legada em média:

* **Membros**: 515 ms ➡️ **309 ms** (Redução de **40%** na latência)
* **Cultos**: 424 ms ➡️ **364 ms** (Redução de **14%** na latência)
* **Escalas**: 455 ms ➡️ **386 ms** (Redução de **15%** na latência)
* **Média Geral de Carregamento**: **353 ms** (Novo) vs **464.6 ms** (Legado)

---

## 📂 5. ARQUIVOS CRÍTICOS DO SISTEMA

* [index.html](file:///c:/Users/Wande/Documents/ia/5_APP_ES_DIACONIA/index.html): Interface principal de escalas e diaconia.
* [js/app.js](file:///c:/Users/Wande/Documents/ia/5_APP_ES_DIACONIA/js/app.js): Lógica de controle do SPA (sessões, navegação, PWA).
* [js/db.js](file:///c:/Users/Wande/Documents/ia/5_APP_ES_DIACONIA/js/db.js): Camada CRUD de banco de dados (controle de cache e conversão de datas).
* [js/firebase-config.js](file:///c:/Users/Wande/Documents/ia/5_APP_ES_DIACONIA/js/firebase-config.js): SDK de configuração e controle de redirecionamento dinâmico de conexões por domínio.
* [sw-notifications.js](file:///c:/Users/Wande/Documents/ia/5_APP_ES_DIACONIA/sw-notifications.js): Service Worker de notificações e cache offline.
* [firestore.rules](file:///c:/Users/Wande/Documents/ia/firestore.rules): Regras de segurança de acesso ativo do Firestore.
* [firestore.indexes.json](file:///c:/Users/Wande/Documents/ia/firestore.indexes.json): Índices compostos exigidos para filtros complexos.

---

## 🔒 6. DIRETRIZES E REGRAS PARA EDIÇÃO E DEPLOY

1. **Sessão Local antes de Deploy**:
   * Sempre confirme o ambiente correto do Firebase CLI antes de realizar o deploy:
     * Para Produção: `firebase use diaconia-a38f1`
     * Para Desenvolvimento: `firebase use ces-diaconia-dev`
2. **Separação de Módulos**:
   * Nunca utilize Project IDs ou arquivos do aplicativo Bulle ou Lausanne admin neste módulo.
3. **Deploy do App**:
   * Subir apenas a hospedagem deste módulo: `firebase deploy --only hosting:diaconia`
   * Subir regras e índices associados: `firebase deploy --only firestore`
4. **Histórico de Restauração**:
   * Salvar backups estruturais em: `3_LAUSANNE_ARQUIVO_HISTORICO/REPOSITORIO_DE_RESTAURACAO_E_VERSOES/`

---

## 🗺️ 7. ROADMAP E MONITORAMENTO

Durante o período de estabilização pós-migração (30 a 60 dias), as seguintes etapas devem ser seguidas:

* **Passo 1: Monitoramento de Tráfego do Legado (`catedral-connect-267b2`)**
  * Verificar nos painéis do Firebase Console se o tráfego de leitura/escrita de banco e hosting no projeto antigo caiu para zero.
  * Garantir que o aplicativo Bulle ou outros sistemas não compartilham recursos ativos no projeto legado antes de desativar.
* **Passo 2: Protocolo Formal de Descomissionamento**
  * **Auditoria Completa:** Revisão de logs e conexões residuais na base antiga.
  * **Validação de Dependências:** Garantia absoluta de que nenhuma outra filial ou sistema (ex: Bulle) consome coleções neste projeto.
  * **Backup Final:** Exportação completa de segurança de todos os dados históricos.
  * **Aprovação Explícita:** Obtenção de autorização formal dos responsáveis antes de qualquer desativação definitiva de rotas ou dados do projeto legado.
