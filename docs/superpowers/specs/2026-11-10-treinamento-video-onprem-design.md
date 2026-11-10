# Design: Vídeo do Treinamento EAD on-premise (upload em partes, FFmpeg na fila, HLS pelo Caddy)

**Data:** 2026-11-10
**Autor:** Matheus Estrela (com assistência Claude)
**Status:** Proposto, aguardando revisão
**Módulo:** `App\Modules\Treinamento`
**Substitui:** [2026-05-28-treinamento-pipeline-video-design.md](2026-05-28-treinamento-pipeline-video-design.md) (desenhado para Azure App Service + FrankenPHP; a máquina de estados, os cenários de erro e a limpeza de órfãos foram herdados de lá)
**Relacionado:** [2026-11-09-treinamento-ead-design.md](2026-11-09-treinamento-ead-design.md) (vídeo é a **Fase 2** daquele desenho) e `docs/superpowers/plans/2026-11-10-treinamento-ead-fase1-base.md` (schema, `modulo_itens`, `progresso_itens`, área do professor e sala de aula que este spec reaproveita)

Legenda: **[decidido]** = decidido pelo Matheus no brainstorming de 2026-11-10. **[escolha do design]** = decisão tomada no design, pode ser revista. Sem marca = contexto ou fato do código.

---

## 1. Intenção

Permitir que o professor suba a videoaula pelo próprio site e que o aluno a assista dentro da sala de aula, com retomada por tempo, **sem passar bytes de vídeo pelo Octane/Swoole** e **sem nenhum serviço de nuvem**. O alvo de produção é só o on-premise [decidido]: a VM Dell R760 com Docker Swarm de **um nó** [decidido], Octane/Swoole em 2 réplicas, container `queue` com supervisor, Reverb e Caddy na borda, Postgres e Redis na stack `sdc-data`.

O vídeo passa a vir **antes** das provas e do certificado [decidido]: a ordem das fases do EAD fica Base → Vídeo → Comprovação.

## 2. Estado atual (o que este spec assume pronto)

Entregue pela Fase 1 (plano de 2026-11-10):

- Banco dedicado `sdc_treinamento`, schema `treinamento`, migration principal consolidada `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php` (toda DDL nova entra lá, idempotente).
- `modulo_itens` com `tipo` já aceitando `VIDEO` no CHECK, `TipoItem` ainda sem o case `VIDEO`.
- `progresso_itens.posicao` inteiro, monotônico (nunca regride, nunca passa do total), `ProgressoService::registrarPosicao()` e `concluir()`.
- Área do professor (`/treinamentos/professor/...`, middleware `LecionaCurso`), página Conteúdo com itens, sala de aula compartilhada (`SalaDeAulaTemplate.vue`) com o visualizador de PDF ocupando a área principal.
- Disco `treinamentos` via `$azureOrLocal` (que, sem `AZURE_STORAGE_CONNECTION_STRING`, cai em `ANEXOS_ROOT/TREINAMENTOS` ou `storage/app/treinamentos`).

Infra existente que o spec usa como está:

- `docker/Dockerfile.queue` (Alpine) + `docker/supervisor/*.conf`: padrão de worker isolado por fila com timeout próprio (`medalhao-worker.conf`, conexão `redis-medalhao` em `config/queue.php` com `retry_after` maior que o `--timeout`).
- `docker/jenkins/stack.app.onpremise.yml`: `app` 2x (1.75 CPU / 2.25 GB, `OCTANE_TASK_WORKERS=2`), `queue` 1x (1 CPU / 1.5 GB), `caddy` 1x na `sdc_edge`, volume `app_storage` montado em `app` e `queue` em `/var/www/storage/app`. **O Caddy não monta storage nenhum hoje.**
- `docker/caddy/Caddyfile`: `reverse_proxy app:8000` e `/app/*` para o Reverb. Em produção o arquivo vive em `/opt/sdc/caddy/Caddyfile` (bind mount).
- Swoole 6.2.1: `upload_max_filesize=64M`, `post_max_size=64M`, `max_execution_time=30`; `config/octane.php` **não** define `package_max_length`, e o Octane fixa 10 MB (`vendor/laravel/octane/src/Commands/StartSwooleCommand.php:131`, conferido no container de homolog em 2026-11-10; ver risco em §13).
- Reverb já em produção para notificações em tempo real.

