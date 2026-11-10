# Treinamento EAD, Fase 2 (Vídeo on-premise) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Professor sobe um MP4 em partes pela página Conteúdo, um worker de fila converte para HLS com FFmpeg, o Caddy entrega os segmentos só para quem pode assistir, e o aluno vê o vídeo na sala de aula com retomada por tempo.

**Architecture:** O Octane só recebe partes de 8 MB e grava no disco `treinamentos` compartilhado; `finalizar` despacha `ProcessarVideoItemJob` na fila `videos`, consumida por um serviço próprio (`queue-videos`, imagem `sdc-queue` + FFmpeg). O job monta o original, sonda com `ffprobe`, transcodifica (480p/720p) para um diretório temporário e publica por `rename` em `hls/{item}/{versao}/`. O Caddy serve `/videos/*` do mesmo volume com `forward_auth` para `/videos/auth`, um endpoint leve do Octane que valida sessão + trilha e cacheia 5 min. O progresso reaproveita `ProgressoService` da Fase 1, generalizado para "total por tipo de item".

**Tech Stack:** Laravel 12 + Octane/Swoole 6.2, PostgreSQL (`sdc_treinamento`, schema `treinamento`), Redis (fila `redis-videos`, cache), FFmpeg/ffprobe via `Illuminate\Support\Facades\Process`, Caddy 2 (`forward_auth`, `file_server`), Docker Swarm, Vue 3 + Inertia, `hls.js@1.7.4`.

**Spec:** `docs/superpowers/specs/2026-11-10-treinamento-video-onprem-design.md` (seções citadas como §N). Base: `docs/superpowers/plans/2026-11-10-treinamento-ead-fase1-base.md` (Fase 1), cujas interfaces este plano consome.

## Global Constraints

- Executa **depois** da Fase 1 concluída, na mesma branch `feat/treinamento-ead` (worktree `.worktrees/treinamento-ead`), ou em `feat/treinamento-video` criada a partir dela.
- Banco: toda DDL entra **consolidada e idempotente** na migration principal `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php` (novo método privado `criarVideo()`), nomes qualificados `treinamento.x`, sem FK para `users`. Nenhuma migration nova.
- Vídeo **nunca** passa inteiro pelo Octane: parte de `8 * 1024 * 1024` bytes, limite por vídeo `2 * 1024 * 1024 * 1024` bytes, mimes `video/mp4`, `video/quicktime`, `video/x-matroska`, sessão expira em 24 h (§7.1). `config/octane.php` passa a declarar `'package_max_length' => 64 * 1024 * 1024` (§13).
- FFmpeg roda **só** no worker da fila `videos` (conexão `redis-videos`, `retry_after` 3700, `--timeout=3600`, `--tries=3`), com `nice -n 10` e `-threads 2`; renditions 480p (854x480, 800k) e 720p (1280x720, 2500k) ativas, 1080p (1920x1080, 5000k) desligada por config; `preset veryfast`, `crf 23`, `hls_time 6`, áudio `aac 128k` (§8).
- HLS publicado por versão em `hls/{item}/{versao}/master.m3u8`; a troca é atômica (`rename`) e a versão anterior só some depois (§8.4). URL pública `/videos/{item}/{versao}/...` servida pelo Caddy; o Laravel só serve segmento quando `TREINAMENTO_VIDEO_SERVIR_PELO_APP=true` e fora de produção (§9.3).
- `/videos/auth`: 204 ou 403 sem corpo, cache de decisão 300 s por `(id da sessão, item, versão)` (§9.2).
- Sem emojis no código. Logs de depuração removidos após o teste. DRY/SOLID. Testes em `SDC/tests/Feature/Treinamento/Ead/` rodam localmente e **não entram em commit**. Commits atômicos por task, gitmoji, pt-BR, **sem** trailer `Co-Authored-By`.
- Comando de teste (host, a partir de `SDC/`): `TEST <x>` = o comando da Fase 1 (`APP_CONFIG_CACHE=/c/tmp/nao-existe-config.php DB_... php -d extension=pdo_pgsql -d extension=pgsql vendor/bin/phpunit <x>`). FFmpeg não existe no host: todo teste de processamento usa `Process::fake()`.
- Fixtures: títulos `TST-EAD-`, CPF de cidadão `999...`; arquivos de teste via `Storage::fake('treinamentos')` e `UploadedFile::fake()->create(...)`.

## Review Focus

1. Parte com `n` fora de `1..total_partes`, tamanho diferente do esperado, ou `upload` de outro item/curso na URL: esperado 422/404 e **nada gravado** no disco nem nos contadores. (Task 2)
2. `finalizar` chamado duas vezes, ou com parte faltando: um único job despachado no primeiro caso, 422 sem despacho no segundo. (Task 2)
3. `/videos/auth` com URI malformada, versão que não é a atual nem a anterior, item de pasta travada, cidadão em curso `SERVIDORES`, sessão sem login: sempre 403, e o 403 **não** entra no cache. (Task 4)
4. Job morto no meio (original parcial, `.tmp-*` sobrando) e reexecutado: retoma sem duplicar; nova versão que falha mantém a anterior servível e o item volta a `PRONTO`. (Task 3)
5. Progresso em item `VIDEO` que não está `PRONTO`, ou `posicao` acima de `duracao_segundos`: 422 no primeiro, corte no total e `CONCLUIDO` no segundo; `posicao` menor que a salva não regride. (Task 1)

---

## File Structure

**Backend (`SDC/app/Modules/Treinamento/`)**
- `Enums/VideoStatus.php` (novo); `Enums/TipoItem.php` (+ `VIDEO`).
- `Models/VideoUpload.php` (novo); `Models/ModuloItem.php` (+ campos de vídeo, `totalProgresso()`, `uploadAberto()`).
- `Support/CaminhosVideo.php` (novo: todos os caminhos e a URL do master num lugar só).
- `Services/Video/VideoUploadService.php`, `Services/Video/ProcessadorDeVideo.php`, `Services/Video/MontadorDeOriginal.php`, `Services/Video/Ffmpeg.php`, `Services/Video/PublicadorHls.php`, `Services/Video/AutorizacaoVideoService.php`, `Services/Video/VideoMetrics.php` (novos).
- `DTOs/SondagemVideo.php` (novo); `DTOs/EstadoItem.php` (+ `video`).
- `Exceptions/FalhaDeVideo.php` (novo).
- `Jobs/ProcessarVideoItemJob.php`, `Jobs/LimparVideosOrfaosJob.php` (novos).
- `Controllers/Professor/Video/{UploadAbrirController,UploadParteController,UploadFinalizarController,UploadCancelarController,StatusController,ReprocessarController,RemoverController}.php`, `Controllers/VideoAuthController.php`, `Controllers/VideoArquivoDevController.php` (novos).
- `Requests/Professor/Video/{AbrirUploadRequest,ReceberParteRequest}.php` (novos).
- `Services/ProgressoService.php`, `Services/TrilhaService.php` (alterados).

