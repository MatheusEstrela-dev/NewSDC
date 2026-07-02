# Changelog

Registro das releases do NewSDC. Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/)
e nas convenções de commit [Gitmoji](https://gitmoji.dev).

---

## [Rumpelstiltskin + Três Porquinhos] — 2026-07-02

Entrega de performance/arquitetura da release **conto-de-fadas**: paralelismo intra-request
via task workers do Swoole (`feat/Rumpelstiltskin`) e quick wins da varredura de arquitetura
(`feat/tres-porquinhos`), com diagnóstico completo de webhooks, ciclo de request, pool de DB
e memória do browser.

### ⚡ Performance — paralelismo intra-request (Rumpelstiltskin)

- **`Concurrency::tasks()`** (`App\Support\Concurrency`): roteia closures por estratégia —
  (1) task workers do Swoole via `Octane::concurrently` quando `server=swoole` com task
  workers e fora de transação; (2) coroutines com WaitGroup quando os hooks Swoole estão
  ligados; (3) execução sequencial como fallback (RoadRunner local, testes, queue, transação
  aberta). Timeout global `OCTANE_TASK_WAIT_MS` (5000ms) com degradação para sequencial em
  timeout — otimização nunca vira 500.
- **Contrato de closures** documentado no helper: `static`, capturar apenas escalares/arrays,
  resolver services dentro da closure e **nunca** aninhar closures na mesma expressão (a
  serialização do SerializableClosure extrai o fonte pela posição no arquivo).
- **Hot paths paralelizados**: `ProcessoStatsService` (20 COUNTs das estatísticas de
  Decretações → 5 tasks por família), `DashboardStatisticsService` (~28 queries do cold miss
  → 4 partições), `PmdaAnaliseController::index` (3 tasks) e `PmdaPlanoController::index`
  (3 tasks + novo `PmdaPlanoService::statisticsIndex()`).
- **Paginação fora do ciclo de request**: `BaseService::paginate` e services PMDA aceitam
  `$page` explícito; controllers capturam página/path antes das tasks e reaplicam
  `withPath()` no worker HTTP.

### 🐛 Correções

- Closures aninhadas na mesma expressão (`array_map` de `fn` retornando `fn`) serializavam a
  closure errada para o task worker (`Too few arguments`); montagem passou a ser feita em
  `foreach` com `static function () use (...)`.

### 🔒 Segurança / ⚡ Quick wins / 🗃️ Banco (Três Porquinhos)

- **`DB_PERSISTENT` default `false`**: PDO persistente sob Octane/Swoole reusa a mesma
  conexão entre requests do worker e vaza estado/transação; ligar apenas via env em runtime
  não-residente.
- **`Municipio::catalogo()`**: catálogo dos 853 municípios (id, nome, uf) cacheado por 24h,
  substituindo a query por request em 8 call sites (PMDA, TDAP, PAE, reset de senha);
  `Municipio::limparCatalogo()` invalida após alterações.
- **`SetTenant`**: memoização em processo de 30s → 300s, alinhada ao TTL do cache Redis —
  a tolerância a stale do sistema continua única e rotas stateless deixam de consultar
  Postgres/Redis a cada request dentro da janela.
- **Índice composto `pmda_planos (status, municipio_id)`**: cobre a fila de análise, a
  listagem filtrada do índice e os counts dos cards.

### 🔎 Diagnóstico e follow-ups (varredura de arquitetura)

- **Webhooks**: `receive()` já é fire-and-forget (HMAC → fila Redis → 202 com trace_id),
  mas faz 2 queries síncronas de idempotência antes do 202 — mover para o job;
  `WebhookLog::create()` é síncrono por tentativa — enfileirar/batch; configurar workers
  por fila de prioridade no supervisor; rate limit por `X-Webhook-Source`.
- **Request**: sampling do `LogSystemActivity` (1 job por request hoje) aguarda decisão de
  auditoria; TTLs do cache Inertia vs Spatie permissions a sincronizar.
- **Frontend (Parte II do plano de performance)**: atendidos — B4 cleanup de composables,
  B5 Service Worker (Vite PWA com estratégias por tipo), B7 Web Worker (IA), B9
  `requestIdleCallback`/prefetch e code-splitting; parciais — B1 `shallowRef`/`markRaw`
  (falta nos selects de municípios), B6 IndexedDB (só RAT offline), B8 Streams (só IA);
  ausentes — B2 virtual scrolling e B3 `v-memo` (baixo ROI com listas paginadas de 15–20).
- **Infra dev**: `MissingAppKeyException` intermitente no recycle de workers (leitura do
  `.env` montado por volume no Docker Windows) — corrigir exportando `APP_KEY` como env do
  container no compose dev.
- **Decisão de arquitetura selada**: `OCTANE_HOOK_FLAGS_ENABLED=false` — hooks ON tem race
  de container no Octane (sem isolamento por coroutine); caminhos alternativos registrados:
  canário medido multi-IP ou avaliação Hypervel.

### ✅ Verificação

- Fallback sequencial provado em CLI (tinker); task workers confirmados no caminho HTTP.
- Endpoints alterados com medianas de 27–62ms em dev após as mudanças.
- Sob carga local (CPU ~95%), conexões PostgreSQL estáveis em 8 — dentro do guardrail
  (`max_connections=100`).
- Índice novo confirmado via `pg_indexes`; migration aplicada no dev.

---

## [RELEASE_PMDA] — 2026-07-02

Entrega completa do **Módulo PMDA** (Plano Municipal de Defesa Agropecuária) — fornecimento
emergencial de água potável por caminhão-pipa. A tag `RELEASE_PMDA` (commit `ca293ff4`) contém
todo o histórico do módulo, do commit de fundação (`0e7000e4`) ao merge da Central de Análises.

### ✨ Funcionalidades

- **Fundação e core do módulo**: provider, rotas, permissões, menu; planos, máquina de estados
  (`PmdaStatus`: RASCUNHO → COMPLETO → EM_ANÁLISE → APROVADO → ATENDIDO + terminais) e cópia com
  protocolo sequencial `{id}{AAAAMMDD}`.
- **Wizard de 7 etapas**: Início → ISS/Prefeitura → COMPDEC → Ponto de Captação → Locais de
  Distribuição → Ações de Resposta → Anexos/Envio; salva a cada etapa (criação em contexto SPA).
- **Comunidades e representantes**: vínculo ao plano (3 representantes por comunidade para completar)
  e registro **mestre** reutilizável por município.
- **Solicitação/aprovação de comunidades (CEDEC)**: município solicita inclusão; CEDEC aprova
  (promove ao registro mestre) ou rejeita com motivo.
- **Pontos de captação** (compartilhados com o TDAP) e dados de Município/COMPDEC.
- **Fichas COMPDEC**: cadastro, equipe (ativos e anteriores) e documentos (leis e decretos),
  reutilizando o módulo COMPDEC por município.
- **Central de Análises CEDEC** (tela dividida): fila de PMDA em análise (Aprovar / Pedir alteração /
  Arquivar) + fila de solicitações de comunidade (Aprovar / Rejeitar).
- **Impressão da Ficha COMPDEC** e **Série Histórica** do plano (timeline estilo PAE, com envio,
  aprovação, arquivamento e devolutiva).
- **Ações do índice**: histórico, imprimir, duplicar e excluir; downloads na aba Início (passo a
  passo, termo de compromisso, declaração ISS).
- **Envio para análise** alinhado ao legado: exige plano **COMPLETO** + Termo/Ofício e grava
  o responsável (`resp_homolog`).
- **Exclusão por perfil**: admin/super-admin excluem em qualquer status; CEDEC apenas quando
  **Atendido**.

### 🎨 UI / ♻️ Refatorações

- **Stat cards como atalho de filtro rápido** (convenção do projeto): clicar num card filtra a
  listagem; aplicado ao PMDA e a mais 9 módulos (Ajuda Humanitária, Cisterna, Compdec, Decretações,
  Demandas, Estoque, Inventário, Plantão, Treinamento).
- **Identidade visual por módulo** com ícones SVG no header.
- **Cores de status** do protocolo: Em Edição = amarelo, Em Análise = azul, Aprovado = verde,
  Arquivado = vermelho.
- Responsividade do índice + calendário padrão RAT nos filtros; padronização de paginação e datepicker.

### 🗃️ Banco de Dados

- Tabelas: `pmda_planos`, `comunidades` (mestre), `pmda_comunidades`, `pmda_representantes`,
  `pip_pmda_ponto`, `pmda_plano_ponto`, `pmda_compdec_membros`, `pmda_comunidade_solicitacoes`.
- Coluna `motivo_analise` (motivo de arquivamento/devolutiva).
- Permissões `pmda.*` (dashboard, planos, comunidades, representantes, pontos, análise, mensagens,
  anexos) concedidas a `admin` (wildcard) e ao perfil CEDEC (`manager`).

### 🐛 Correções

- Sidebar do PMDA mantém-se ativa; saturação de cor do PlanCon no modo claro.
- Protocolo/Município deixavam de aparecer a partir da aba COMPDEC (colisão do campo `data` na
  serialização do resource).
- "Piscada" ao iniciar um novo PMDA (troca de redirect full-reload por navegação SPA).
- Resolução de marcadores de conflito herdados do `origin/dev` em 7 arquivos do RAT que impediam
  o boot da aplicação.

### ✅ Qualidade

- Backend do PMDA validado por smoke tests de runtime: **25/25 aprovados** (criação, envio, análise,
  exclusão por perfil, fila, solicitação/aprovação de comunidade, cores e máquina de estados).
- `build` (Vite/bun) e boot da aplicação (`route:list`) OK.

### 📌 Pendências conhecidas (fora desta release)

- Transição **Aprovado → Atendido** (execução) via integração com o módulo TDAP — planejada.
- Retrofit dos stat-cards nos índices do TDAP (backend de filtro em WIP).