## 3. Objetivos

1. Professor do curso sobe um MP4 pela página Conteúdo e acompanha o processamento (enviando → processando → pronto / falhou) [decidido: upload e conversão pelo site].
2. Conversão para HLS em **processo da fila**, nunca em worker HTTP nem task worker do Swoole [decidido, anti-objetivo do spec EAD].
3. Segmentos entregues pelo **Caddy** direto do disco, só para quem pode assistir [decidido: proteger para usuário logado].
4. Aluno assiste na sala de aula, retoma de onde parou, e o item conta como concluído ao chegar ao fim; mesmo `progresso_itens` da Fase 1.
5. Tudo roda num único nó, com disco local compartilhado entre `app`, `queue` e `caddy`.

## 4. Não objetivos

- MinIO/S3, Azure ou qualquer storage de objeto. Fica registrado em §12 como caminho se um dia houver segundo nó.
- Nginx. O Caddy já é a borda e faz `file_server` + `forward_auth`.
- Task workers do Swoole para FFmpeg.
- DRM, marca d'água por aluno, legendas, download offline, transmissão ao vivo.
- Vídeo externo (YouTube/Vimeo embed). Pode ser item futuro, não faz parte deste spec.
- Trocar o player da Fase 1 (PDF) ou a regra de anti-burla; o vídeo segue a mesma postura: a prova é a trava real.

## 5. Anti-objetivos (falha mesmo que "funcione")

- Um upload de 2 GB passar inteiro por uma requisição do Octane, ou segurar um worker HTTP por mais de alguns segundos.
- FFmpeg competindo com os workers HTTP pela CPU do container `app`, ou morrendo no `octane:reload` / rolling update.
- Segmento `.ts` ou manifesto acessível por URL adivinhável sem sessão válida e inscrição aprovada.
- Aluno em uma réplica do Octane não enxergar as partes enviadas pela outra (disco não compartilhado).
- Troca de vídeo em item com alunos assistindo quebrar a reprodução no meio (versão antiga removida antes da nova estar pronta).
- Duas implementações do player ou do upload, uma por escopo (servidor x cidadão).

## 6. Fluxo ponta a ponta

```
Professor (navegador)                 Octane (2 replicas)              queue (supervisor)            Caddy
--------------------                  -------------------              ------------------            -----
1. escolhe MP4 ----- POST upload ---> cria video_uploads (ABERTO)
2. fatia em partes   PUT parte n ---> grava uploads/{id}/{n}.part no disco treinamentos (compartilhado)
   (8 MB, sequencial, retry 3x)
3. fecha ----------- POST finalizar-> marca COMPLETO, item ENVIADO, dispatch ProcessarVideoItemJob (fila videos)
4. acompanha status  GET status ---> le modulo_itens.video_status      5. junta partes -> originais/{item}/{uuid}.mp4
   (polling 5 s ou evento Reverb)                                       6. ffprobe valida e le duracao
                                                                        7. ffmpeg -> hls/{item}/.tmp-{uuid}/ (480p, 720p)
                                                                        8. rename atomico -> hls/{item}/{versao}/
                                                                        9. item PRONTO, hls_versao, duracao; apaga versao anterior e original

Aluno (navegador)                     Octane                                                         Caddy
-----------------                     ------                                                         -----
10. abre item VIDEO na sala ---------> EstadoItem traz url do master e posicao salva
11. HLS.js pede /videos/{item}/{versao}/master.m3u8 ------------------------------------------------> forward_auth GET /videos/auth
                                      valida sessao + inscricao aprovada + item liberado (cache 5 min) <-- X-Forwarded-Uri
                                      204 ou 403 -----------------------------------------------------> file_server le do disco
12. a cada 15 s / pause / ended ----> POST progresso (mesmo endpoint da Fase 1, posicao em segundos)
```

## 7. Upload em partes