**Outros backend:** migration principal; `SDC/config/treinamento.php` (novo); `SDC/config/octane.php`, `SDC/config/queue.php`, `SDC/config/logging.php`; `SDC/routes/modules/treinamento.php`, `SDC/routes/web.php`, `SDC/routes/console.php`; `SDC/app/Http/Controllers/Api/MetricsController.php`; `SDC/.env.example`.

**Infra (`SDC/docker/`)**: `Dockerfile.queue`, `queue/entrypoint.sh`, `supervisor/videos-worker.conf` (novo), `jenkins/stack.app.onpremise.yml`, `jenkins/Jenkinsfile.onprem`, `compose.homolog.yml`, `caddy/Caddyfile`, `caddy/Caddyfile.homolog`.

**Frontend (`SDC/resources/js/`)**: `Composables/treinamento/useUploadEmPartes.js`, `Composables/treinamento/useProgressoVideo.js`, `Components/Organisms/Treinamento/Conteudo/UploaderVideo.vue`, `Components/Organisms/Treinamento/Sala/PlayerVideo.vue` (novos); `Pages/Treinamento/Professor/Conteudo.vue`, `Templates/Treinamento/SalaDeAulaTemplate.vue`, `package.json` (alterados).

**Testes (locais):** `tests/Feature/Treinamento/Ead/Video/{SchemaVideoTest,ProgressoVideoTest,VideoUploadTest,ProcessarVideoJobTest,VideoAuthTest,LimpezaVideosTest}.php`, reutilizando `EadTestCase` da Fase 1.

---

### Task 1: Schema, config, enums, model e progresso por tipo

**Files:**
- Modify: `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php`, `SDC/config/octane.php:69-93`, `Enums/TipoItem.php`, `Models/ModuloItem.php`, `Services/ProgressoService.php`, `Requests/RegistrarProgressoRequest.php`, `SDC/.env.example`
- Create: `SDC/config/treinamento.php`, `Enums/VideoStatus.php`, `Models/VideoUpload.php`, `Support/CaminhosVideo.php`
- Test (local): `tests/Feature/Treinamento/Ead/Video/SchemaVideoTest.php`, `tests/Feature/Treinamento/Ead/Video/ProgressoVideoTest.php`

**Interfaces:**
- Consumes: `EadTestCase` (helpers `criarCurso`, `criarPasta`, `criarItem`, `inscrever`), `ProgressoService::registrarPosicao(Inscricao, ModuloItem, int): ProgressoItem`, `TrilhaService::itemLiberado()` (Fase 1).
- Produces (DDL, schema `treinamento`):
  - `modulo_itens` + `video_status varchar(20) NOT NULL DEFAULT 'SEM_VIDEO'` CHECK (`SEM_VIDEO`,`UPLOAD_ABERTO`,`ENVIADO`,`PROCESSANDO`,`PRONTO`,`FALHOU`) com índice; + `video_original_path varchar(500)`, `video_tamanho_bytes bigint`, `hls_versao varchar(40)`, `duracao_segundos integer`, `video_largura integer`, `video_altura integer`, `video_processado_em timestamp(0)`, `video_erro text`, `video_tentativas smallint NOT NULL DEFAULT 0`.
  - `video_uploads` (`id uuid PK`, `modulo_item_id` FK -> `modulo_itens` ON DELETE CASCADE, `user_id bigint NOT NULL`, `nome_original varchar(255)`, `mime varchar(100)`, `tamanho_total bigint NOT NULL`, `tamanho_parte integer NOT NULL`, `total_partes integer NOT NULL`, `partes_recebidas integer NOT NULL DEFAULT 0`, `tamanho_recebido bigint NOT NULL DEFAULT 0`, `status varchar(20) NOT NULL DEFAULT 'ABERTO'` CHECK (`ABERTO`,`COMPLETO`,`CANCELADO`), `expira_em timestamp(0) NOT NULL`, timestamps); índices `video_uploads_user_id_fkidx`, e único parcial `video_uploads_um_aberto_por_item ON (modulo_item_id) WHERE status = 'ABERTO'`.
