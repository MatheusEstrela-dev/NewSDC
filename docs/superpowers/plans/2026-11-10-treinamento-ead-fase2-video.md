# Treinamento EAD, Fase 2 (Vídeo on-premise) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Professor sobe um MP4 em partes para a **biblioteca de vídeos** (ou reaproveita um já pronto) e o item do curso o vincula; um worker de fila converte para HLS com FFmpeg, o Caddy entrega os segmentos só para quem pode assistir, e o aluno vê o vídeo na sala de aula com retomada por tempo. O mesmo vídeo pode estar em vários cursos (emenda `.superpowers/sdd/2026-11-10-treinamento-ead-fase2-video/emenda-biblioteca.md`, que prevalece; spec §7.3).

**Architecture:** O Octane só recebe partes de 8 MB e grava no disco `treinamentos` compartilhado; `finalizar` despacha `ProcessarVideoJob(videoId, uploadId)` na fila `videos`, consumida por um serviço próprio (`queue-videos`, imagem `sdc-queue` + FFmpeg). O job monta o original, sonda com `ffprobe`, transcodifica (480p/720p) para um diretório temporário e publica por `rename` em `hls/{video}/{versao}/`. O vídeo é um registro próprio (`treinamento.videos`); `modulo_itens.video_id` só aponta para ele. O Caddy serve `/videos/*` do mesmo volume com `forward_auth` para `/videos/auth`, um endpoint leve do Octane que valida sessão + trilha de qualquer curso que use o vídeo e cacheia 5 min. O progresso reaproveita `ProgressoService` da Fase 1, generalizado para "total por tipo de item" (vídeo: via `item->video`).

**Tech Stack:** Laravel 12 + Octane/Swoole 6.2, PostgreSQL (`sdc_treinamento`, schema `treinamento`), Redis (fila `redis-videos`, cache), FFmpeg/ffprobe via `Illuminate\Support\Facades\Process`, Caddy 2 (`forward_auth`, `file_server`), Docker Swarm, Vue 3 + Inertia, `hls.js@1.7.4`.

**Spec:** `docs/superpowers/specs/2026-11-10-treinamento-video-onprem-design.md` (seções citadas como §N). Base: `docs/superpowers/plans/2026-11-10-treinamento-ead-fase1-base.md` (Fase 1), cujas interfaces este plano consome.

## Global Constraints

- Executa **depois** da Fase 1 concluída, na mesma branch `feat/treinamento-ead` (worktree `.worktrees/treinamento-ead`), ou em `feat/treinamento-video` criada a partir dela.
- Banco: toda DDL entra **consolidada e idempotente** na migration principal `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php` (novo método privado `criarVideo()`, que cria `videos`, `modulo_itens.video_id` e `video_uploads`), nomes qualificados `treinamento.x`, sem FK para `users`. Nenhuma migration nova.
- Vídeo **nunca** passa inteiro pelo Octane: parte de `8 * 1024 * 1024` bytes, limite por vídeo `2 * 1024 * 1024 * 1024` bytes, mimes `video/mp4`, `video/quicktime`, `video/x-matroska`, sessão expira em 24 h (§7.1). `config/octane.php` **não** muda nesta fase: já declara `package_max_length` de 64 MB desde a Fase 1 (§13).
- FFmpeg roda **só** no worker da fila `videos` (conexão `redis-videos`, `retry_after` 3700, `--timeout=3600`, `--tries=3`), com `nice -n 10` e `-threads 2`; renditions 480p (854x480, 800k) e 720p (1280x720, 2500k) ativas, 1080p (1920x1080, 5000k) desligada por config; `preset veryfast`, `crf 23`, `hls_time 6`, áudio `aac 128k` (§8).
- Caminhos em disco e URL são chaveados por **videoId** (nunca por item): HLS publicado por versão em `hls/{videoId}/{versao}/master.m3u8`; a troca é atômica (`rename`), a versão anterior só some depois (§8.4) e vale para todos os cursos que usam o vídeo. URL pública `/videos/{videoId}/{versao}/...` servida pelo Caddy; o Laravel só serve segmento quando `TREINAMENTO_VIDEO_SERVIR_PELO_APP=true` e fora de produção (§9.3).
- `/videos/auth`: 204 ou 403 sem corpo, cache positivo de 300 s por `(id da sessão, vídeo, versão)`, negativo não cacheia (§9.2). O 403 sai com `Cache-Control: no-store`; caminho com segmento `.tmp-*` é sempre 403.
- Permissões da biblioteca (§7.3): gerenciar um vídeo = dono (`videos.user_id`) ou `treinamento.cursos.edit` (middleware `gerencia.video`); ver a biblioteca e vincular um vídeo `PRONTO` = `treinamento.professor.acessar` no curso que leciona (`leciona.curso`); excluir só se `!emUso()`, senão 422 "Vídeo em uso por N itens".
- Rotas do professor ficam em `SDC/routes/modules/treinamento-professor.php` (não em `treinamento.php`); o alias `gerencia.video` é registrado no `TreinamentoServiceProvider`, ao lado de `leciona.curso`.
- Multi-réplica (homolog roda 4 réplicas do app atrás do Caddy; partes do mesmo upload caem em réplicas diferentes): nenhum estado de upload ou de autorização em memória do processo (`static`, `SwooleTable`, cache `array`). Estado no banco + disco `treinamentos` (volume compartilhado); exclusão mútua (abrir, finalizar, mesma parte duas vezes) por `Cache::lock()` no redis ou constraint do banco; cache do `/videos/auth` pelo `Cache` facade.
- Produção entrega vídeo **só** pelo Caddy; a rota que serve pelo app (`TREINAMENTO_VIDEO_SERVIR_PELO_APP`) fica bloqueada em produção e a contingência pelo Swoole fica para uma fase posterior.
- Sem emojis no código. Logs de depuração removidos após o teste. DRY/SOLID. Testes em `SDC/tests/Feature/Treinamento/Ead/` rodam localmente e **não entram em commit**. Commits atômicos por task, gitmoji, pt-BR, **sem** trailer `Co-Authored-By`.
- Testes rodam via `bash .superpowers/sdd/2026-11-10-treinamento-ead-fase2-video/phpunit.sh <path relativo a SDC/>` (abaixo, `TEST <x>` é esse comando). FFmpeg não existe no host: todo teste de processamento usa `Process::fake()`.
- Fixtures: títulos `TST-EAD-`, CPF de cidadão `999...`; arquivos de teste via `Storage::fake('treinamentos')` e `UploadedFile::fake()->create(...)`.

## Review Focus

1. Parte com `n` fora de `1..total_partes`, tamanho diferente do esperado, ou `upload` de outro vídeo na URL: esperado 422/404 e **nada gravado** no disco nem nos contadores; quem não é dono nem tem `cursos.edit` recebe 403 em toda rota de gerência. (Task 2)
2. `finalizar` chamado duas vezes, ou com parte faltando: um único job despachado no primeiro caso, 422 sem despacho no segundo. (Task 2)
3. `/videos/auth` com URI malformada, versão que não é a atual nem a anterior, item de pasta travada, cidadão em curso `SERVIDORES`, sessão sem login: sempre 403, e o 403 **não** entra no cache. (Task 4)
6. Vídeo compartilhado por dois cursos: a autorização segue a trilha de cada curso (aluno do curso A só passa se o **item dele** estiver liberado; professor de qualquer um dos cursos passa). (Task 4)
7. Excluir vídeo em uso por item não excluído: 422 "Vídeo em uso por N itens", nada removido do disco nem do banco; desvincular não apaga o vídeo. (Tasks 2 e 3)
4. Job morto no meio (original parcial, `.tmp-*` sobrando) e reexecutado: retoma sem duplicar; nova versão que falha mantém a anterior servível e o item volta a `PRONTO`. (Task 3)
5. Progresso em item `VIDEO` sem vídeo `PRONTO`, ou `posicao` acima de `duracao_segundos`: 422 no primeiro, corte no total e `CONCLUIDO` no segundo; `posicao` menor que a salva não regride. (Task 1)

---

## File Structure