### 7.1 Protocolo [escolha do design]

Tudo sob o escopo do professor (`/treinamentos/professor/cursos/{treinamento}/itens/{item}/video/...`), protegido por `LecionaCurso` (Fase 1). Admin com `treinamento.cursos.editar` também pode [escolha do design].

| Método | Rota | Faz | Resposta |
|---|---|---|---|
| POST | `.../video/upload` | abre a sessão: `nome_original`, `tamanho_total`, `mime` | `{ upload_id, tamanho_parte, total_partes, expira_em }` |
| PUT | `.../video/upload/{upload}/partes/{n}` | recebe **uma** parte (multipart, campo `parte`), `n` de 1 a `total_partes` | `{ partes_recebidas, tamanho_recebido }` |
| POST | `.../video/upload/{upload}/finalizar` | confere que todas as partes chegaram com o tamanho esperado, marca `COMPLETO`, item `ENVIADO`, despacha o job | `{ video_status: 'ENVIADO' }` |
| DELETE | `.../video/upload/{upload}` | cancela e apaga as partes | 204 |
| GET | `.../video/status` | `video_status`, `duracao_segundos`, `video_erro`, `processado_em` | JSON |
| POST | `.../video/reprocessar` | só em `FALHOU`; redespacha o job (original ainda existe) | `{ video_status: 'PROCESSANDO' }` |
| DELETE | `.../video` | remove HLS, original e volta o item para `SEM_VIDEO` | 204 |

Regras:

- **Tamanho da parte: 8 MB** [escolha do design]: cabe no `package_max_length` do Octane mesmo no padrão de 10 MB, e mantém baixa a memória que cada worker HTTP segura por requisição (o Swoole carrega o corpo inteiro em RAM). Vem na resposta do POST para o front não precisar conhecer o número. Configurável em `config/treinamento.php`.
- **Limite por vídeo: 2 GB** [escolha do design], `mime` aceito `video/mp4`, `video/quicktime`, `video/x-matroska`; a validação real de conteúdo é do `ffprobe` no worker.
- Cada parte é gravada como arquivo próprio `uploads/{upload_id}/{n}.part` no disco `treinamentos`. Reenviar a mesma parte sobrescreve (idempotente); o servidor confere `tamanho` da parte (todas iguais a `tamanho_parte`, exceto a última). Checksum por parte fica a critério do builder; o `ffprobe` pega corrupção grosseira.
- A requisição de parte **não** concatena nada: só move o arquivo temporário do PHP para o disco e atualiza contadores. Fica dentro dos 30 s de `max_execution_time` com folga.
- Sessão de upload expira em **24 h** [escolha do design]; o job de limpeza (§10) apaga as partes.
- Só **uma** sessão `ABERTO` por item; abrir outra cancela a anterior. Finalizar um item que já está `PRONTO` cria uma **nova versão** (§8.4); o vídeo atual continua servido até a nova ficar pronta.
- Auditoria em `RegistroDeAcao` (padrão do módulo) em finalizar, reprocessar e remover.

Por que não SAS/presigned: não há storage de objeto no on-prem, e subir um MinIO só para isso adiciona serviço, backup e rede a uma VM de nó único. Por que não TUS: o protocolo resolve o mesmo problema com mais superfície (cabeçalhos próprios, extensões) e o front já teria de ser escrito à mão; partes numeradas com reenvio bastam.

### 7.2 Frontend do professor

- `Composables/treinamento/useUploadEmPartes.js`: fatia o `File` com `slice`, envia **sequencialmente** (uma parte por vez, para não disputar com as duas réplicas e para o progresso ser linear), até 3 tentativas por parte com backoff, expõe `progresso` (%), `estado`, `cancelar()`. Retomada após recarregar a página **não** é objetivo (o professor refaz o envio) [escolha do design].
- `Components/Organisms/Treinamento/Conteudo/UploaderVideo.vue`: usado no item `VIDEO` da página Conteúdo da Fase 1: arrastar/escolher arquivo, barra de progresso do envio, depois estado do processamento. Acompanhamento do processamento: **polling de 5 s** no `GET status` enquanto `ENVIADO`/`PROCESSANDO` [escolha do design]; evento Reverb é melhoria opcional, o builder decide se vale usar o canal privado já existente do módulo de Notificações.

