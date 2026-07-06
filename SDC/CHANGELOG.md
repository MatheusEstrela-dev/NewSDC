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

### 🚑 Terceira leva — Webhooks e filas (Flautista de Hamelin)

- **Ingestão de webhooks sem I/O de banco antes do 202**: o `receive()` só valida e
  enfileira (push Redis); idempotência (SELECT + upsert em `webhook_events`) movida para o
  `ProcessInboundWebhook` no worker de fila, distinguindo retry travado (PROCESSING antigo,
  retoma) de duplicata concorrente (PROCESSING recente/COMPLETED, descarta). O índice único
  `(external_event_id, provider)` garante linha única em corrida. Header `Idempotency-Key`
  (já documentado no Swagger) agora é honrado como chave de deduplicação. Despacho legado
  `dispatch($event)` continua funcionando.
- **Fix de jobs órfãos em produção**: os workers de prod (`entrypoint.prod.sh`,
  `docker/swoole/entrypoint.sh`, `supervisor/laravel-worker.conf`) consumiam apenas
  `high-throughput,default,low` — as filas `critical`, `high` (RequestPriority) e
  `webhooks` (inbound) **nunca eram consumidas**. Lista alinhada em ordem de prioridade:
  `critical,high,high-throughput,webhooks,default,low`.
- Verificado end-to-end em dev: POST duplo com mesmo `Idempotency-Key` → 202 em 18ms
  (quente), uma única linha `completed` com `attempts=1` no `webhook_events`.

### 🔧 Quarta leva — Dev estável, auditoria e browser (João e Maria)

- **`APP_KEY` como env do container** (app e queue no compose dev, valor no `docker/.env`
  gitignorado, `:?` falha o boot com mensagem clara se ausente): elimina o
  `MissingAppKeyException` intermitente quando o recycle de workers relia o `.env` via
  bind-mount do Windows sob carga. Inclui o fix do queue worker regenerando o autoload
  antes de subir (classmap stale dos módulos montados).
- **Sampling opcional do `LogSystemActivity`** (`ACTIVITY_LOG_SAMPLE_PERCENT`, default
  100 = comportamento atual): erros (≥400) e mutações (não-GET) são SEMPRE logados —
  apenas leituras bem-sucedidas respeitam o percentual, preservando o sinal de auditoria.
- **Frontend B1**: `shallowRef` no catálogo de municípios do `useLocationData` (853 itens
  read-only deixam de virar um Proxy reativo por objeto por instância do composable).
  Os consumidores via props Inertia já estavam shallow (sem mudança).

### 🐳 Quinta leva — Swoole máximo na VM on-premise 8c/16GB (Gato de Botas)

- **`compose.onprem.yml` + `postgres/onprem.conf`**: orçamento explícito da VM (app 3.5cpu/4.5G
  com 12 workers + 4 task, queue 1cpu/1.5G, reverb 0.5cpu/512M, Postgres 2cpu/4G com
  shared_buffers=1GB, Redis 0.5cpu/1.25G volatile-lru, Caddy) — o compose de prod anterior
  mirava um R760 (DB 24G) e estourava a VM; `OCTANE_MAX_REQUESTS=1000` espaça a reciclagem.
- **`START_EMBEDDED_QUEUE`** no entrypoint Swoole (default `true` preserva o Azure):
  elimina o queue worker duplicado quando há container queue dedicado; o guardrail de
  conexões PG conta coerentemente nos dois modos.
- **Memo por worker no `Municipio::catalogo()`** (padrão do SetTenant): 16,5ms (Redis) →
  0,002ms (RAM do processo) por request dentro da janela de 300s.
- **Item 7 do plano (métricas)**: stats do runtime Swoole (workers ociosos, conexões, fila
  de tasks, coroutines, uptime) em `App\Support\Metrics\SwooleRuntimeMetrics`, expostas no
  `/api/metrics` (scrape; token opcional `METRICS_TOKEN` via `X-Metrics-Token`) e no
  `/api/health/metrics` (sanctum + permissão). Correção do artigo: `/octane/metrics` e
  `--metrics` não existem no Octane.
- **Item 6 do plano (tempo real)**: `laravel/reverb ^1.10` instalado (fase A) — conexão
  `reverb` no broadcasting, `config/reverb.php`, serviço no compose on-premise (porta 8080);
  a `GeneralNotification` já emitia no canal `broadcast` (canal `App.Models.User.{id}`
  autorizado) e passa a ter transporte real. Correção do artigo: `'websocket' => true` não
  existe no Octane — o caminho de prod é Reverb. Fase B (Echo no frontend substituindo o
  polling do `useNotifications`) é follow-up (npm travado na máquina).
- **Canário hooks ON (registro definitivo, 03/07)**: com `OCTANE_HOOK_FLAGS_ENABLED=true` o
  server quebra imediatamente — `Swoole\Coroutine\Scheduler::start(): Unable to use async-io
  in task processes` (hooks tornam o I/O dos task workers assíncrono e `task_enable_coroutine`
  precisa ficar `false` no Octane 2.13.1 + Swoole 6.2.1) — além do race de container já
  documentado. Decisão hooks OFF reafirmada; upgrade do Octane fica como follow-up.

### ⚡ Sexta leva — Runtime do worker (Pequeno Polegar)

- **GC manual**: `gc_disable()` no boot do worker — o ciclo automático do GC disparava no
  meio de requests (pausas imprevisíveis); a coleta continua no boundary entre requests
  via listener `CollectGarbage` do Octane (`octane.garbage` = 50MB), que funciona mesmo
  com o GC automático desligado.
- **CPU affinity**: worker N fixado no core N%vCores via `Swoole\Process::setAffinity`
  (o Swoole 6 removeu a função global `swoole_set_cpu_affinity`) — elimina migração de
  workers entre cores e invalidação de cache L1/L2. Verificado em dev: 8 workers pinados
  em cores distintos (`Cpus_allowed_list` 0–7).

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