**Backend (`SDC/app/Modules/Treinamento/`)**
- `Enums/VideoStatus.php` (novo); `Enums/TipoItem.php` (+ `VIDEO`).
- `Models/Video.php`, `Models/VideoUpload.php` (novos); `Models/ModuloItem.php` (+ `video()`, `totalProgresso()`).
- `Support/CaminhosVideo.php` (novo: todos os caminhos e a URL do master num lugar só, por `videoId`).
- `Services/Video/VideoBibliotecaService.php`, `Services/Video/VideoUploadService.php`, `Services/Video/ProcessadorDeVideo.php`, `Services/Video/MontadorDeOriginal.php`, `Services/Video/Ffmpeg.php`, `Services/Video/PublicadorHls.php`, `Services/Video/AutorizacaoVideoService.php`, `Services/Video/VideoMetrics.php` (novos).
- `DTOs/SondagemVideo.php` (novo); `DTOs/EstadoItem.php` (+ `video`).
- `Exceptions/FalhaDeVideo.php` (novo).
- `Http/Middleware/GerenciaVideo.php` (novo; alias `gerencia.video`, registrado no `TreinamentoServiceProvider` junto ao de `leciona.curso`).
- `Jobs/ProcessarVideoJob.php`, `Jobs/LimparVideosOrfaosJob.php` (novos).
- `Controllers/Professor/Video/{VideoIndexController,VideoStoreController,VideoUpdateController,VideoDestroyController,UploadAbrirController,UploadParteController,UploadFinalizarController,UploadCancelarController,StatusController,ReprocessarController,ItemVideoVincularController,ItemVideoDesvincularController}.php`, `Controllers/VideoAuthController.php`, `Controllers/VideoArquivoDevController.php` (novos).
- `Requests/Professor/Video/{SalvarVideoRequest,AbrirUploadRequest,ReceberParteRequest,VincularVideoRequest}.php` (novos).
- `Services/ProgressoService.php`, `Services/TrilhaService.php` (alterados).

**Outros backend:** migration principal; `SDC/config/treinamento.php` (novo); `SDC/config/queue.php`, `SDC/config/logging.php`; `SDC/routes/modules/treinamento-professor.php` (rotas de biblioteca, gerência e vínculo; **não** `treinamento.php`), `SDC/routes/web.php`, `SDC/routes/console.php`; `SDC/app/Modules/Treinamento/TreinamentoServiceProvider.php` (alias); `SDC/app/Http/Controllers/Api/MetricsController.php`; `SDC/.env.example`. `config/octane.php` **não** muda (64 MB desde a Fase 1).

**Infra (`SDC/docker/`)**: `Dockerfile.queue`, `queue/entrypoint.sh`, `supervisor/videos-worker.conf` (novo), `jenkins/stack.app.onpremise.yml`, `jenkins/Jenkinsfile.onprem`, `compose.homolog.yml`, `caddy/Caddyfile`, `caddy/Caddyfile.homolog`.

**Frontend (`SDC/resources/js/`)**: `Composables/treinamento/useUploadEmPartes.js`, `Composables/treinamento/useProgressoVideo.js`, `Components/Organisms/Treinamento/Conteudo/UploaderVideo.vue`, `Components/Organisms/Treinamento/Conteudo/SeletorVideo.vue`, `Components/Organisms/Treinamento/Sala/PlayerVideo.vue` (novos); `Pages/Treinamento/Professor/Conteudo.vue`, `Templates/Treinamento/SalaDeAulaTemplate.vue`, `package.json` (alterados).

**Testes (locais):** `tests/Feature/Treinamento/Ead/Video/{SchemaVideoTest,ProgressoVideoTest,VideoBibliotecaTest,VideoUploadTest,ProcessarVideoJobTest,VideoAuthTest,LimpezaVideosTest}.php`, reutilizando `EadTestCase` da Fase 1.

---

### Task 1: Schema, config, enums, models e progresso por tipo

**Files:**
- Modify: `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php`, `Enums/TipoItem.php`, `Models/ModuloItem.php`, `Services/ProgressoService.php`, `Requests/RegistrarProgressoRequest.php`, `SDC/.env.example`
- Create: `SDC/config/treinamento.php`, `Enums/VideoStatus.php`, `Models/Video.php`, `Models/VideoUpload.php`, `Support/CaminhosVideo.php`
- Test (local): `tests/Feature/Treinamento/Ead/Video/SchemaVideoTest.php`, `tests/Feature/Treinamento/Ead/Video/ProgressoVideoTest.php`
- `config/octane.php` **não** entra: já tem `package_max_length` de 64 MB desde a Fase 1.

**Interfaces:**
- Consumes: `EadTestCase` (helpers `criarCurso`, `criarPasta`, `criarItem`, `inscrever`), `ProgressoService::registrarPosicao(Inscricao, ModuloItem, int): ProgressoItem`, `TrilhaService::itemLiberado()` (Fase 1).
- Produces (DDL, schema `treinamento`, método `criarVideo()` idempotente no fim de `up()`):
  - `videos` (nova): `id bigserial PK`, `titulo varchar(255) NOT NULL`, `user_id bigint NOT NULL` (dono; sem FK, índice `videos_user_id_fkidx`), `status varchar(20) NOT NULL DEFAULT 'SEM_VIDEO'` CHECK (`SEM_VIDEO`,`UPLOAD_ABERTO`,`ENVIADO`,`PROCESSANDO`,`PRONTO`,`FALHOU`) com índice, `original_path varchar(500)`, `tamanho_bytes bigint`, `hls_versao varchar(40)`, `duracao_segundos integer`, `largura integer`, `altura integer`, `processado_em timestamp(0)`, `erro text`, `tentativas smallint NOT NULL DEFAULT 0`, `created_at`, `updated_at`, `deleted_at timestamp(0)`.
  - `modulo_itens` ganha **só** `video_id bigint NULL REFERENCES treinamento.videos(id) ON DELETE RESTRICT` + índice `modulo_itens_video_id_idx`. Nenhuma outra coluna de vídeo em `modulo_itens`.
  - `video_uploads` (`id uuid PK`, `video_id bigint NOT NULL REFERENCES treinamento.videos(id) ON DELETE CASCADE`, `user_id bigint NOT NULL`, `nome_original varchar(255)`, `mime varchar(100)`, `tamanho_total bigint NOT NULL`, `tamanho_parte integer NOT NULL`, `total_partes integer NOT NULL`, `partes_recebidas integer NOT NULL DEFAULT 0`, `tamanho_recebido bigint NOT NULL DEFAULT 0`, `status varchar(20) NOT NULL DEFAULT 'ABERTO'` CHECK (`ABERTO`,`COMPLETO`,`CANCELADO`), `expira_em timestamp(0) NOT NULL`, timestamps); índices `video_uploads_user_id_fkidx` e único parcial `video_uploads_um_aberto_por_video ON (video_id) WHERE status = 'ABERTO'`.
- Produces (config `SDC/config/treinamento.php`, chave `video`): `disco => 'treinamentos'`, `tamanho_parte_bytes => (int) env('TREINAMENTO_VIDEO_PARTE_BYTES', 8388608)`, `tamanho_maximo_bytes => (int) env('TREINAMENTO_VIDEO_MAX_BYTES', 2147483648)`, `mimes => ['video/mp4','video/quicktime','video/x-matroska']`, `upload_expira_horas => 24`, `manter_original => (bool) env('TREINAMENTO_VIDEO_MANTER_ORIGINAL', false)`, `conexao_fila => 'redis-videos'`, `fila => 'videos'`, `auth_cache_segundos => 300`, `servir_pelo_app => (bool) env('TREINAMENTO_VIDEO_SERVIR_PELO_APP', false)`, `log_ffmpeg_dias => 30`, `ffmpeg => ['binario' => env('FFMPEG_BIN','ffmpeg'), 'ffprobe' => env('FFPROBE_BIN','ffprobe'), 'nice' => 10, 'threads' => 2, 'preset' => 'veryfast', 'crf' => 23, 'hls_time' => 6, 'audio_bitrate' => '128k']`, `renditions => ['480p' => ['largura'=>854,'altura'=>480,'bitrate'=>'800k','ativa'=>true], '720p' => ['largura'=>1280,'altura'=>720,'bitrate'=>'2500k','ativa'=>true], '1080p' => ['largura'=>1920,'altura'=>1080,'bitrate'=>'5000k','ativa'=>(bool) env('TREINAMENTO_VIDEO_1080P', false)]]`. As mesmas envs entram comentadas no `.env.example`.
- Produces (código):
  - `enum VideoStatus: string { SEM_VIDEO; UPLOAD_ABERTO; ENVIADO; PROCESSANDO; PRONTO; FALHOU; getLabel(): string; estaPronto(): bool; podeReprocessar(): bool /* FALHOU */; emProcessamento(): bool /* ENVIADO|PROCESSANDO */ }`.
  - `TipoItem::VIDEO = 'VIDEO'`, label "Vídeo"; `TipoItem::exigeArquivo(): bool` (`PDF` true, demais false), usado pelo `ConteudoCursoService::criarItem` para não exigir arquivo em `VIDEO`.
  - `Video` (`treinamento.videos`, `UsaConexaoDeTreinamento`, `SoftDeletes`, casts `status => VideoStatus`, `processado_em` datetime): `uploads(): HasMany`, `uploadAberto(): HasOne` (`VideoUpload` com `status = ABERTO`), `itens(): HasMany` (`ModuloItem` por `video_id`), `estaPronto(): bool` (status `PRONTO`), `temVersaoPublicada(): bool` (`hls_versao !== null`; é o que decide se o vídeo pode ser vinculado e assistido), `emUso(): bool` (existe item não excluído apontando).
  - `VideoUpload` (`treinamento.video_uploads`, `HasUuids`, `UsaConexaoDeTreinamento`, casts `expira_em` datetime): `video(): BelongsTo`, `estaAberto(): bool` (`ABERTO` e não expirado), `completo(): bool` (`partes_recebidas === total_partes && tamanho_recebido === tamanho_total`).
  - `ModuloItem`: `video(): BelongsTo`; `totalProgresso(): ?int` (`PDF` -> `total_paginas`; `VIDEO` -> `video?->duracao_segundos` só se `video?->estaPronto()`; demais `null`). Sem campos de vídeo próprios.
  - `CaminhosVideo` (estático, relativo ao disco `treinamentos`; todos recebem `int $videoId`): `partes(string $uploadId): string` = `uploads/{uploadId}`; `parte(string $uploadId, int $n): string`; `original(int $videoId, string $uuid): string` = `originais/{videoId}/{uuid}.mp4`; `hlsRaiz(int $videoId): string` = `hls/{videoId}`; `hlsTemporario(int $videoId, string $uuid): string` = `hls/{videoId}/.tmp-{uuid}`; `hlsVersao(int $videoId, string $versao): string`; `urlMaster(int $videoId, string $versao): string` = `/videos/{videoId}/{versao}/master.m3u8`; `logFfmpeg(int $videoId, string $versao): string` = `storage_path("logs/ffmpeg/{videoId}-{versao}.log")`.
  - `ProgressoService::registrarPosicao()` deixa de exigir `PDF`: usa `$item->totalProgresso()`; `null` lança `DomainException('Este item nao registra progresso por posicao.')` (controller devolve 422); o resto da regra da Fase 1 (max com a atual, corte no total, conclui ao atingir o total) vale para qualquer tipo. `RegistrarProgressoRequest`: `posicao` passa a `integer|min:0`.