## 8. Processamento

### 8.1 Worker

- `docker/Dockerfile.queue`: `apk add --no-cache ffmpeg` (o pacote traz `ffprobe`). A imagem `sdc-queue` continua única.
- `docker/supervisor/videos-worker.conf`, espelho do `medalhao-worker.conf`:
  `php artisan queue:work redis-videos --queue=videos --sleep=5 --tries=3 --max-time=7200 --timeout=3600`, `numprocs=1`, `stopwaitsecs=900`, `user=www-data`.
- `config/queue.php`: conexão `redis-videos` com `queue => 'videos'`, `retry_after => 3700` (maior que o `--timeout`), `block_for => 5`.
- **Serviço próprio no Swarm: `queue-videos`** [escolha do design, após revisão]. Mesma imagem `sdc-queue`, mas o supervisor carrega **só** o `videos-worker.conf` (o entrypoint escolhe o conjunto de confs por variável, ex.: `SUPERVISOR_PROFILE=videos`). Limites **1.5 CPU / 2 GB**; o `queue` atual fica como está (1 CPU / 1.5 GB) e continua com `default`, `medalhao`, `ranking` e scheduler. Motivo: o FFmpeg não disputa CPU com o ETL do medalhão nem segura o deploy do `queue`, e o orçamento fica explícito. Stack total: 2 x 1.75 + 1 + 1.5 + 0.5 + 0.5 = **7 das 8 vCPUs**; o FFmpeg roda com `nice -n 10` e `-threads 2`. Em `compose.homolog.yml` entra o mesmo serviço, ao lado de `queue_medalhao` e `queue_ranking`, que já seguem esse padrão.
- **Deploy durante um processamento** [escolha do design]: `stop_grace_period` do `queue-videos` fica em 16 min como o do `queue`; um rolling update no meio de uma aula de 1 h mata o FFmpeg. O job é retomável por etapa (original já montado é reaproveitado) e `tries=3` absorve a morte; o custo é só o transcode recomeçar. Subir o grace para 1 h faria cada deploy do serviço esperar até 1 h, o que não compensa.
- O `queue-videos` monta o mesmo `app_storage` que `app` e `queue`: o worker lê as partes e grava o HLS no mesmo lugar que o Octane enxerga.

### 8.2 Job `ProcessarVideoItemJob`

`ShouldBeUnique` por `modulo_item_id` (`uniqueFor` 7200 s), `tries=2`, `backoff=[120]`. Passos, cada um com log estruturado (`stage`, `item_id`, `duracao_ms`):

1. **Montar o original**: concatena `uploads/{upload_id}/1..N.part` por stream em `originais/{item}/{uuid}.mp4`, confere o tamanho total, apaga as partes. Idempotente: se o original já existe com o tamanho certo, pula.
2. **Validar**: `ffprobe -v error -show_streams -show_format -of json`. Exige pelo menos um stream de vídeo; lê `duration`, largura e altura. Falha aqui vira `FALHOU` sem retry (arquivo inválido).
3. **Transcodificar**: `ffmpeg` com **uma** invocação gerando todas as renditions (`-filter_complex split`, `-var_stream_map`, `-master_pl_name master.m3u8`), para decodificar o original só uma vez [escolha do design, o builder pode cair para uma invocação por rendition se a sintaxe der trabalho]. `libx264 -preset veryfast -crf 23 -maxrate/-bufsize` por rendition, `aac 128k`, `-hls_time 6 -hls_playlist_type vod -hls_segment_type mpegts`. Renditions que excedem a altura do original são puladas (não faz upscale).
4. **Publicar**: tudo é escrito em `hls/{item}/.tmp-{uuid}/`; ao terminar, `rename` para `hls/{item}/{versao}/` (`versao` = timestamp ou sequência). Só então o item vira `PRONTO` com `hls_versao`, `duracao_segundos`, `video_processado_em`. Depois disso apaga a versão anterior e, se `manter_original=false` (padrão), o original.
5. Em falha após as tentativas: `FALHOU`, `video_erro` com os últimos 1000 caracteres do stderr, `video_tentativas` incrementado; o original fica para permitir `reprocessar`.