- Produces (config `SDC/config/treinamento.php`, chave `video`): `disco => 'treinamentos'`, `tamanho_parte_bytes => (int) env('TREINAMENTO_VIDEO_PARTE_BYTES', 8388608)`, `tamanho_maximo_bytes => (int) env('TREINAMENTO_VIDEO_MAX_BYTES', 2147483648)`, `mimes => ['video/mp4','video/quicktime','video/x-matroska']`, `upload_expira_horas => 24`, `manter_original => (bool) env('TREINAMENTO_VIDEO_MANTER_ORIGINAL', false)`, `conexao_fila => 'redis-videos'`, `fila => 'videos'`, `auth_cache_segundos => 300`, `servir_pelo_app => (bool) env('TREINAMENTO_VIDEO_SERVIR_PELO_APP', false)`, `log_ffmpeg_dias => 30`, `ffmpeg => ['binario' => env('FFMPEG_BIN','ffmpeg'), 'ffprobe' => env('FFPROBE_BIN','ffprobe'), 'nice' => 10, 'threads' => 2, 'preset' => 'veryfast', 'crf' => 23, 'hls_time' => 6, 'audio_bitrate' => '128k']`, `renditions => ['480p' => ['largura'=>854,'altura'=>480,'bitrate'=>'800k','ativa'=>true], '720p' => ['largura'=>1280,'altura'=>720,'bitrate'=>'2500k','ativa'=>true], '1080p' => ['largura'=>1920,'altura'=>1080,'bitrate'=>'5000k','ativa'=>(bool) env('TREINAMENTO_VIDEO_1080P', false)]]`. As mesmas envs entram comentadas no `.env.example`.
- Produces (código):
  - `enum VideoStatus: string { SEM_VIDEO; UPLOAD_ABERTO; ENVIADO; PROCESSANDO; PRONTO; FALHOU; getLabel(): string; estaPronto(): bool; podeReprocessar(): bool /* FALHOU */; emProcessamento(): bool /* ENVIADO|PROCESSANDO */ }`.
  - `TipoItem::VIDEO = 'VIDEO'`, label "Vídeo"; `TipoItem::exigeArquivo(): bool` (`PDF` true, demais false), usado pelo `ConteudoCursoService::criarItem` para não exigir arquivo em `VIDEO`.
  - `VideoUpload` (`treinamento.video_uploads`, `HasUuids`, `UsaConexaoDeTreinamento`, casts `expira_em` datetime): `item(): BelongsTo`, `estaAberto(): bool` (`ABERTO` e não expirado), `completo(): bool` (`partes_recebidas === total_partes && tamanho_recebido === tamanho_total`).
  - `ModuloItem`: casts `video_status => VideoStatus`, `video_processado_em => datetime`; `uploadAberto(): HasOne` (`VideoUpload` com `status = ABERTO`); `totalProgresso(): ?int` (`PDF` -> `total_paginas`; `VIDEO` -> `duracao_segundos` só se `video_status->estaPronto()`; demais `null`); `temVideoPronto(): bool`.
  - `CaminhosVideo` (estático, relativo ao disco `treinamentos`): `partes(string $uploadId): string` = `uploads/{uploadId}`; `parte(string $uploadId, int $n): string`; `original(int $itemId, string $uuid): string` = `originais/{itemId}/{uuid}.mp4`; `hlsRaiz(int $itemId): string` = `hls/{itemId}`; `hlsTemporario(int $itemId, string $uuid): string` = `hls/{itemId}/.tmp-{uuid}`; `hlsVersao(int $itemId, string $versao): string`; `urlMaster(int $itemId, string $versao): string` = `/videos/{itemId}/{versao}/master.m3u8`; `logFfmpeg(int $itemId, string $versao): string` = `storage_path("logs/ffmpeg/{itemId}-{versao}.log")`.
  - `ProgressoService::registrarPosicao()` deixa de exigir `PDF`: usa `$item->totalProgresso()`; `null` lança `DomainException('Este item nao registra progresso por posicao.')` (controller devolve 422); o resto da regra da Fase 1 (max com a atual, corte no total, conclui ao atingir o total) vale para qualquer tipo. `RegistrarProgressoRequest`: `posicao` passa a `integer|min:0`.
  - `config/octane.php`: dentro de `swoole.options`, `'package_max_length' => 64 * 1024 * 1024,` com comentário de uma linha (padrão do Octane é 10 MB; php.ini já aceita 64M).

- [ ] **Step 1: Escrever `SchemaVideoTest`**: `test_tabela_video_uploads_existe`; `test_colunas_de_video_em_modulo_itens` (`hasColumns('treinamento.modulo_itens', ['video_status','hls_versao','duracao_segundos','video_erro','video_tentativas'])`); `test_status_invalido_rejeitado` (`video_status = 'X'` lança `QueryException`); `test_dois_uploads_abertos_no_mesmo_item_rejeitado` (segundo insert `ABERTO` lança `QueryException`; um `CANCELADO` + um `ABERTO` passa).
- [ ] **Step 2: Escrever `ProgressoVideoTest`**: `test_video_pronto_registra_posicao_em_segundos` (item `VIDEO` `PRONTO` com `duracao_segundos = 600`; posição 120 -> `posicao 120`, `EM_ANDAMENTO`); `test_posicao_acima_da_duracao_e_cortada_e_conclui` (9999 -> 600, `CONCLUIDO`) (Review Focus 5); `test_posicao_menor_nao_regride` (300 depois 100 -> 300); `test_video_nao_pronto_nao_registra` (`PROCESSANDO` -> `DomainException`) (Review Focus 5); `test_projeto_nao_registra_posicao` (`DomainException`); `test_pdf_continua_funcionando` (3 páginas, posição 3 -> `CONCLUIDO`).
- [ ] **Step 3: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/SchemaVideoTest.php` e `TEST tests/Feature/Treinamento/Ead/Video/ProgressoVideoTest.php`; FAIL (tabela e `VideoStatus` inexistentes).
- [ ] **Step 4: Implementar** DDL (`criarVideo()` chamado no fim de `up()`), config, enums, model, `CaminhosVideo`, `ModuloItem`, `ProgressoService`, request e `octane.php` conforme Interfaces.
- [ ] **Step 5: Aplicar no banco de dev** (migration idempotente, mesmo ritual da Fase 1):

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc_treinamento -c "DELETE FROM migrations WHERE migration = '2026_11_05_210000_create_treinamento_schemas_tables';"
docker exec newsdc_dev_app php artisan migrate --database=pgsql_treinamento --path=database/migrations/treinamento --force
```

- [ ] **Step 6: Rodar os dois testes**: PASS (4 + 6). Rodar também `TEST tests/Feature/Treinamento/Ead/TrilhaServiceTest.php` (Fase 1) para garantir que o progresso do PDF não mudou.
- [ ] **Step 7: Commit**

```bash
git add SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php SDC/config/treinamento.php SDC/config/octane.php SDC/.env.example SDC/app/Modules/Treinamento/Enums/VideoStatus.php SDC/app/Modules/Treinamento/Enums/TipoItem.php SDC/app/Modules/Treinamento/Models/VideoUpload.php SDC/app/Modules/Treinamento/Models/ModuloItem.php SDC/app/Modules/Treinamento/Support/CaminhosVideo.php SDC/app/Modules/Treinamento/Services/ProgressoService.php SDC/app/Modules/Treinamento/Requests/RegistrarProgressoRequest.php
git commit -m "🗃️ db(treinamento): schema e config do video EAD, progresso por tipo de item"
```

---

### Task 2: Upload em partes (backend do professor)

**Files:**
- Create: `Services/Video/VideoUploadService.php`, `Controllers/Professor/Video/{UploadAbrirController,UploadParteController,UploadFinalizarController,UploadCancelarController}.php`, `Requests/Professor/Video/{AbrirUploadRequest,ReceberParteRequest}.php`, `Jobs/ProcessarVideoItemJob.php` (só a casca: construtor, unicidade, fila; o `handle` vem na Task 3)
- Modify: `SDC/config/queue.php`, `SDC/routes/modules/treinamento.php`, `Services/ConteudoCursoService.php` (aceitar `tipo = VIDEO` sem arquivo, via `TipoItem::exigeArquivo()`), `Requests/Professor/SalvarItemRequest.php` (`tipo in:PDF,PROJETO,VIDEO`)
- Test (local): `tests/Feature/Treinamento/Ead/Video/VideoUploadTest.php`