- [ ] **Step 1: Escrever `SchemaVideoTest`**: `test_tabelas_videos_e_video_uploads_existem`; `test_modulo_itens_so_tem_video_id` (`hasColumn('treinamento.modulo_itens', 'video_id')` e `! hasColumn(..., 'video_status')`); `test_status_invalido_rejeitado` (`videos.status = 'X'` lança `QueryException`); `test_dois_uploads_abertos_no_mesmo_video_rejeitado` (segundo insert `ABERTO` lança `QueryException`; um `CANCELADO` + um `ABERTO` passa); `test_video_apontado_por_item_nao_pode_ser_apagado_no_banco` (`ON DELETE RESTRICT`).
- [ ] **Step 2: Escrever `ProgressoVideoTest`**: `test_video_pronto_registra_posicao_em_segundos` (item `VIDEO` com `video` `PRONTO` e `duracao_segundos = 600`; posição 120 -> `posicao 120`, `EM_ANDAMENTO`); `test_posicao_acima_da_duracao_e_cortada_e_conclui` (9999 -> 600, `CONCLUIDO`) (Review Focus 5); `test_posicao_menor_nao_regride` (300 depois 100 -> 300); `test_video_nao_pronto_nao_registra` (`PROCESSANDO` -> `DomainException`) (Review Focus 5); `test_item_sem_video_vinculado_nao_registra` (`DomainException`); `test_projeto_nao_registra_posicao` (`DomainException`); `test_pdf_continua_funcionando` (3 páginas, posição 3 -> `CONCLUIDO`).
- [ ] **Step 3: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/SchemaVideoTest.php` e `TEST tests/Feature/Treinamento/Ead/Video/ProgressoVideoTest.php`; FAIL (tabelas e `VideoStatus` inexistentes).
- [ ] **Step 4: Implementar** DDL (`criarVideo()` chamado no fim de `up()`), config, enums, models, `CaminhosVideo`, `ModuloItem`, `ProgressoService` e request conforme Interfaces.
- [ ] **Step 5: Aplicar no banco de dev** (migration idempotente, mesmo ritual da Fase 1):

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc_treinamento -c "DELETE FROM migrations WHERE migration = '2026_11_05_210000_create_treinamento_schemas_tables';"
bash .superpowers/sdd/2026-11-10-treinamento-ead-fase2-video/artisan.sh migrate --database=pgsql_treinamento --path=database/migrations/treinamento --force
```

- [ ] **Step 6: Rodar os dois testes**: PASS (5 + 7). Rodar também `TEST tests/Feature/Treinamento/Ead/TrilhaServiceTest.php` (Fase 1) para garantir que o progresso do PDF não mudou.
- [ ] **Step 7: Commit**

```bash
git add SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php SDC/config/treinamento.php SDC/.env.example SDC/app/Modules/Treinamento/Enums/VideoStatus.php SDC/app/Modules/Treinamento/Enums/TipoItem.php SDC/app/Modules/Treinamento/Models/Video.php SDC/app/Modules/Treinamento/Models/VideoUpload.php SDC/app/Modules/Treinamento/Models/ModuloItem.php SDC/app/Modules/Treinamento/Support/CaminhosVideo.php SDC/app/Modules/Treinamento/Services/ProgressoService.php SDC/app/Modules/Treinamento/Requests/RegistrarProgressoRequest.php
git commit -m "🗃️ db(treinamento): biblioteca de videos (schema e config), progresso por tipo de item"
```

---

### Task 2: Biblioteca, vínculo e upload em partes (backend do professor)

**Files:**
- Create: `Services/Video/VideoBibliotecaService.php`, `Services/Video/VideoUploadService.php`, `Http/Middleware/GerenciaVideo.php`, `Controllers/Professor/Video/{VideoIndexController,VideoStoreController,VideoUpdateController,ItemVideoVincularController,ItemVideoDesvincularController,UploadAbrirController,UploadParteController,UploadFinalizarController,UploadCancelarController}.php`, `Requests/Professor/Video/{SalvarVideoRequest,AbrirUploadRequest,ReceberParteRequest,VincularVideoRequest}.php`, `Jobs/ProcessarVideoJob.php` (só a casca: construtor, unicidade, fila; o `handle` vem na Task 3)
- Modify: `SDC/config/queue.php`, `SDC/routes/modules/treinamento-professor.php`, `TreinamentoServiceProvider` (alias `gerencia.video` onde o da Fase 1, `leciona.curso`, está registrado), `Services/ConteudoCursoService.php` (aceitar `tipo = VIDEO` sem arquivo, via `TipoItem::exigeArquivo()`), `Requests/Professor/SalvarItemRequest.php` (`tipo in:PDF,PROJETO,VIDEO`)
- Test (local): `tests/Feature/Treinamento/Ead/Video/VideoBibliotecaTest.php`, `tests/Feature/Treinamento/Ead/Video/VideoUploadTest.php`