Saída de stderr completa do FFmpeg em `storage/logs/ffmpeg/{item}-{versao}.log` (retenção 30 dias, mesma limpeza de §10).

### 8.3 Renditions e orçamento [escolha do design]

| Rendition | Resolução | Vídeo | Padrão |
|---|---|---|---|
| 480p | 854x480 | 800 kbps | ligada |
| 720p | 1280x720 | 2500 kbps | ligada |
| 1080p | 1920x1080 | 5000 kbps | **desligada** (`config`), pelo custo de CPU no R760 |

Estimativas para calibrar, não promessas: 1 h de aula em 480p+720p ocupa ~1,6 GB de HLS e leva entre 1x e 2x o tempo real para processar com 2 threads em `veryfast`. Vale medir na primeira aula real e ajustar `preset`/renditions em config, sem mexer em código.

### 8.4 Máquina de estados de `modulo_itens.video_status`

```
SEM_VIDEO --POST upload--> UPLOAD_ABERTO --finalizar--> ENVIADO --job pega--> PROCESSANDO --ok--> PRONTO
    ^                          |                                                   |               |
    |            cancelar / expirou 24 h                                        falhou          novo upload
    |                          v                                                   v               v
    +<------------------- SEM_VIDEO                                             FALHOU --reprocessar--> PROCESSANDO
    |                                                                              |
    +<------------------------------ DELETE /video (de qualquer estado) <----------+
```

Com um vídeo já `PRONTO`, um novo upload passa o item por `UPLOAD_ABERTO -> ENVIADO -> PROCESSANDO` **mantendo `hls_versao` da versão atual**; o aluno continua assistindo a versão antiga até a troca atômica. Se a nova falhar, o item volta a `PRONTO` com a versão antiga e `video_erro` preenchido para o professor ver.

## 9. Entrega protegida pelo Caddy

### 9.1 Caddyfile [escolha do design]

```
handle_path /videos/* {
    forward_auth app:8000 {
        uri /videos/auth
    }
    root * /srv/treinamentos/hls
    header Cache-Control "private, max-age=3600"
    file_server
}
```

- `handle_path` remove o prefixo; o disco fica em `/srv/treinamentos/hls/{item}/{versao}/...`, montado no Caddy **somente leitura** a partir da mesma origem física do disco `treinamentos` (§11).
- `forward_auth` manda `X-Forwarded-Uri` com o caminho original; o Octane responde 2xx (libera) ou 403. O Caddy nunca encaminha o corpo do arquivo ao Octane.
- O manifesto e os segmentos são servidos pelo `file_server` do Caddy, com cache de página do kernel (os segmentos quentes ficam em RAM). `private` impede cache compartilhado em proxies no caminho.
- Como `/videos/` está no mesmo domínio do app, o navegador envia o cookie de sessão sozinho; HLS.js não precisa de `withCredentials`.

Alternativas descartadas: URL assinada verificada pelo Caddy exige plugin (build próprio do Caddy); Nginx `secure_link` traria um segundo edge; servir pelo Laravel (`$disk->response()`) viola o anti-objetivo.

### 9.2 Endpoint `/videos/auth` no Octane

- Rota própria em arquivo de rotas leve (só sessão, sem Inertia, sem CSRF, sem throttle pesado), `GET`, responde `204` ou `403` sem corpo.
- Extrai `{item}` de `X-Forwarded-Uri` (`/videos/{item}/{versao}/...`); qualquer outro formato é `403`.
- Resolve quem está logado nos **dois guards** (`web` e `cidadao`) e pergunta ao `TrilhaService` da Fase 1 se o item está liberado para a inscrição aprovada dessa pessoa; professor do curso e admin também passam.
- **Cache da decisão: 5 min no Redis**, chave `(id da sessão, item)` [escolha do design]. Produção usa `SESSION_DRIVER=cookie`, mas o id da sessão continua existindo dentro do payload do cookie (`session()->getId()`), e é regenerado no login e invalidado no logout: depois do logout a próxima chamada não tem sessão autenticada e não bate na chave antiga, então o vídeo corta na hora. Um aluno gera um `auth` a cada segmento (~6 s), então quase todo hit sai do cache. Consequência aceita: inscrição cancelada (sem logout) ainda vê até 5 min de vídeo.
- Também valida que `{versao}` é a `hls_versao` atual ou a imediatamente anterior durante a troca (evita servir diretório órfão).