**Interfaces:**
- Consumes: `LecionaCurso`, `ConteudoCursoService::garantirDoCurso()` (Fase 1); `VideoUpload`, `CaminhosVideo`, `config('treinamento.video.*')` (Task 1).
- Produces:
  - `config/queue.php`: conexão `'redis-videos' => ['driver' => 'redis', 'connection' => env('REDIS_QUEUE_CONNECTION', 'default'), 'queue' => 'videos', 'retry_after' => 3700, 'block_for' => 5, 'after_commit' => false]`.
  - `VideoUploadService`:
    - `abrir(ModuloItem $item, int $userId, string $nome, int $tamanhoTotal, string $mime): VideoUpload`: `DomainException` se `tipo !== VIDEO`, se `tamanhoTotal > tamanho_maximo_bytes` ou `mime` fora da lista; cancela upload `ABERTO` anterior do item (apaga partes); cria com `tamanho_parte` da config, `total_partes = (int) ceil(tamanhoTotal / tamanho_parte)`, `expira_em = now()->addHours(24)`; item passa a `UPLOAD_ABERTO` (se estava `SEM_VIDEO` ou `FALHOU`; se `PRONTO`, mantém `PRONTO` e `hls_versao`, §8.4).
    - `receberParte(VideoUpload $u, int $n, UploadedFile $parte): VideoUpload`: `DomainException` se `!estaAberto()`, se `n < 1 || n > total_partes`, ou se o tamanho da parte difere do esperado (`tamanho_parte`, ou `tamanho_total - tamanho_parte*(total_partes-1)` para a última); grava `storeAs(CaminhosVideo::partes($u->id), "{$n}.part", 'treinamentos')`; recalcula `partes_recebidas` e `tamanho_recebido` **contando os arquivos existentes** (reenvio não duplica).
    - `finalizar(VideoUpload $u): ModuloItem`: se `status === COMPLETO` retorna o item sem despachar de novo; `DomainException` se `!completo()`; marca `COMPLETO`, item `ENVIADO` (ou mantém `PRONTO` quando há versão, gravando a intenção em `video_erro = null`), despacha `ProcessarVideoItemJob::dispatch($item->id, $u->id)`; `RegistroDeAcao::registrarNoProtocolo(Treinamento::class, $treinamentoId, AcaoTrilha::Relacionado, 'video enviado para processamento')`.
    - `cancelar(VideoUpload $u): void`: apaga `uploads/{id}` do disco, marca `CANCELADO`, item volta a `SEM_VIDEO` se não tem `hls_versao`, senão `PRONTO`.
  - `ProcessarVideoItemJob implements ShouldQueue, ShouldBeUnique`: `__construct(public int $itemId, public string $uploadId)`; `$tries = 3`, `$timeout = 3600`, `$uniqueFor = 7200`, `uniqueId(): string` = `"video-item-{$this->itemId}"`, `backoff(): array` = `[120]`; `onConnection(config('treinamento.video.conexao_fila'))->onQueue(config('treinamento.video.fila'))` no construtor.
  - Rotas (grupo do professor da Fase 1, com `leciona.curso`), nome `treinamentos.professor.itens.video.`: `POST /{treinamento}/itens/{item}/video/upload` -> `upload.abrir` (JSON `{upload_id, tamanho_parte, total_partes, expira_em}`); `PUT /{treinamento}/itens/{item}/video/upload/{upload}/partes/{n}` -> `upload.parte` (multipart campo `parte`; JSON `{partes_recebidas, tamanho_recebido}`); `POST /{treinamento}/itens/{item}/video/upload/{upload}/finalizar` -> `upload.finalizar` (JSON `{video_status}`); `DELETE /{treinamento}/itens/{item}/video/upload/{upload}` -> `upload.cancelar` (204). `{upload}` de outro item ou `{item}` de outro curso -> 404 (`garantirDoCurso` + `firstOrFail` por `modulo_item_id`). `DomainException` -> 422 com `message`.
  - `AbrirUploadRequest`: `nome_original required|string|max:255`, `tamanho_total required|integer|min:1`, `mime required|string`. `ReceberParteRequest`: `parte required|file`.