**Interfaces:**
- Consumes: `LecionaCurso`, `ConteudoCursoService::garantirDoCurso()` (Fase 1); `Video`, `VideoUpload`, `CaminhosVideo`, `config('treinamento.video.*')` (Task 1).
- Produces:
  - `config/queue.php`: conexão `'redis-videos' => ['driver' => 'redis', 'connection' => env('REDIS_QUEUE_CONNECTION', 'default'), 'queue' => 'videos', 'retry_after' => 3700, 'block_for' => 5, 'after_commit' => false]`.
  - `GerenciaVideo` (alias `gerencia.video`): lê o parâmetro `video` (model); 403 se `video->user_id !== auth()->id()` e `!can('treinamento.cursos.edit')`.
  - `VideoBibliotecaService`: `listar(?string $q, bool $somenteProntos): LengthAwarePaginator` (itens `{id, titulo, status, status_label, duracao_segundos, dono, em_uso}`); `criar(int $userId, string $titulo): Video`; `renomear(Video, string): Video`; `vincular(ModuloItem $item, Video $video): ModuloItem` (`DomainException` se `item->tipo !== VIDEO`, se o vídeo está excluído ou `!temVersaoPublicada()`); `desvincular(ModuloItem): ModuloItem` (`video_id = null`, **não** apaga o vídeo); `excluir(Video): void` (`DomainException("Video em uso por N itens")` se `emUso()`; a remoção de HLS/original entra na Task 3). Auditoria `RegistroDeAcao` em vincular, desvincular e excluir.
  - `VideoUploadService`:
    - `abrir(Video $video, int $userId, string $nome, int $tamanhoTotal, string $mime): VideoUpload`: `DomainException` se `tamanhoTotal > tamanho_maximo_bytes` ou `mime` fora da lista; cancela upload `ABERTO` anterior do vídeo (apaga partes); cria com `tamanho_parte` da config, `total_partes = (int) ceil(tamanhoTotal / tamanho_parte)`, `expira_em = now()->addHours(24)`; vídeo passa a `UPLOAD_ABERTO` (se estava `SEM_VIDEO` ou `FALHOU`; se `PRONTO`, mantém `PRONTO` e `hls_versao`, §8.4).
    - `receberParte(VideoUpload $u, int $n, UploadedFile $parte): VideoUpload`: `DomainException` se `!estaAberto()`, se `n < 1 || n > total_partes`, ou se o tamanho da parte difere do esperado (`tamanho_parte`, ou `tamanho_total - tamanho_parte*(total_partes-1)` para a última); grava `storeAs(CaminhosVideo::partes($u->id), "{$n}.part", 'treinamentos')`; recalcula `partes_recebidas` e `tamanho_recebido` **contando os arquivos existentes** (reenvio não duplica).
    - `finalizar(VideoUpload $u): Video`: se `status === COMPLETO` retorna o vídeo sem despachar de novo; `DomainException` se `!completo()`; marca `COMPLETO`, vídeo `ENVIADO` sempre, mantendo `hls_versao` (spec §8.4: a versão atual segue servida), com `erro = null`; chamada repetida com o vídeo ainda `ENVIADO` redespacha (o job é único por vídeo), despacha `ProcessarVideoJob::dispatch($video->id, $u->id)`; `RegistroDeAcao::registrarNoProtocolo(...)` de envio para processamento.
    - `cancelar(VideoUpload $u): void`: apaga `uploads/{id}` do disco, marca `CANCELADO`, vídeo volta a `SEM_VIDEO` se não tem `hls_versao`, senão `PRONTO`.
  - `ProcessarVideoJob implements ShouldQueue, ShouldBeUnique`: `__construct(public int $videoId, public string $uploadId)`; `$tries = 3`, `$timeout = 3600`, `$uniqueFor = 7200`, `uniqueId(): string` = `"video-{$this->videoId}"`, `backoff(): array` = `[120]`; `onConnection(config('treinamento.video.conexao_fila'))->onQueue(config('treinamento.video.fila'))` no construtor.
  - Rotas em `routes/modules/treinamento-professor.php`, grupo professor (`can:treinamento.professor.acessar`), nomes `treinamentos.professor.videos.*`:
    - Biblioteca (sem `{treinamento}`): `GET /videos` -> `videos.index` (JSON paginado, filtros `q`, `prontos=1`); `POST /videos` -> `videos.store` (`titulo required|string|max:255`; cria com `user_id = auth()->id()`).
    - Gerência (middleware `gerencia.video`): `PUT /videos/{video}` -> `videos.update` (`titulo`); `POST /videos/{video}/upload` -> `videos.upload.abrir` (JSON `{upload_id, tamanho_parte, total_partes, expira_em}`); `PUT /videos/{video}/upload/{upload}/partes/{n}` -> `videos.upload.parte` (multipart campo `parte`; JSON `{partes_recebidas, tamanho_recebido}`); `POST /videos/{video}/upload/{upload}/finalizar` -> `videos.upload.finalizar` (JSON `{status}`); `DELETE /videos/{video}/upload/{upload}` -> `videos.upload.cancelar` (204). `{upload}` de outro vídeo -> 404 (`firstOrFail` por `video_id`). `DomainException` -> 422 com `message`. (`DELETE /videos/{video}`, `GET .../status` e `POST .../reprocessar` entram na Task 3.)
    - Vínculo (grupo `leciona.curso`): `PUT /{treinamento}/itens/{item}/video` (body `video_id`) -> `treinamentos.professor.itens.video.vincular`; `DELETE /{treinamento}/itens/{item}/video` -> `treinamentos.professor.itens.video.desvincular` (204). `{item}` de outro curso -> 404 (`garantirDoCurso`); `video_id` inexistente/excluído -> 422.
  - `AbrirUploadRequest`: `nome_original required|string|max:255`, `tamanho_total required|integer|min:1`, `mime required|string`. `ReceberParteRequest`: `parte required|file`. `SalvarVideoRequest`: `titulo required|string|max:255`. `VincularVideoRequest`: `video_id required|integer`.