### 9.3 Ambiente de dev

`compose.dev.yml` não tem Caddy. Para o desenvolvedor testar sem subir um, existe uma rota de contingência no Laravel que faz `response()->file()` do segmento pedido, ligada **apenas** por `TREINAMENTO_VIDEO_SERVIR_PELO_APP=true` e recusada quando `APP_ENV=production` [escolha do design]. Ela reaproveita o mesmo serviço de autorização do `/videos/auth`, então não é segunda lógica, só segunda saída. `compose.homolog.yml` tem Caddy e usa a configuração real.

## 10. Limpeza e observabilidade

- `LimparVideosOrfaosJob` diário (03:00): sessões `ABERTO` com `expira_em` vencido (apaga partes, item volta a `SEM_VIDEO` se não tinha versão); `originais/` de itens `PRONTO` quando `manter_original=false`; diretórios em `hls/{item}/` que não são a `hls_versao` atual; logs de FFmpeg com mais de 30 dias.
- Log em `Log::channel('treinamento')` (criar se a Fase 1 não criou), com `item_id`, `stage`, `duracao_ms`, `bytes`.
- Métricas no exporter já existente: `treinamento_video_processados_total{resultado}`, `treinamento_video_processamento_segundos` (histograma), `treinamento_video_hls_bytes` (gauge). O `/videos/auth` entra nas métricas de rota normais do Octane.
- Alerta sugerido no Prometheus: fila `videos` com job há mais de 2 h, ou item `PROCESSANDO` há mais de 3 h.

## 11. Deploy e infra por ambiente

| Ambiente | Raiz do disco `treinamentos` | Montagem no Caddy |
|---|---|---|
| Swarm on-prem (`stack.app.onpremise.yml`) | volume `app_storage` -> `/var/www/storage/app/treinamentos` | `app_storage` montado em `/srv/treinamentos:ro` (subpasta `hls` via `root`) |
| `docker-compose.yml` (bind `ANEXOS_ROOT=/data/anexos`) | `/data/anexos/TREINAMENTOS` | `${ANEXOS_HOST_PATH}/TREINAMENTOS:/srv/treinamentos:ro` |
| Homolog (`compose.homolog.yml`) | `homolog_storage` | mesmo volume em `/srv/treinamentos:ro` |
| Dev (`compose.dev.yml`) | bind local | sem Caddy; rota de contingência (§9.3) |

Observação: hoje o stack do Swarm não define `ANEXOS_ROOT`, enquanto `docker-compose.yml` define. Antes do passo 4 da ordem de subida, quem tem acesso ao R760 (Matheus) confirma com `docker service inspect sdc_app` e `docker volume inspect sdc_app_storage` qual linha da tabela vale e se `app_storage` é volume local do nó; o builder só prepara os dois arquivos.

Ordem de subida:

1. Migration consolidada (colunas de vídeo em `modulo_itens` + tabela `video_uploads`).
2. `config/octane.php` com `package_max_length` explícito (64 MB) e `config/treinamento.php`; `config/queue.php` com `redis-videos`.
3. Imagem `sdc-queue` com FFmpeg, `videos-worker.conf` e perfil de supervisor; serviço `queue-videos` no stack.
4. Caddyfile com o bloco `/videos/*` e o volume no serviço `caddy` (exige `docker stack deploy`, não só `service update --image`, porque entra serviço novo e muda volume).
5. App com rotas, job, player; smoke test com aula curta e uma de 1 h em homolog, medindo CPU do `queue-videos` e latência do app durante o processamento.
6. **Smoke test em produção, de fora da rede corporativa** [decidido]: upload de uma aula real e reprodução a partir de uma conexão externa (4G ou rede doméstica) logo após o deploy, porque não está confirmado o que existe entre a internet e o Caddy no domínio público (proxy, WAF ou balanceador da Prodemge). Se uma parte de 8 MB ou um segmento `.ts` for barrado ou sofrer timeout nesse caminho, o `tamanho_parte` e o `hls_time` baixam por config, sem mudança de código; por isso os dois são configuração e o POST de abertura devolve o tamanho ao front.