- [ ] **Step 1: Escrever `VideoUploadTest`** (com `Storage::fake('treinamentos')` e `Queue::fake()`): `test_abrir_calcula_partes_e_marca_item` (tamanho 20 MB -> `total_partes 3`, `tamanho_parte 8388608`, item `UPLOAD_ABERTO`); `test_abrir_recusa_mime_e_tamanho` (`video/avi` -> 422; 3 GB -> 422); `test_abrir_em_item_pdf_da_422`; `test_parte_fora_da_faixa_nao_grava` (`n = 4` em 3 partes -> 422, `Storage::assertMissing`) (Review Focus 1); `test_parte_com_tamanho_errado_nao_grava` (Review Focus 1); `test_reenviar_parte_nao_duplica_contagem`; `test_upload_de_outro_item_na_url_da_404` (Review Focus 1); `test_finalizar_com_parte_faltando_da_422_sem_despachar` (`Queue::assertNothingPushed`) (Review Focus 2); `test_finalizar_despacha_uma_vez_na_fila_videos` (3 partes, finalizar 2x -> `Queue::assertPushedOn('videos', ProcessarVideoItemJob::class)` e `assertPushed(..., 1)`, item `ENVIADO`) (Review Focus 2); `test_cancelar_apaga_partes_e_volta_sem_video`; `test_professor_de_outro_curso_recebe_403`.
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/VideoUploadTest.php`.
- [ ] **Step 3: Implementar** conforme Interfaces (controllers invocáveis, finos; a transação de `finalizar` via `ConexaoTreinamento::transacao()` e o `dispatch` depois do commit).
- [ ] **Step 4: Rodar**: PASS (11).
- [ ] **Step 5: Commit**

```bash
git add SDC/config/queue.php SDC/routes/modules/treinamento.php SDC/app/Modules/Treinamento/Services/Video/VideoUploadService.php SDC/app/Modules/Treinamento/Controllers/Professor/Video SDC/app/Modules/Treinamento/Requests/Professor/Video SDC/app/Modules/Treinamento/Requests/Professor/SalvarItemRequest.php SDC/app/Modules/Treinamento/Services/ConteudoCursoService.php SDC/app/Modules/Treinamento/Jobs/ProcessarVideoItemJob.php
git commit -m "✨ feat(treinamento): upload de video em partes pelo professor"
```

---

### Task 3: Processamento (montagem, ffprobe, FFmpeg, publicação por versão) e endpoints de status

**Files:**
- Create: `Services/Video/MontadorDeOriginal.php`, `Services/Video/Ffmpeg.php`, `Services/Video/PublicadorHls.php`, `Services/Video/ProcessadorDeVideo.php`, `DTOs/SondagemVideo.php`, `Exceptions/FalhaDeVideo.php`, `Controllers/Professor/Video/{StatusController,ReprocessarController,RemoverController}.php`
- Modify: `Jobs/ProcessarVideoItemJob.php` (`handle`, `failed`), `SDC/routes/modules/treinamento.php`, `SDC/config/logging.php` (canal `treinamento`: `daily`, `storage/logs/treinamento.log`, `days 14`)
- Test (local): `tests/Feature/Treinamento/Ead/Video/ProcessarVideoJobTest.php`

**Interfaces:**
- Consumes: `VideoUpload`, `CaminhosVideo`, `VideoStatus`, config (Task 1); `VideoUploadService::cancelar()` (Task 2).
- Produces:
  - `final readonly class SondagemVideo { __construct(public int $duracaoSegundos, public int $largura, public int $altura) }`.
  - `class FalhaDeVideo extends \RuntimeException { __construct(string $mensagem, public readonly bool $definitiva = false) }` (`definitiva` = não vale tentar de novo: arquivo inválido).
  - `MontadorDeOriginal::montar(VideoUpload $u): string` (caminho relativo do original): se já existe com `size === tamanho_total`, retorna; senão concatena `1..total_partes` por stream (`fopen`/`stream_copy_to_stream`, 1 MiB por vez) em arquivo `.tmp` e renomeia; confere o tamanho (`FalhaDeVideo` definitiva se diferir); apaga `uploads/{id}`.
  - `Ffmpeg::sondar(string $absoluto): SondagemVideo` (`Process::run([ffprobe, '-v','error','-show_streams','-show_format','-of','json', $absoluto])`; sem stream `codec_type = video` -> `FalhaDeVideo` definitiva; `duracao = (int) ceil(format.duration)`).
  - `Ffmpeg::transcodificar(string $origemAbs, string $destinoAbs, SondagemVideo $s, string $logAbs): void`: monta **um** comando `nice -n {nice} ffmpeg -y -i origem -threads {threads} -filter_complex "[0:v]split=N[v1]..[vN];[v1]scale=w=854:h=480[v1out]..." -map [v1out] -c:v:0 libx264 -preset {preset} -crf {crf} -maxrate:v:0 800k -bufsize:v:0 1600k -pix_fmt yuv420p -map a:0? -c:a aac -b:a 128k ... -f hls -hls_time {hls_time} -hls_playlist_type vod -hls_segment_type mpegts -hls_segment_filename "destino/%v/%05d.ts" -master_pl_name master.m3u8 -var_stream_map "v:0,a:0,name:480p v:1,a:1,name:720p" destino/%v/index.m3u8`, só com as renditions `ativa` cuja `altura <= $s->altura` (sempre pelo menos a menor); `Process::timeout(3500)`; stderr gravado em `$logAbs`; exit code != 0 -> `FalhaDeVideo` (não definitiva) com os últimos 1000 caracteres do stderr. `comando(...): array` é público para o teste inspecionar.
  - `PublicadorHls::publicar(ModuloItem $item, string $tmpRelativo): string` (gera `versao = now()->format('YmdHis')`, `rename` do diretório temporário para `hlsVersao`, guarda a `hls_versao` anterior em `Cache::put("video_versao_anterior:{$item->id}", $anterior, 600)` para a janela de troca da Task 4, retorna `versao`); `removerVersao(ModuloItem $item, string $versao): void`; `removerTemporarios(ModuloItem $item): void` (apaga `hls/{item}/.tmp-*`); `removerTudo(ModuloItem $item): void`.
  - `ProcessadorDeVideo::processar(ModuloItem $item, VideoUpload $upload): void`: item -> `PROCESSANDO` (guardando `hls_versao` anterior em variável); `removerTemporarios`; `montar`; `sondar`; `transcodificar` para `hlsTemporario(item, uuid)`; `publicar`; atualiza item `PRONTO`, `hls_versao`, `duracao_segundos`, `video_largura/altura`, `video_tamanho_bytes`, `video_processado_em = now()`, `video_erro = null`, `video_tentativas = 0`; remove versão anterior e, se `!manter_original`, o original; grava `VideoMetrics` (Task 8, chamada via interface mínima `VideoMetrics::registrarProcessamento(bool $sucesso, int $segundos, int $bytesHls)` já criada aqui). Em `FalhaDeVideo`: item `FALHOU` se não tinha versão, senão volta a `PRONTO` com a versão anterior; `video_erro = mensagem`, `video_tentativas++`; relança se `!definitiva`.
  - `ProcessarVideoItemJob::handle(ProcessadorDeVideo $p)`: carrega item e upload (`COMPLETO`); se upload não existe ou item foi removido, retorna; chama `processar`; captura `FalhaDeVideo` definitiva e chama `$this->fail($e)`. `failed(\Throwable $e)`: garante `FALHOU`/`PRONTO` + `video_erro` (caso de timeout/kill).
  - Rotas (mesmo grupo e prefixo de nome da Task 2): `GET /{treinamento}/itens/{item}/video/status` -> `status` (JSON `{video_status, video_status_label, duracao_segundos, hls_versao, video_erro, video_processado_em, upload: {id, partes_recebidas, total_partes} | null}`); `POST /{treinamento}/itens/{item}/video/reprocessar` -> `reprocessar` (só `podeReprocessar()`, senão 422; redespacha o job com o último upload `COMPLETO`; auditoria); `DELETE /{treinamento}/itens/{item}/video` -> `remover` (`PublicadorHls::removerTudo`, apaga original e upload aberto, zera os campos de vídeo, `SEM_VIDEO`; auditoria).

- [ ] **Step 1: Escrever `ProcessarVideoJobTest`** (`Storage::fake('treinamentos')`; `Process::fake([...])` com `'*ffprobe*' => Process::result(json com stream de vídeo 1280x720 e duration 95.4)` e `'*ffmpeg*' => Process::result('')`; antes de rodar o job, o teste cria no disco fake o que o FFmpeg criaria, em `hls/{item}/.tmp-*/master.m3u8`, usando um `Process::fake` com callback que escreve o arquivo): `test_fluxo_feliz_publica_versao_e_limpa` (item `PRONTO`, `duracao_segundos 96`, `master.m3u8` em `hls/{item}/{versao}/`, `uploads/{id}` ausente, original ausente); `test_manter_original_preserva_mp4`; `test_ffprobe_sem_video_falha_definitivo` (`FALHOU`, `video_erro` preenchido, `Process::assertNotRan('*ffmpeg*')`); `test_ffmpeg_com_erro_relanca_para_retry` (exit 1 -> `FalhaDeVideo` propagada, item `FALHOU`, `video_tentativas 1`); `test_reexecucao_reaproveita_original_e_limpa_tmp` (original já montado + `.tmp-x` sobrando -> não remonta, `.tmp-x` some, publica) (Review Focus 4); `test_nova_versao_que_falha_mantem_a_anterior` (item `PRONTO` com `hls_versao v1`; ffmpeg falha -> `PRONTO`, `hls_versao v1`, `video_erro` preenchido) (Review Focus 4); `test_nova_versao_ok_remove_a_anterior`; `test_comando_ffmpeg_pula_rendition_maior_que_o_original` (original 854x480 -> `var_stream_map` só com `480p`); `test_status_reprocessar_e_remover` (status JSON; reprocessar em `PRONTO` -> 422; em `FALHOU` -> job despachado; remover -> `SEM_VIDEO` e `hls/{item}` ausente).
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/ProcessarVideoJobTest.php`.
- [ ] **Step 3: Implementar** conforme Interfaces; log estruturado por etapa no canal `treinamento` (`item_id`, `stage`, `duracao_ms`, `bytes`).
- [ ] **Step 4: Rodar**: PASS (9).
- [ ] **Step 5: Commit**