- [ ] **Step 1: Escrever `VideoBibliotecaTest`**: `test_usuario_sem_professor_acessar_recebe_403_na_biblioteca`; `test_biblioteca_lista_e_filtra_por_q_e_prontos` (campos `em_uso`, `dono`); `test_criar_video_define_dono_e_status_sem_video`; `test_gerencia_so_dono_ou_cursos_edit` (outro professor -> 403 em `PUT /videos/{video}`; dono -> 200; usuário com `treinamento.cursos.edit` -> 200) (Review Focus 1); `test_vincular_video_pronto_ao_item`; `test_vincular_video_nao_pronto_da_422`; `test_vincular_em_item_nao_video_da_422`; `test_vincular_video_excluido_da_422`; `test_desvincular_nao_apaga_o_video` (Review Focus 7).
- [ ] **Step 2: Escrever `VideoUploadTest`** (com `Storage::fake('treinamentos')` e `Queue::fake()`): `test_abrir_calcula_partes_e_marca_video` (tamanho 20 MB -> `total_partes 3`, `tamanho_parte 8388608`, vídeo `UPLOAD_ABERTO`); `test_abrir_recusa_mime_e_tamanho` (`video/avi` -> 422; 3 GB -> 422); `test_abrir_cancela_sessao_anterior`; `test_parte_fora_da_faixa_nao_grava` (`n = 4` em 3 partes -> 422, `Storage::assertMissing`) (Review Focus 1); `test_parte_com_tamanho_errado_nao_grava` (Review Focus 1); `test_reenviar_parte_nao_duplica_contagem`; `test_upload_de_outro_video_na_url_da_404` (Review Focus 1); `test_finalizar_com_parte_faltando_da_422_sem_despachar` (`Queue::assertNothingPushed`) (Review Focus 2); `test_finalizar_despacha_uma_vez_na_fila_videos` (3 partes, finalizar 2x -> `Queue::assertPushedOn('videos', ProcessarVideoJob::class)` e `assertPushed(..., 1)`, vídeo `ENVIADO`) (Review Focus 2); `test_cancelar_apaga_partes_e_volta_sem_video`; `test_quem_nao_e_dono_recebe_403_no_upload` (Review Focus 1).
- [ ] **Step 3: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/VideoBibliotecaTest.php` e `TEST tests/Feature/Treinamento/Ead/Video/VideoUploadTest.php`.
- [ ] **Step 4: Implementar** conforme Interfaces (controllers invocáveis, finos; a transação de `finalizar` via `ConexaoTreinamento::transacao()` e o `dispatch` depois do commit; alias `gerencia.video` no `TreinamentoServiceProvider`).
- [ ] **Step 5: Rodar**: PASS (9 + 11).
- [ ] **Step 6: Commit**

```bash
git add SDC/config/queue.php SDC/routes/modules/treinamento-professor.php SDC/app/Modules/Treinamento/TreinamentoServiceProvider.php SDC/app/Modules/Treinamento/Http/Middleware/GerenciaVideo.php SDC/app/Modules/Treinamento/Services/Video/VideoBibliotecaService.php SDC/app/Modules/Treinamento/Services/Video/VideoUploadService.php SDC/app/Modules/Treinamento/Controllers/Professor/Video SDC/app/Modules/Treinamento/Requests/Professor/Video SDC/app/Modules/Treinamento/Requests/Professor/SalvarItemRequest.php SDC/app/Modules/Treinamento/Services/ConteudoCursoService.php SDC/app/Modules/Treinamento/Jobs/ProcessarVideoJob.php
git commit -m "✨ feat(treinamento): biblioteca de videos, vinculo no item e upload em partes"
```

---

### Task 3: Processamento (montagem, ffprobe, FFmpeg, publicação por versão), status e exclusão

**Files:**
- Create: `Services/Video/MontadorDeOriginal.php`, `Services/Video/Ffmpeg.php`, `Services/Video/PublicadorHls.php`, `Services/Video/ProcessadorDeVideo.php`, `Services/Video/VideoMetrics.php` (só `registrarProcessamento`), `DTOs/SondagemVideo.php`, `Exceptions/FalhaDeVideo.php`, `Controllers/Professor/Video/{StatusController,ReprocessarController,VideoDestroyController}.php`
- Modify: `Jobs/ProcessarVideoJob.php` (`handle`, `failed`), `Services/Video/VideoBibliotecaService.php` (`excluir` remove HLS/original), `SDC/routes/modules/treinamento-professor.php`, `SDC/config/logging.php` (canal `treinamento`: `daily`, `storage/logs/treinamento.log`, `days 14`)
- Test (local): `tests/Feature/Treinamento/Ead/Video/ProcessarVideoJobTest.php`, `tests/Feature/Treinamento/Ead/Video/VideoBibliotecaTest.php` (+2 testes)

**Interfaces:**
- Consumes: `Video`, `VideoUpload`, `CaminhosVideo`, `VideoStatus`, config (Task 1); `VideoUploadService::cancelar()`, `VideoBibliotecaService` (Task 2).
- Produces:
  - `final readonly class SondagemVideo { __construct(public int $duracaoSegundos, public int $largura, public int $altura) }`.
  - `class FalhaDeVideo extends \RuntimeException { __construct(string $mensagem, public readonly bool $definitiva = false) }` (`definitiva` = não vale tentar de novo: arquivo inválido).
  - `MontadorDeOriginal::montar(VideoUpload $u): string` (caminho relativo do original, `originais/{videoId}/{uuid}.mp4`): se já existe com `size === tamanho_total`, retorna; senão concatena `1..total_partes` por stream (`fopen`/`stream_copy_to_stream`, 1 MiB por vez) em arquivo `.tmp` e renomeia; confere o tamanho (`FalhaDeVideo` definitiva se diferir); apaga `uploads/{id}`.
  - `Ffmpeg::sondar(string $absoluto): SondagemVideo` (`Process::run([ffprobe, '-v','error','-show_streams','-show_format','-of','json', $absoluto])`; sem stream `codec_type = video` -> `FalhaDeVideo` definitiva; `duracao = (int) ceil(format.duration)`).
  - `Ffmpeg::transcodificar(string $origemAbs, string $destinoAbs, SondagemVideo $s, string $logAbs): void`: monta **um** comando `nice -n {nice} ffmpeg -y -i origem -threads {threads} -filter_complex "[0:v]split=N[v1]..[vN];[v1]scale=w=854:h=480[v1out]..." -map [v1out] -c:v:0 libx264 -preset {preset} -crf {crf} -maxrate:v:0 800k -bufsize:v:0 1600k -pix_fmt yuv420p -map a:0? -c:a aac -b:a 128k ... -f hls -hls_time {hls_time} -hls_playlist_type vod -hls_segment_type mpegts -hls_segment_filename "destino/%v/%05d.ts" -master_pl_name master.m3u8 -var_stream_map "v:0,a:0,name:480p v:1,a:1,name:720p" destino/%v/index.m3u8`, só com as renditions `ativa` cuja `altura <= $s->altura` (sempre pelo menos a menor); `Process::timeout(3500)`; stderr gravado em `$logAbs`; exit code != 0 -> `FalhaDeVideo` (não definitiva) com os últimos 1000 caracteres do stderr. `comando(...): array` é público para o teste inspecionar.
  - `PublicadorHls::publicar(Video $video, string $tmpRelativo): string` (gera `versao = now()->format('YmdHis')`, `rename` do diretório temporário para `hlsVersao($video->id, $versao)`, guarda a `hls_versao` anterior em `Cache::put("video_versao_anterior:{$video->id}", $anterior, 600)` para a janela de troca da Task 4, retorna `versao`); `removerVersao(Video $video, string $versao): void`; `removerTemporarios(Video $video): void` (apaga `hls/{video}/.tmp-*`); `removerTudo(Video $video): void`.
  - `ProcessadorDeVideo::processar(Video $video, VideoUpload $upload): void`: vídeo -> `PROCESSANDO` (guardando `hls_versao` anterior em variável); `removerTemporarios`; `montar`; `sondar`; `transcodificar` para `hlsTemporario($video->id, uuid)`; `publicar`; atualiza o vídeo `PRONTO`, `hls_versao`, `duracao_segundos`, `largura`, `altura`, `tamanho_bytes`, `processado_em = now()`, `erro = null`, `tentativas = 0`; remove versão anterior e, se `!manter_original`, o original; chama `VideoMetrics::registrarProcessamento(bool $sucesso, int $segundos, int $bytesHls)` (completada na Task 8). Em `FalhaDeVideo`: vídeo `FALHOU` se não tinha versão, senão volta a `PRONTO` com a versão anterior; `erro = mensagem`, `tentativas++`; relança se `!definitiva`. A troca vale para todos os cursos que usam o vídeo (nada a atualizar nos itens).
  - `ProcessarVideoJob::handle(ProcessadorDeVideo $p)`: carrega vídeo (não excluído) e upload (`COMPLETO`); se não existem, retorna; chama `processar`; captura `FalhaDeVideo` definitiva e chama `$this->fail($e)`. `failed(\Throwable $e)`: garante `FALHOU`/`PRONTO` + `erro` (caso de timeout/kill).
  - `VideoBibliotecaService::excluir(Video)`: após a checagem de `emUso()`, `PublicadorHls::removerTudo`, apaga original e upload aberto e faz `delete()` (soft delete).
  - Rotas (gerência, middleware `gerencia.video`, nomes `treinamentos.professor.videos.*`): `GET /videos/{video}/status` -> `status` (JSON `{status, status_label, duracao_segundos, hls_versao, erro, processado_em, upload: {id, partes_recebidas, total_partes} | null}`); `POST /videos/{video}/reprocessar` -> `reprocessar` (só `podeReprocessar()`, senão 422; redespacha o job com o último upload `COMPLETO`; auditoria); `DELETE /videos/{video}` -> `destroy` (204; 422 "Video em uso por N itens" se `emUso()`; auditoria).

- [ ] **Step 1: Escrever `ProcessarVideoJobTest`** (`Storage::fake('treinamentos')`; `Process::fake([...])` com `'*ffprobe*' => Process::result(json com stream de vídeo 1280x720 e duration 95.4)` e `'*ffmpeg*' => Process::result('')`; antes de rodar o job, o teste cria no disco fake o que o FFmpeg criaria, em `hls/{video}/.tmp-*/master.m3u8`, usando um `Process::fake` com callback que escreve o arquivo): `test_fluxo_feliz_publica_versao_e_limpa` (vídeo `PRONTO`, `duracao_segundos 96`, `master.m3u8` em `hls/{video}/{versao}/`, `uploads/{id}` ausente, original ausente); `test_manter_original_preserva_mp4`; `test_ffprobe_sem_video_falha_definitivo` (`FALHOU`, `erro` preenchido, `Process::assertNotRan('*ffmpeg*')`); `test_ffmpeg_com_erro_relanca_para_retry` (exit 1 -> `FalhaDeVideo` propagada, vídeo `FALHOU`, `tentativas 1`); `test_reexecucao_reaproveita_original_e_limpa_tmp` (original já montado + `.tmp-x` sobrando -> não remonta, `.tmp-x` some, publica) (Review Focus 4); `test_nova_versao_que_falha_mantem_a_anterior` (vídeo `PRONTO` com `hls_versao v1`; ffmpeg falha -> `PRONTO`, `hls_versao v1`, `erro` preenchido) (Review Focus 4); `test_nova_versao_ok_remove_a_anterior_e_vale_para_todos_os_itens` (dois itens de cursos distintos apontando para o vídeo veem a mesma `hls_versao` nova); `test_comando_ffmpeg_pula_rendition_maior_que_o_original` (original 854x480 -> `var_stream_map` só com `480p`); `test_status_e_reprocessar` (status JSON; reprocessar em `PRONTO` -> 422; em `FALHOU` -> job despachado). Em `VideoBibliotecaTest`: `test_excluir_video_em_uso_da_422_e_nada_e_removido` (Review Focus 7); `test_excluir_video_livre_remove_hls_e_registro`.
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/ProcessarVideoJobTest.php`.
- [ ] **Step 3: Implementar** conforme Interfaces; log estruturado por etapa no canal `treinamento` (`video_id`, `stage`, `duracao_ms`, `bytes`).
- [ ] **Step 4: Rodar**: PASS (9 em `ProcessarVideoJobTest`; `VideoBibliotecaTest` com 11).
- [ ] **Step 5: Commit**

```bash
git add SDC/config/logging.php SDC/routes/modules/treinamento-professor.php SDC/app/Modules/Treinamento/Services/Video SDC/app/Modules/Treinamento/DTOs/SondagemVideo.php SDC/app/Modules/Treinamento/Exceptions/FalhaDeVideo.php SDC/app/Modules/Treinamento/Jobs/ProcessarVideoJob.php SDC/app/Modules/Treinamento/Controllers/Professor/Video
git commit -m "✨ feat(treinamento): processamento de video para HLS na fila com publicacao por versao"
```

---

### Task 4: Autorização de entrega (`/videos/auth`), rota de contingência de dev e estado do item

**Files:**
- Create: `Services/Video/AutorizacaoVideoService.php`, `Controllers/VideoAuthController.php`, `Controllers/VideoArquivoDevController.php`
- Modify: `DTOs/EstadoItem.php`, `Services/TrilhaService.php`, `SDC/routes/web.php` (fora do grupo `auth`, dentro de `web`)
- Test (local): `tests/Feature/Treinamento/Ead/Video/VideoAuthTest.php`