## 12. Modelo de dados (consolidado na migration principal)

`modulo_itens` (+ colunas, todas nullable salvo `video_status`):

- `video_status varchar(20) NOT NULL DEFAULT 'SEM_VIDEO'` CHECK (`SEM_VIDEO`, `UPLOAD_ABERTO`, `ENVIADO`, `PROCESSANDO`, `PRONTO`, `FALHOU`), índice.
- `video_original_path varchar(500)`, `video_tamanho_bytes bigint`, `hls_versao varchar(40)`, `duracao_segundos integer`, `video_largura integer`, `video_altura integer`, `video_processado_em timestamp(0)`, `video_erro text`, `video_tentativas smallint NOT NULL DEFAULT 0`.

`video_uploads` (nova):

- `id uuid PK`, `modulo_item_id` FK -> `modulo_itens` ON DELETE CASCADE, `user_id bigint NOT NULL` (sem FK, outro banco, índice `_fkidx`), `nome_original varchar(255)`, `mime varchar(100)`, `tamanho_total bigint`, `tamanho_parte integer`, `total_partes integer`, `partes_recebidas integer NOT NULL DEFAULT 0`, `tamanho_recebido bigint NOT NULL DEFAULT 0`, `status varchar(20)` CHECK (`ABERTO`, `COMPLETO`, `CANCELADO`), `expira_em timestamp(0)`, timestamps. Índice parcial `(modulo_item_id) WHERE status = 'ABERTO'` para garantir uma sessão aberta por item.

Enums: `TipoItem::VIDEO`; `VideoStatus` com `podeReprocessar()`, `estaPronto()`, `emProcessamento()`.

`progresso_itens` não muda: para `VIDEO`, `posicao` é o **maior segundo alcançado** e o total é `duracao_segundos` [escolha do design, mesma regra monotônica do PDF].

Se houver segundo nó no futuro: o disco `treinamentos` vira NFS ou MinIO, o `forward_auth` e o job não mudam; só a montagem do Caddy e o driver do disco.

## 13. Riscos

| Risco | Mitigação |
|---|---|
| `package_max_length` do Octane (10 MB, confirmado) derrubar a conexão em uploads maiores, inclusive os PDFs de 50 MB prometidos na Fase 1 | Definir explicitamente em `config/octane.php` (64 MB, alinhado ao php.ini, para a Fase 1 cumprir os 50 MB) e manter a parte de vídeo em 8 MB mesmo assim, para não segurar memória do worker; a Fase 1 ganha um teste de upload de PDF acima de 10 MB |
| Disco cheio por originais e versões antigas | `manter_original=false`, troca atômica apaga versão anterior, limpeza diária, gauge de bytes com alerta |
| FFmpeg roubar CPU do app no mesmo nó | `nice`, `-threads 2`, limite de 2 CPU no `queue`, `numprocs=1`; medir em homolog |
| Réplicas do Octane com discos diferentes | É o mesmo volume `app_storage` nos dois; o teste de fumaça envia partes alternando réplicas (via Caddy) e confere o `finalizar` |
| Proxy/WAF desconhecido entre a internet e o Caddy limitar corpo ou timeout | Smoke test externo em produção (§11, passo 6); `tamanho_parte` e `hls_time` em config |
| Aluno sem cookie (portal em outro domínio) | Portal e SDC são o mesmo domínio hoje; se mudar, `/videos/*` precisa de `CORS` + `withCredentials` no HLS.js |
| Vídeo em formato exótico (HEVC 10 bits, VFR) | `ffprobe` valida; `-pix_fmt yuv420p` e `-vsync cfr` no transcode; falha vira `FALHOU` legível para o professor |
| Troca de vídeo durante aula | Versões por diretório + `auth` aceita a versão anterior durante a janela de troca |