```bash
git add SDC/config/logging.php SDC/routes/modules/treinamento.php SDC/app/Modules/Treinamento/Services/Video SDC/app/Modules/Treinamento/DTOs/SondagemVideo.php SDC/app/Modules/Treinamento/Exceptions/FalhaDeVideo.php SDC/app/Modules/Treinamento/Jobs/ProcessarVideoItemJob.php SDC/app/Modules/Treinamento/Controllers/Professor/Video
git commit -m "✨ feat(treinamento): processamento de video para HLS na fila com publicacao por versao"
```

---

### Task 4: Autorização de entrega (`/videos/auth`), rota de contingência de dev e estado do item

**Files:**
- Create: `Services/Video/AutorizacaoVideoService.php`, `Controllers/VideoAuthController.php`, `Controllers/VideoArquivoDevController.php`
- Modify: `DTOs/EstadoItem.php`, `Services/TrilhaService.php`, `SDC/routes/web.php` (fora do grupo `auth`, dentro de `web`)
- Test (local): `tests/Feature/Treinamento/Ead/Video/VideoAuthTest.php`

**Interfaces:**
- Consumes: `TrilhaService::itemLiberado(Inscricao, ModuloItem)`, `ProfessorService::leciona(int, Treinamento)`, `InscricaoDoAluno` (Fase 1); `CaminhosVideo::urlMaster()`, `VideoStatus` (Task 1).
- Produces:
  - `AutorizacaoVideoService::autorizarUri(string $uri): bool`: casa `#^/videos/(\d+)/([A-Za-z0-9_-]{1,40})/#`; senão `false`. Resolve o item (`ModuloItem::with('modulo.treinamento')`, não excluído, `PRONTO`), aceita `versao === hls_versao` ou `versao === Cache::get("video_versao_anterior:{item}")` (gravado pelo `PublicadorHls` na Task 3); delega a `podeAssistir`.
  - `AutorizacaoVideoService::podeAssistir(ModuloItem $item, ?Authenticatable $servidor, ?Authenticatable $cidadao): bool`: professor do curso ou `can('treinamento.cursos.edit')` -> `true`; inscrito (`servidor` em curso `SERVIDORES`, `cidadao` em `CIDADAOS`) com `Inscricao` `APROVADA` (consulta direta por `inscrito_type`/`inscrito_id`, sem o `InscricaoDoAluno`, que aborta) e `itemLiberado` -> `true`; senão `false`. O resultado positivo é cacheado por `auth_cache_segundos` em `video_auth:{session()->getId()}:{item}:{versao}`; o negativo **não** é cacheado.
  - `VideoAuthController` (`GET /videos/auth`, nome `videos.auth`, middleware só `web`): lê `X-Forwarded-Uri` (fallback `X-Original-URI`), responde `204` ou `403` sem corpo, sem Inertia.
  - `VideoArquivoDevController` (`GET /videos/{item}/{versao}/{arquivo}` com `->where('arquivo', '[A-Za-z0-9_\-]+(/[A-Za-z0-9_\-]+)*\.(m3u8|ts)')`, nome `videos.arquivo-dev`): `abort(404)` se `!config('treinamento.video.servir_pelo_app') || app()->isProduction()`; `403` se `!autorizarUri(request()->getPathInfo())`; senão `response()->file()` do caminho no disco com `Content-Type` `application/vnd.apple.mpegurl` ou `video/MP2T`. Registrada **antes** de `videos.auth` não conflita (`auth` não casa o regex por falta de extensão).
  - `EstadoItem` ganha `public ?array $video = null` (`['status' => string, 'status_label' => string, 'duracao_segundos' => ?int, 'url_master' => ?string]`; `url_master` só quando `PRONTO`), preenchido no `TrilhaService::estado()` e `estadoPrevia()` para itens `VIDEO`; `toArray()` inclui a chave `video`.