**Interfaces:**
- Consumes: `TrilhaService::itemLiberado(Inscricao, ModuloItem)`, `ProfessorService::leciona(int, Treinamento)`, `InscricaoDoAluno` (Fase 1); `CaminhosVideo::urlMaster()`, `Video`, `VideoStatus` (Task 1).
- Produces:
  - `AutorizacaoVideoService::autorizarUri(string $uri): bool`: casa `#^/videos/(\d+)/([A-Za-z0-9_-]{1,40})/#`; senão `false`. Resolve o `Video` (não excluído, com versão publicada: `temVersaoPublicada()`, em qualquer `status`), aceita `versao === hls_versao` ou `versao === Cache::get("video_versao_anterior:{videoId}")` (gravado pelo `PublicadorHls` na Task 3); delega a `podeAssistir`.
  - `AutorizacaoVideoService::podeAssistir(Video $video, ?Authenticatable $servidor, ?Authenticatable $cidadao): bool`: libera o dono do vídeo; `can('treinamento.cursos.edit')`; professor de **qualquer** curso com item (não excluído) apontando para o vídeo; aluno (`servidor` em curso `SERVIDORES`, `cidadao` em `CIDADAOS`) com `Inscricao` `APROVADA` (consulta direta por `inscrito_type`/`inscrito_id`, sem o `InscricaoDoAluno`, que aborta) em curso EAD publicado com item apontando para o vídeo e `itemLiberado(inscricao, item)` verdadeiro para **pelo menos um** desses itens; senão `false`. O resultado positivo é cacheado por `auth_cache_segundos` em `video_auth:{session()->getId()}:{videoId}:{versao}`; o negativo **não** é cacheado.
  - `VideoAuthController` (`GET /videos/auth`, nome `videos.auth`, middleware só `web`): lê `X-Forwarded-Uri` (fallback `X-Original-URI`), responde `204` ou `403` sem corpo, sem Inertia. O parser aceita o caminho com ou sem o prefixo `/videos` (o Caddy envia o caminho original, `{http.request.orig_uri}`).
  - `VideoArquivoDevController` (`GET /videos/{videoId}/{versao}/{arquivo}` com `->where('arquivo', '[A-Za-z0-9_\-]+(/[A-Za-z0-9_\-]+)*\.(m3u8|ts)')`, nome `videos.arquivo-dev`): `abort(404)` se `!config('treinamento.video.servir_pelo_app') || app()->isProduction()`; `403` se `!autorizarUri(request()->getPathInfo())`; senão `response()->file()` do caminho no disco com `Content-Type` `application/vnd.apple.mpegurl` ou `video/MP2T`. Registrada **antes** de `videos.auth` não conflita (`auth` não casa o regex por falta de extensão).
  - `EstadoItem` ganha `public ?array $video = null` (`['status' => string, 'status_label' => string, 'duracao_segundos' => ?int, 'url_master' => ?string]`, vindo de `item->video`; `url_master` sempre que o vídeo tem versão publicada (`hls_versao`), inclusive durante a troca de arquivo), preenchido no `TrilhaService::estado()` e `estadoPrevia()` para itens `VIDEO` (item sem vídeo vinculado -> `video` com `status SEM_VIDEO`); `toArray()` inclui a chave `video`.