## 14. Player e progresso do aluno

- `Components/Organisms/Treinamento/Sala/PlayerVideo.vue` no lugar do `VisualizadorPdf.vue` quando `item.tipo === 'VIDEO'`; **HLS.js** (dependência npm nova) com fallback nativo no Safari [escolha do design, mais leve que Video.js para um player só].
- `Composables/treinamento/useProgressoVideo.js`: ao carregar, `seek` para `posicao` salva (se não concluído); envia `posicao` (segundos inteiros) a cada 15 s, em `pause`, em `visibilitychange` e em `ended` (que envia `duracao_segundos`, fechando o item). Usa o **mesmo endpoint de progresso da Fase 1**.
- **Generalização do `ProgressoService`**: o plano da Fase 1 escreveu `registrarPosicao()` exigindo `tipo = PDF` e usando `total_paginas` como teto. A Fase 1 ainda não foi mergeada (branch `feat/treinamento-ead`), então a **primeira task do plano de vídeo** troca isso por um total por tipo no próprio item (ex.: `ModuloItem::totalProgresso(): ?int`, `PDF` -> `total_paginas`, `VIDEO` -> `duracao_segundos`), sem `if` por tipo no serviço; se a Fase 1 ainda estiver em execução quando o plano de vídeo for escrito, a mudança entra direto na task de progresso da Fase 1. Aluno que pula para o fim conclui o item, como no PDF: a prova é a trava (§4).
- `EstadoItem` da Fase 1 ganha, para `VIDEO`, `video_status`, `duracao_segundos` e `url_master` (`/videos/{item}/{versao}/master.m3u8`). Item `PRONTO` é o único que aparece como assistível; nos demais estados o aluno vê "vídeo em preparação" e o item não bloqueia a prova se for opcional.

## 15. Testes (locais, fora do commit)

- Upload: professor de outro curso recebe 403; parte maior que `tamanho_parte` ou `n` fora da faixa recebe 422; reenvio da mesma parte não duplica contagem; `finalizar` com parte faltando recebe 422 e não despacha; `finalizar` correto despacha `ProcessarVideoItemJob` na fila `videos` (`Queue::fake`).
- Job: com `Process::fake` para `ffprobe`/`ffmpeg`, fluxo feliz publica versão e apaga partes/original; `ffprobe` sem stream de vídeo vira `FALHOU` sem retry; segunda execução sobre item já `PRONTO` da mesma sessão é no-op (idempotência); falha de nova versão mantém a anterior.
- `/videos/auth`: 204 para aluno com inscrição aprovada e item liberado, 403 para pendente, para item travado e para URI malformada; professor do curso 204; segunda chamada sai do cache (`Cache::spy`).
- Progresso de vídeo: `posicao` acima de `duracao_segundos` é cortada; `ended` conclui; retrocesso não regride.
- Fumaça em homolog: aula de 2 min e aula de 1 h; conferir `master.m3u8` com duas renditions, player trocando qualidade, CPU do `queue` e p95 do app durante o processamento.

## 16. Deixado para o builder

- Nomes finais de rotas, classes e colunas; se `VideoStatus` e `video_uploads` ganham Resources próprios ou entram no `EstadoItem`.
- Uma invocação de FFmpeg com `var_stream_map` ou uma por rendition.
- Checksum por parte (sim/não) e se o upload envia 1 ou 2 partes em paralelo.
- Polling ou evento Reverb para o status do processamento.
- Formato de `hls_versao` e política exata de retenção da versão anterior (apagar logo após a troca ou após N minutos).
- Como o `root` do Caddy chega ao caminho certo em cada ambiente (tabela de §11), e se vale um `Caddyfile` único com variável de ambiente.
- Valores finais de `preset`, `crf`, `maxrate`, `hls_time`, a partir da medição em homolog.