- [ ] **Step 1: Escrever `VideoAuthTest`**: `test_aluno_aprovado_com_item_liberado_recebe_204` (servidor, curso `SERVIDORES`, GET `/videos/auth` com header `X-Forwarded-Uri: /videos/{item}/{versao}/720p/00001.ts`); `test_cidadao_aprovado_recebe_204_pelo_guard_cidadao`; `test_sem_login_recebe_403`; `test_inscricao_pendente_recebe_403`; `test_item_de_pasta_travada_recebe_403` (Review Focus 3); `test_cidadao_em_curso_de_servidores_recebe_403` (Review Focus 3); `test_versao_desconhecida_recebe_403_e_anterior_recebe_204` (Review Focus 3); `test_uri_malformada_recebe_403` (`/videos/../x`, `/videos/abc/v/`) (Review Focus 3); `test_professor_do_curso_recebe_204`; `test_decisao_positiva_vai_para_o_cache_e_negativa_nao` (`Cache::spy()`: 204 -> `put` chamado uma vez; segunda chamada não consulta `TrilhaService` (mock `shouldReceive('itemLiberado')->once()`); 403 -> `put` nunca chamado); `test_rota_dev_serve_m3u8_so_fora_de_producao` (`config(['treinamento.video.servir_pelo_app' => true])`, `app()->detectEnvironment(fn () => 'local')` -> 200 com content-type do m3u8; `app()->detectEnvironment(fn () => 'production')` -> 404); `test_estado_do_item_video_traz_url_master_quando_pronto` (`estado.pastas[0].itens[0].video.url_master === "/videos/{item}/{versao}/master.m3u8"`; item `PROCESSANDO` -> `url_master null`).
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/VideoAuthTest.php`.
- [ ] **Step 3: Implementar** conforme Interfaces. As duas rotas entram em `routes/web.php` logo após o `require` de `treinamento-portal.php` (mesma região: dentro de `web`, fora de `auth`).
- [ ] **Step 4: Rodar**: PASS (12). Rodar `TEST tests/Feature/Treinamento/Ead/SalaEndpointsTest.php` (Fase 1) para conferir que `toArray()` com a chave nova não quebrou nada.
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

### Task 6: Frontend do professor: uploader em partes e acompanhamento

**Files:**
- Create: `SDC/resources/js/Composables/treinamento/useUploadEmPartes.js`, `SDC/resources/js/Components/Organisms/Treinamento/Conteudo/UploaderVideo.vue`
- Modify: `SDC/resources/js/Pages/Treinamento/Professor/Conteudo.vue`, `Controllers/Professor/ConteudoController.php` (itens `VIDEO` trazem `video_status`, `video_status_label`, `duracao_segundos`, `video_erro`, `upload_aberto` `{id, partes_recebidas, total_partes}|null`)

**Interfaces:**
- Consumes: rotas `treinamentos.professor.itens.video.*` (Tasks 2 e 3), via ziggy `route()`.
- Produces:
  - `useUploadEmPartes({ treinamentoId, itemId })` -> `{ enviar(file: File): Promise<void>, cancelar(): Promise<void>, progresso: Ref<number> /* 0..100 */, estado: Ref<'ocioso'|'enviando'|'finalizando'|'enviado'|'erro'|'cancelado'>, erro: Ref<string|null> }`. `enviar`: POST `upload.abrir` (`nome_original`, `tamanho_total`, `mime` = `file.type`), depois `PUT upload.parte` para `n = 1..total_partes` **sequencialmente** com `file.slice((n-1)*tamanho_parte, n*tamanho_parte)` em `FormData` (campo `parte`), até 3 tentativas por parte com espera 1 s, 2 s, 4 s; depois POST `upload.finalizar`. Erro de validação (422) não é retentado. Usa `axios` (já disponível via `window.axios`).
  - `UploaderVideo.vue` props `{ treinamentoId: Number, item: Object }`, emite `atualizado(item)`: zona de arrastar/escolher (`accept="video/mp4,video/quicktime,video/x-matroska"`), valida tamanho no cliente (`> 2 GiB` mostra erro sem enviar), barra de progresso do envio; quando `item.video_status` é `ENVIADO`/`PROCESSANDO`, faz polling do `status` a cada 5 s (`setInterval`, limpo no `onUnmounted`) e emite `atualizado` a cada resposta; em `PRONTO` mostra duração formatada e os botões "Substituir vídeo" e "Remover"; em `FALHOU` mostra `video_erro` e "Tentar de novo" (`reprocessar`) além de "Substituir".
  - `Conteudo.vue`: o modal de item ganha `tipo = VIDEO` (sem campo de arquivo); o card do item `VIDEO` renderiza `<UploaderVideo>` e atualiza o item local no evento `atualizado`.

- [ ] **Step 1: Implementar** composable e componente conforme Interfaces; ajustar `ConteudoController` e `Conteudo.vue`.
- [ ] **Step 2: Build**: `cd SDC && npx vite build`; esperado sem erro.
- [ ] **Step 3: Smoke manual em dev** (Octane recarregado com `octane:reload`; `composer dump-autoload` no container antes, por causa das classes novas): como professor, criar item Vídeo, enviar um MP4 de ~50 MB; a barra sobe em 7 passos; aba Rede mostra PUTs de 8 MB e nenhum POST maior que isso; `video_uploads` fica `COMPLETO`; sem worker rodando, o item fica em "Enviado"; subir `docker compose -f docker/compose.dev.yml run --rm -e SUPERVISOR_PROFILE=videos queue` (ou o worker equivalente do dev) e ver o item passar a "Pronto" com a duração certa. Desligar a rede no meio de um envio e religar: a parte é retentada e o envio termina.
- [ ] **Step 4: Commit**

```bash
git add SDC/resources/js/Composables/treinamento/useUploadEmPartes.js SDC/resources/js/Components/Organisms/Treinamento/Conteudo/UploaderVideo.vue SDC/resources/js/Pages/Treinamento/Professor/Conteudo.vue SDC/app/Modules/Treinamento/Controllers/Professor/ConteudoController.php
git commit -m "✨ feat(treinamento): uploader de video em partes na area do professor"
```

---

### Task 7: Frontend do aluno: player HLS e progresso por tempo

**Files:**
- Create: `SDC/resources/js/Components/Organisms/Treinamento/Sala/PlayerVideo.vue`, `SDC/resources/js/Composables/treinamento/useProgressoVideo.js`
- Modify: `SDC/resources/js/Templates/Treinamento/SalaDeAulaTemplate.vue`, `SDC/package.json` (`hls.js@1.7.4`)

**Interfaces:**
- Consumes: `estado.pastas[].itens[].video` (`status`, `status_label`, `duracao_segundos`, `url_master`) e `posicao`/`status` do item (Task 4 e Fase 1); `useRotasSala().progresso(itemId)` (Fase 1).
- Produces:
  - `PlayerVideo.vue` props `{ urlMaster: String, posicaoInicial: Number, duracao: Number, concluido: Boolean }`, emite `posicao(segundos: number)` e `terminou()`: `<video controls playsinline>`; se `Hls.isSupported()` usa `new Hls({ enableWorker: true })` com `loadSource/attachMedia`, senão atribui `src` direto (Safari); em `loadedmetadata`, `currentTime = posicaoInicial` quando `!concluido && posicaoInicial > 0`; emite `posicao(Math.floor(currentTime))` a cada 15 s enquanto toca, em `pause` e em `visibilitychange` (hidden); em `ended` emite `terminou()`; `hls.destroy()` no `onUnmounted`.
  - `useProgressoVideo({ enviar: (posicao: number) => Promise<void>, duracao: number })` -> `{ aoPosicao(segundos), aoTerminar() }`: `aoPosicao` só envia quando `segundos > ultimaEnviada`; `aoTerminar` envia `duracao`.
  - `SalaDeAulaTemplate.vue`: para `item.tipo === 'VIDEO'`, se `item.video.url_master` renderiza `<PlayerVideo>` ligado ao `useProgressoVideo` (reusando a função que hoje envia o progresso do PDF e substitui `estado` pela resposta); senão mostra o cartão "Vídeo em preparação" com `video.status_label`. Em `escopo === 'previa'` não envia progresso (como o PDF).

- [ ] **Step 1: Instalar e implementar**: `cd SDC && npm install hls.js@1.7.4`; componente, composable e template conforme Interfaces.
- [ ] **Step 2: Build**: `npx vite build`; sem erro.
- [ ] **Step 3: Smoke manual em dev** (`TREINAMENTO_VIDEO_SERVIR_PELO_APP=true` no `.env` do dev, `config:clear`): aluno servidor abre o item de vídeo processado na Task 6, o player toca, a aba Rede mostra `master.m3u8` e `.ts` em `/videos/...` com 200 e, antes deles, nenhum 403; fechar na metade e reabrir volta no mesmo ponto; assistir até o fim marca o item concluído e a pasta seguinte abre; cidadão repete pelo portal num curso `CIDADAOS`; na prévia do professor o vídeo toca e nada é gravado.
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
- Consumes: `VideoUpload`, `CaminhosVideo`, `PublicadorHls::removerVersao()` (Tasks 1 e 3).
- Produces:
  - `LimparVideosOrfaosJob` (fila `videos`, `handle(): void`): (1) uploads `ABERTO` com `expira_em < now()` -> `VideoUploadService::cancelar`; (2) `originais/{item}/*` de itens `PRONTO` quando `!manter_original`; (3) diretórios em `hls/{item}/` diferentes de `hls_versao` e de `.tmp-*` com menos de 2 h; (4) `storage/logs/ffmpeg/*.log` com mais de `log_ffmpeg_dias`. Devolve nada; loga contagens no canal `treinamento`.
  - `routes/console.php`: `Schedule::job(new LimparVideosOrfaosJob)->dailyAt('03:30')->onOneServer()`.
  - `VideoMetrics` (Redis, prefixo `treinamento:video:`): `registrarProcessamento(bool $sucesso, int $segundos, int $bytesHls): void` (`incr processados:{sucesso|falha}`, `incrby processamento_segundos_sum`, `incr processamento_segundos_count`, `incrby hls_bytes` ou `decrby` ao remover versão via `registrarRemocao(int $bytes)`); `linhas(): array` no formato Prometheus text com `# HELP`/`# TYPE`: `sdc_treinamento_video_processados_total{resultado="sucesso"|"falha"}` (counter), `sdc_treinamento_video_processamento_segundos_sum` e `_count` (summary parcial), `sdc_treinamento_video_hls_bytes` (gauge). Em erro de Redis, retorna `[]`.
  - `MetricsController`: `$linhas = array_merge($linhas, SwooleRuntimeMetrics::linhas(), VideoMetrics::linhas());`.

- [ ] **Step 1: Escrever `LimpezaVideosTest`** (`Storage::fake`): `test_upload_expirado_e_cancelado_e_partes_apagadas`; `test_upload_vigente_e_mantido`; `test_original_de_item_pronto_e_apagado_sem_manter_original`; `test_versao_antiga_e_apagada_e_atual_mantida`; `test_tmp_recente_e_preservado`; `test_metricas_formatam_linhas_prometheus` (após `registrarProcessamento(true, 90, 1000)`, `linhas()` contém `sdc_treinamento_video_processados_total{resultado="sucesso"} 1` e `sdc_treinamento_video_hls_bytes 1000`).
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/Video/LimpezaVideosTest.php`.
- [ ] **Step 3: Implementar**; rodar; PASS (6). Conferir `docker exec newsdc_dev_app php artisan schedule:list | grep -i video`.
- [ ] **Step 4: Commit**

```bash
git add SDC/routes/console.php SDC/app/Http/Controllers/Api/MetricsController.php SDC/app/Modules/Treinamento/Jobs/LimparVideosOrfaosJob.php SDC/app/Modules/Treinamento/Services/Video/VideoMetrics.php
git commit -m "✨ feat(treinamento): limpeza de videos orfaos e metricas do pipeline"
```

---

### Task 9: Verificação da fase (dev, homolog e produção externa)

- [ ] **Step 1: Suite**: `TEST tests/Feature/Treinamento`; tudo verde (Fase 1 + os 6 arquivos desta fase).
- [ ] **Step 2: Rotas**: `docker exec newsdc_dev_app php artisan route:list --path=video` lista as 7 rotas do professor, `videos.auth` e `videos.arquivo-dev`; `--path=treinamentos/aluno` inalterado.
- [ ] **Step 3: Homolog ponta a ponta com Caddy real** (`compose.homolog.yml`, `TREINAMENTO_VIDEO_SERVIR_PELO_APP` ausente): professor sobe uma aula de 2 min e uma de 1 h; acompanhar `docker stats newsdc_homolog_queue_videos` (CPU perto de 150%, não mais) e o p95 de `/health` do app durante o processamento (não deve subir); `master.m3u8` da aula de 1 h tem as duas renditions; aluno toca pelo Caddy em `:8090`, troca de qualidade com a rede limitada no DevTools; `curl` anônimo no `.ts` recebe 403; anotar o tempo de processamento e o tamanho de `hls/{item}` para calibrar `preset`/renditions (§8.3).
- [ ] **Step 4: Deploy em produção** seguindo a ordem de §11 (migration, config, imagem `sdc-queue`, `docker stack deploy` por causa do serviço e do volume novos, app). Antes do `stack deploy`, Matheus confirma no R760 `docker volume inspect sdc_app_storage` e a presença/ausência de `ANEXOS_ROOT` em `docker service inspect sdc_app`, e escolhe a linha certa da tabela de §11.
- [ ] **Step 5: Smoke externo em produção** (§11, passo 6): de uma conexão fora da rede corporativa, professor sobe uma aula real e aluno assiste; se alguma parte de 8 MB ou segmento falhar por limite no caminho, baixar `TREINAMENTO_VIDEO_PARTE_BYTES` e/ou `hls_time` e repetir, sem mudança de código.
- [ ] **Step 6: Limpar** os cursos, itens e arquivos de smoke em dev e homolog, e listar ao usuário o que foi apagado.