- [ ] **Step 1: Escrever `VideoAuthTest`**: `test_aluno_aprovado_com_item_liberado_recebe_204` (servidor, curso `SERVIDORES`, GET `/videos/auth` com header `X-Forwarded-Uri: /videos/{video}/{versao}/720p/00001.ts`); `test_cidadao_aprovado_recebe_204_pelo_guard_cidadao`; `test_sem_login_recebe_403`; `test_inscricao_pendente_recebe_403`; `test_item_de_pasta_travada_recebe_403` (Review Focus 3); `test_cidadao_em_curso_de_servidores_recebe_403` (Review Focus 3); `test_versao_desconhecida_recebe_403_e_anterior_recebe_204` (Review Focus 3); `test_uri_malformada_recebe_403` (`/videos/../x`, `/videos/abc/v/`) (Review Focus 3); `test_video_excluido_ou_sem_versao_publicada_recebe_403`; `test_professor_de_qualquer_curso_que_usa_o_video_recebe_204`; `test_video_compartilhado_por_dois_cursos_segue_a_trilha_de_cada_um` (dois cursos apontam para o mesmo vídeo; aluno do curso A com item travado em A -> 403 mesmo com o item liberado em B; aluno do curso B com item liberado -> 204; aluno do curso A com item liberado -> 204) (Review Focus 6); `test_dono_do_video_sem_curso_recebe_204`; `test_decisao_positiva_vai_para_o_cache_e_negativa_nao` (`Cache::spy()`: 204 -> `put` chamado uma vez; segunda chamada não consulta `TrilhaService` (mock `shouldReceive('itemLiberado')->once()`); 403 -> `put` nunca chamado); `test_rota_dev_serve_m3u8_so_fora_de_producao` (`config(['treinamento.video.servir_pelo_app' => true])`, `app()->detectEnvironment(fn () => 'local')` -> 200 com content-type do m3u8; `app()->detectEnvironment(fn () => 'production')` -> 404); `test_estado_do_item_video_traz_url_master_quando_pronto` (`estado.pastas[0].itens[0].video.url_master === "/videos/{video}/{versao}/master.m3u8"`; vídeo `PROCESSANDO` sem versão -> `url_master null`; `PROCESSANDO` com versão anterior -> `url_master` da versão anterior).
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/VideoAuthTest.php`.
- [ ] **Step 3: Implementar** conforme Interfaces. As duas rotas entram em `routes/web.php` logo após o `require` de `treinamento-portal.php` (mesma região: dentro de `web`, fora de `auth`).
- [ ] **Step 4: Rodar**: PASS (14). Rodar `TEST tests/Feature/Treinamento/Ead/SalaEndpointsTest.php` (Fase 1) para conferir que `toArray()` com a chave nova não quebrou nada.
- [ ] **Step 5: Commit**

```bash
git add SDC/routes/web.php SDC/app/Modules/Treinamento/Services/Video/AutorizacaoVideoService.php SDC/app/Modules/Treinamento/Controllers/VideoAuthController.php SDC/app/Modules/Treinamento/Controllers/VideoArquivoDevController.php SDC/app/Modules/Treinamento/DTOs/EstadoItem.php SDC/app/Modules/Treinamento/Services/TrilhaService.php
git commit -m "🔒 security(treinamento): autorizacao de video para o Caddy e estado do item na trilha"
```

---

### Task 5: Infra: FFmpeg na imagem, serviço `queue-videos`, Caddy e Jenkins

**Files:**
- Create: `SDC/docker/supervisor/videos-worker.conf`
- Modify: `SDC/docker/Dockerfile.queue`, `SDC/docker/queue/entrypoint.sh`, `SDC/docker/jenkins/stack.app.onpremise.yml`, `SDC/docker/jenkins/Jenkinsfile.onprem:43,320-352`, `SDC/docker/compose.homolog.yml`, `SDC/docker/caddy/Caddyfile`, `SDC/docker/caddy/Caddyfile.homolog`

**Interfaces:**
- Produces:
  - `Dockerfile.queue`: `ffmpeg` na lista do primeiro `apk add --no-cache`; `COPY docker/supervisor/videos-worker.conf /etc/supervisor/conf.d/videos-worker.conf`.
  - `videos-worker.conf` (cópia do `medalhao-worker.conf` com): `command=php /var/www/artisan queue:work redis-videos --queue=videos --sleep=5 --tries=3 --max-time=7200 --timeout=3600`, `numprocs=1`, `stopwaitsecs=900`, `stdout_logfile=/var/log/supervisor/videos-worker.log`, comentário explicando por que é fila e serviço próprios.
  - `queue/entrypoint.sh`: variável `SUPERVISOR_PROFILE` (`default` quando ausente). `videos`: remove de `/etc/supervisor/conf.d/` tudo exceto `videos-worker.conf`. `default`: remove `videos-worker.conf`. Comentário de 3 linhas dizendo que a imagem é uma e o perfil escolhe o conjunto de workers.
  - `stack.app.onpremise.yml`: serviço `queue-videos` = cópia de `queue` com `hostname: queue-videos`, `SUPERVISOR_PROFILE: videos`, `stop_grace_period: 16m`, `resources.limits` `cpus: "1.5"`, `memory: 2G`, `reservations` `cpus: "0.25"`, `memory: 512M`, mesmo `app_storage` e rede `sdc_internal`; comentário de orçamento atualizado no cabeçalho (`7 de 8 vCPUs`). Serviço `caddy` ganha `- app_storage:/srv/storage:ro` e `environment: VIDEOS_HLS_ROOT: /srv/storage/treinamentos/hls`. Comentário de 2 linhas: se o R760 usar `ANEXOS_ROOT`, trocar o volume por `${ANEXOS_HOST_PATH}/TREINAMENTO:/srv/storage/treinamentos:ro` (§11).
  - `Caddyfile` e `Caddyfile.homolog`, antes do `reverse_proxy` principal:

```
# Videos do EAD: o Caddy entrega do volume, o Octane so autoriza (204/403).
handle_path /videos/* {
	forward_auth app:8000 {
		uri /videos/auth
	}
	root * {$VIDEOS_HLS_ROOT:/srv/storage/treinamentos/hls}
	header Cache-Control "private, max-age=3600"
	file_server
}
```

  No homolog o `forward_auth` usa o mesmo upstream dinâmico do app (`forward_auth { dynamic a { name app port 8000 } uri /videos/auth }`), e o serviço `caddy` do `compose.homolog.yml` monta `homolog_storage:/srv/storage:ro`.
  - `compose.homolog.yml`: serviço `queue_videos` no padrão de `queue_medalhao`, imagem construída de `Dockerfile.queue` (`build: {context: .., dockerfile: docker/Dockerfile.queue}`, `image: newsdc-queue-dev:latest`), `SUPERVISOR_PROFILE: videos`, `cpus: 1.5`, `mem_limit: 2g`, mesmos volumes e envs de fila; healthcheck `pgrep -f 'queue:work'`.
  - `Jenkinsfile.onprem`: `QUEUE_VIDEOS_SERVICE = "sdc_queue-videos"` ao lado das outras constantes; no estágio `Deploy Rolling Update`, um `docker service update --image ${QUEUE_IMAGE}:${IMAGE_TAG} --update-order stop-first --with-registry-auth --detach=false ${QUEUE_VIDEOS_SERVICE}` após o do `queue`; rollback espelhado.

- [ ] **Step 1: Build da imagem de queue** (em dev, com proxy): `cd SDC && docker build -f docker/Dockerfile.queue -t newsdc-queue-dev:latest .`; esperado sucesso.
- [ ] **Step 2: Conferir o binário e o perfil**: `docker run --rm -e SUPERVISOR_PROFILE=videos --entrypoint sh newsdc-queue-dev:latest -c 'ffmpeg -version | head -1; ffprobe -version | head -1; /entrypoint.sh ls /etc/supervisor/conf.d'`; esperado as duas versões e **só** `videos-worker.conf` listado. Repetir sem a variável: lista sem `videos-worker.conf`.
- [ ] **Step 3: Validar o Caddyfile**: `docker run --rm -v "$PWD/docker/caddy/Caddyfile.homolog:/etc/caddy/Caddyfile:ro" caddy:2-alpine caddy validate --config /etc/caddy/Caddyfile`; esperado `Valid configuration`. Mesmo comando para `Caddyfile` com `-e APP_DOMAIN=localhost`.
- [ ] **Step 4: Validar o stack**: `docker compose -f docker/jenkins/stack.app.onpremise.yml config > /dev/null` (com as envs exportadas de um `.env` de exemplo); esperado sem erro.
- [ ] **Step 5: Subir o worker em homolog**: `docker compose -f docker/compose.homolog.yml up -d --build queue_videos caddy`; `docker logs newsdc_homolog_queue_videos` mostra `videos-worker_00 entered RUNNING`; `curl -s -o /dev/null -w '%{http_code}' http://localhost:8090/videos/1/x/master.m3u8` responde `403` (sem sessão).
- [ ] **Step 6: Commit**

```bash
git add SDC/docker/Dockerfile.queue SDC/docker/queue/entrypoint.sh SDC/docker/supervisor/videos-worker.conf SDC/docker/jenkins/stack.app.onpremise.yml SDC/docker/jenkins/Jenkinsfile.onprem SDC/docker/compose.homolog.yml SDC/docker/caddy/Caddyfile SDC/docker/caddy/Caddyfile.homolog
git commit -m "🐳 docker(treinamento): worker de video com FFmpeg e entrega HLS pelo Caddy"
```

---

### Task 6: Frontend do professor: biblioteca, seletor e uploader em partes

**Files:**
- Create: `SDC/resources/js/Composables/treinamento/useUploadEmPartes.js`, `SDC/resources/js/Components/Organisms/Treinamento/Conteudo/UploaderVideo.vue`, `SDC/resources/js/Components/Organisms/Treinamento/Conteudo/SeletorVideo.vue`
- Modify: `SDC/resources/js/Pages/Treinamento/Professor/Conteudo.vue`, `Controllers/Professor/ConteudoController.php` (itens `VIDEO` trazem `video` `{id, titulo, status, status_label, duracao_segundos, erro, pode_gerenciar, upload_aberto: {id, partes_recebidas, total_partes}|null}|null`)

**Interfaces:**
- Consumes: rotas `treinamentos.professor.videos.*` e `treinamentos.professor.itens.video.*` (Tasks 2 e 3), via ziggy `route()`.
- Produces:
  - `useUploadEmPartes({ videoId })` -> `{ enviar(file: File): Promise<void>, cancelar(): Promise<void>, progresso: Ref<number> /* 0..100 */, estado: Ref<'ocioso'|'enviando'|'finalizando'|'enviado'|'erro'|'cancelado'>, erro: Ref<string|null> }`. `enviar`: POST `videos.upload.abrir` (`nome_original`, `tamanho_total`, `mime` = `file.type`), depois `PUT videos.upload.parte` para `n = 1..total_partes` **sequencialmente** com `file.slice((n-1)*tamanho_parte, n*tamanho_parte)` em `FormData` (campo `parte`), até 3 tentativas por parte com espera 1 s, 2 s, 4 s; depois POST `videos.upload.finalizar`. Erro de validação (422) não é retentado. Usa `axios` (já disponível via `window.axios`).
  - `UploaderVideo.vue` props `{ video: Object }`, emite `atualizado(video)`: opera sobre o **vídeo**: zona de arrastar/escolher (`accept="video/mp4,video/quicktime,video/x-matroska"`), valida tamanho no cliente (`> 2 GiB` mostra erro sem enviar), barra de progresso do envio; quando `video.status` é `ENVIADO`/`PROCESSANDO`, faz polling de `videos.status` a cada 5 s (`setInterval`, limpo no `onUnmounted`) e emite `atualizado` a cada resposta; em `PRONTO` mostra duração formatada e "Substituir arquivo" (atualiza todos os cursos que usam o vídeo; avisar quando `em_uso`); em `FALHOU` mostra `erro` e "Tentar de novo" (`reprocessar`) além de "Substituir". Só aparece para quem `pode_gerenciar`.
  - `SeletorVideo.vue` props `{ treinamentoId: Number, item: Object }`, emite `vinculado(item)`: modal com duas abas. **Biblioteca**: busca com debounce em `videos.index` (`q`, `prontos=1`), lista título/duração/dono/`em_uso`, "Usar este vídeo" -> `PUT itens.video.vincular`. **Enviar novo**: `POST videos.store` com `titulo` = título do item, abre `UploaderVideo` sobre o novo vídeo e, ao terminar o envio (finalizado), vincula o vídeo ao item (o item mostra "em preparação" até `PRONTO`).
  - `Conteudo.vue`: o modal de item ganha `tipo = VIDEO` (sem campo de arquivo); o card do item `VIDEO` mostra o vídeo vinculado (título, status, duração) com "Trocar vídeo" (abre `SeletorVideo`), "Desvincular" (`itens.video.desvincular`; não apaga o vídeo) e, para quem gerencia, o `UploaderVideo`; atualiza o item local nos eventos `atualizado`/`vinculado`.

- [ ] **Step 1: Implementar** composable e componentes conforme Interfaces; ajustar `ConteudoController` e `Conteudo.vue`.
- [ ] **Step 2: Build**: `cd SDC && npx vite build`; esperado sem erro.
- [ ] **Step 3: Smoke manual em dev** (Octane recarregado com `octane:reload`; `composer dump-autoload` no container antes, por causa das classes novas): como professor, criar item Vídeo, aba "Enviar novo" com um MP4 de ~50 MB; a barra sobe em 7 passos; aba Rede mostra PUTs de 8 MB e nenhum POST maior que isso; `video_uploads` fica `COMPLETO`; sem worker rodando, o vídeo fica em "Enviado"; subir `docker compose -f docker/compose.dev.yml run --rm -e SUPERVISOR_PROFILE=videos queue` (ou o worker equivalente do dev) e ver o vídeo passar a "Pronto" com a duração certa. Em outro curso, item Vídeo, aba "Biblioteca": o mesmo vídeo aparece, vincula e toca; professor que não é dono não vê "Substituir". Desligar a rede no meio de um envio e religar: a parte é retentada e o envio termina.
- [ ] **Step 4: Commit**

```bash
git add SDC/resources/js/Composables/treinamento/useUploadEmPartes.js SDC/resources/js/Components/Organisms/Treinamento/Conteudo/UploaderVideo.vue SDC/resources/js/Components/Organisms/Treinamento/Conteudo/SeletorVideo.vue SDC/resources/js/Pages/Treinamento/Professor/Conteudo.vue SDC/app/Modules/Treinamento/Controllers/Professor/ConteudoController.php
git commit -m "✨ feat(treinamento): biblioteca de videos e uploader em partes na area do professor"
```

---

### Task 7: Frontend do aluno: player HLS e progresso por tempo

**Files:**
- Create: `SDC/resources/js/Components/Organisms/Treinamento/Sala/PlayerVideo.vue`, `SDC/resources/js/Composables/treinamento/useProgressoVideo.js`
- Modify: `SDC/resources/js/Templates/Treinamento/SalaDeAulaTemplate.vue`, `SDC/package.json` (`hls.js@1.7.4`)

**Interfaces:**
- Consumes: `estado.pastas[].itens[].video` (`status`, `status_label`, `duracao_segundos`, `url_master`, vindo de `item->video`) e `posicao`/`status` do item (Task 4 e Fase 1); `useRotasSala().progresso(itemId)` (Fase 1).
- Produces:
  - `PlayerVideo.vue` props `{ urlMaster: String, posicaoInicial: Number, duracao: Number, concluido: Boolean }`, emite `posicao(segundos: number)` e `terminou()`: `<video controls playsinline>`; se `Hls.isSupported()` usa `new Hls({ enableWorker: true })` com `loadSource/attachMedia`, senão atribui `src` direto (Safari); o `hls.js` é carregado por **import dinâmico** (`await import('hls.js')`, code-split) só quando um item `VIDEO` abre, para não pesar o bundle da sala; em `loadedmetadata`, `currentTime = posicaoInicial` quando `!concluido && posicaoInicial > 0`; emite `posicao(Math.floor(currentTime))` a cada 15 s enquanto toca, em `pause` e em `visibilitychange` (hidden); em `ended` emite `terminou()`; `hls.destroy()` no `onUnmounted`.
  - `useProgressoVideo({ enviar: (posicao: number) => Promise<void>, duracao: number })` -> `{ aoPosicao(segundos), aoTerminar() }`: `aoPosicao` só envia quando `segundos > ultimaEnviada`; `aoTerminar` envia `duracao`.
  - `SalaDeAulaTemplate.vue`: para `item.tipo === 'VIDEO'`, se `item.video.url_master` renderiza `<PlayerVideo>` ligado ao `useProgressoVideo` (reusando a função que hoje envia o progresso do PDF e substitui `estado` pela resposta); senão mostra o cartão "Vídeo em preparação" com `video.status_label`. Em `escopo === 'previa'` não envia progresso (como o PDF).

- [ ] **Step 1: Instalar e implementar**: `cd SDC && npm install hls.js@1.7.4`; componente, composable e template conforme Interfaces.
- [ ] **Step 2: Build**: `npx vite build`; sem erro, com o `hls.js` em chunk separado (não no bundle da sala).
- [ ] **Step 3: Smoke manual em dev** (`TREINAMENTO_VIDEO_SERVIR_PELO_APP=true` no `.env` do dev, `config:clear`): aluno servidor abre o item de vídeo processado na Task 6 (a aba Rede só baixa o chunk do `hls.js` ao abrir o item `VIDEO`), o player toca, a aba Rede mostra `master.m3u8` e `.ts` em `/videos/{videoId}/...` com 200 e, antes deles, nenhum 403; fechar na metade e reabrir volta no mesmo ponto; assistir até o fim marca o item concluído e a pasta seguinte abre; cidadão repete pelo portal num curso `CIDADAOS`; na prévia do professor o vídeo toca e nada é gravado.
- [ ] **Step 4: Commit**

```bash
git add SDC/package.json SDC/package-lock.json SDC/resources/js/Components/Organisms/Treinamento/Sala/PlayerVideo.vue SDC/resources/js/Composables/treinamento/useProgressoVideo.js SDC/resources/js/Templates/Treinamento/SalaDeAulaTemplate.vue
git commit -m "✨ feat(treinamento): player HLS com retomada por tempo na sala de aula"
```

---

### Task 8: Limpeza de órfãos e métricas

**Files:**
- Create: `Jobs/LimparVideosOrfaosJob.php`, `Services/Video/VideoMetrics.php` (se a Task 3 criou só a casca, completar aqui)
- Modify: `SDC/routes/console.php`, `SDC/app/Http/Controllers/Api/MetricsController.php:59`
- Test (local): `tests/Feature/Treinamento/Ead/Video/LimpezaVideosTest.php`

**Interfaces:**
- Consumes: `Video`, `VideoUpload`, `CaminhosVideo`, `PublicadorHls::removerVersao()` (Tasks 1 e 3).
- Produces:
  - `LimparVideosOrfaosJob` (fila `videos`, `handle(): void`): (1) uploads `ABERTO` com `expira_em < now()` -> `VideoUploadService::cancelar`; (2) `originais/{video}/*` de vídeos `PRONTO` quando `!manter_original`; (3) diretórios em `hls/{video}/` diferentes de `hls_versao` e de `.tmp-*` com menos de 2 h; (4) `storage/logs/ffmpeg/*.log` com mais de `log_ffmpeg_dias`. Devolve nada; loga contagens no canal `treinamento`.
  - `routes/console.php`: `Schedule::job(new LimparVideosOrfaosJob)->dailyAt('03:30')->onOneServer()`.
  - `VideoMetrics` (Redis, prefixo `treinamento:video:`): `registrarProcessamento(bool $sucesso, int $segundos, int $bytesHls): void` (`incr processados:{sucesso|falha}`, `incrby processamento_segundos_sum`, `incr processamento_segundos_count`, `incrby hls_bytes` ou `decrby` ao remover versão via `registrarRemocao(int $bytes)`); `linhas(): array` no formato Prometheus text com `# HELP`/`# TYPE`: `sdc_treinamento_video_processados_total{resultado="sucesso"|"falha"}` (counter), `sdc_treinamento_video_processamento_segundos_sum` e `_count` (summary parcial), `sdc_treinamento_video_hls_bytes` (gauge). Em erro de Redis, retorna `[]`.
  - `MetricsController`: `$linhas = array_merge($linhas, SwooleRuntimeMetrics::linhas(), VideoMetrics::linhas());`.

- [ ] **Step 1: Escrever `LimpezaVideosTest`** (`Storage::fake`): `test_upload_expirado_e_cancelado_e_partes_apagadas`; `test_upload_vigente_e_mantido`; `test_original_de_video_pronto_e_apagado_sem_manter_original`; `test_versao_antiga_e_apagada_e_atual_mantida`; `test_tmp_recente_e_preservado`; `test_metricas_formatam_linhas_prometheus` (após `registrarProcessamento(true, 90, 1000)`, `linhas()` contém `sdc_treinamento_video_processados_total{resultado="sucesso"} 1` e `sdc_treinamento_video_hls_bytes 1000`).
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/LimpezaVideosTest.php`.
- [ ] **Step 3: Implementar**; rodar; PASS (6). Conferir `bash .superpowers/sdd/2026-11-10-treinamento-ead-fase2-video/artisan.sh schedule:list | grep -i video`.
- [ ] **Step 4: Commit**

```bash
git add SDC/routes/console.php SDC/app/Http/Controllers/Api/MetricsController.php SDC/app/Modules/Treinamento/Jobs/LimparVideosOrfaosJob.php SDC/app/Modules/Treinamento/Services/Video/VideoMetrics.php
git commit -m "✨ feat(treinamento): limpeza de videos orfaos e metricas do pipeline"
```

---

### Task 9: Verificação da fase (dev, homolog e produção externa)

- [ ] **Step 1: Suite**: `TEST tests/Feature/Treinamento`; tudo verde (Fase 1 + os 7 arquivos desta fase).
- [ ] **Step 2: Rotas**: `bash .superpowers/sdd/2026-11-10-treinamento-ead-fase2-video/artisan.sh route:list --path=video` lista as rotas de biblioteca/gerência (`treinamentos.professor.videos.*`), vincular/desvincular do item, `videos.auth` e `videos.arquivo-dev`; `--path=treinamentos/aluno` inalterado.
- [ ] **Step 3: Homolog ponta a ponta com Caddy real** (`compose.homolog.yml`, `TREINAMENTO_VIDEO_SERVIR_PELO_APP` ausente): professor sobe uma aula de 2 min e uma de 1 h e reaproveita uma delas num segundo curso pela biblioteca; acompanhar `docker stats newsdc_homolog_queue_videos` (CPU perto de 150%, não mais) e o p95 de `/health` do app durante o processamento (não deve subir); `master.m3u8` da aula de 1 h tem as duas renditions; aluno toca pelo Caddy em `:8090`, troca de qualidade com a rede limitada no DevTools; `curl` anônimo no `.ts` recebe 403; aluno do segundo curso só toca se o item dele estiver liberado; anotar o tempo de processamento e o tamanho de `hls/{item}` para calibrar `preset`/renditions (§8.3).
- [ ] **Step 4: Deploy em produção** seguindo a ordem de §11 (migration, config, imagem `sdc-queue`, `docker stack deploy` por causa do serviço e do volume novos, app). Antes do `stack deploy`, Matheus confirma no R760 `docker volume inspect sdc_app_storage` e a presença/ausência de `ANEXOS_ROOT` em `docker service inspect sdc_app`, e escolhe a linha certa da tabela de §11.
- [ ] **Step 5: Smoke externo em produção** (§11, passo 6): de uma conexão fora da rede corporativa, professor sobe uma aula real e aluno assiste; se alguma parte de 8 MB ou segmento falhar por limite no caminho, baixar `TREINAMENTO_VIDEO_PARTE_BYTES` e/ou `hls_time` e repetir, sem mudança de código.
- [ ] **Step 6: Limpar** os cursos, itens e arquivos de smoke em dev e homolog, e listar ao usuário o que foi apagado.
