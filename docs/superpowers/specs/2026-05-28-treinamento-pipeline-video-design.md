# Design — Spec 1: Pipeline de Vídeo do Módulo de Treinamento

**Data:** 2026-05-28
**Autor:** Matheus Estrela (com assistência Claude)
**Status:** Proposto — aguardando revisão final
**Sub-projeto:** Treinamento (EAD) — Pipeline de Vídeo
**Specs relacionados (futuros):**
- Spec 2 — Tracking de Progresso (retomada de vídeo)
- Spec 3 — Quiz / Avaliação ao final do módulo
- Spec 4 — Certificado PDF na conclusão

---

## 1. Contexto e Objetivo

### Estado atual

O módulo `Treinamento` já existe parcialmente no projeto:

- Tabelas: `treinamentos`, `modulos`, `inscricoes`, `frequencias`
- Models: `Treinamento`, `Modulo`, `Inscricao`, `Frequencia`
- Controllers: `TreinamentoIndexController`, `ShowController`, `StoreController`, `ExportController`
- Vue pages: `TreinamentoIndex.vue`, `TreinamentoShow.vue` (com TODO de listagem de módulos)
- Tipos: `PRESENCIAL | EAD | HIBRIDO`
- Frequência hoje é presencial (data de aula + PRESENTE/AUSENTE/JUSTIFICADA)

O suporte real a EAD com vídeo não existe ainda — é o que este spec endereça.

### Objetivo

Implementar o **pipeline completo de vídeo** para módulos de treinamento, do upload até a reprodução pelo aluno:

1. Admin ou instrutor designado faz upload de um arquivo MP4
2. Sistema transcodifica para HLS multi-qualidade (480p/720p/1080p) em worker dedicado
3. Aluno inscrito e aprovado assiste com Video.js, com qualidade adaptativa

Tracking de progresso, quizzes e certificados ficam **fora deste spec** (Specs 2-4).

### Decisões de produto já tomadas

| Tema | Decisão |
|------|---------|
| Deployment | Azure (App Service + Azure Blob, igual à prod atual `sdcdefesa`) |
| Entrega de vídeo | HLS multi-qualidade com transcoding próprio (FFmpeg) |
| Modelo de progresso | "Tempo assistido com retomada" *(Spec 2, não aqui)* |
| Estrutura | 1 módulo = 1 vídeo + materiais (PDFs/anexos via spatie/medialibrary) |
| Permissão de upload | Admin global + instrutor designado do treinamento |
| Estratégia de upload | Híbrida: SAS direto Browser→Azure com fallback para PHP multipart |

---

## 2. Arquitetura

### Visão geral

```
┌───────────────────────────────────────────────────────────────────┐
│  FRONTEND (Vue 3 + Inertia)                                        │
│                                                                    │
│  ModuloEdit.vue (admin/instrutor)                                  │
│    └── <VideoUploader>                                             │
│          ├── useVideoUpload composable                             │
│          │     ├── tryDirectAzureUpload()  ← caminho A (SAS)       │
│          │     └── fallbackPhpUpload()     ← caminho B (multipart) │
│          └── progress bar + polling de status de processamento     │
│                                                                    │
│  ModuloPlayer.vue (aluno inscrito)                                 │
│    └── <VideoPlayer>                                               │
│          ├── Video.js + @videojs/http-streaming (HLS)              │
│          └── useVideoPlayer composable (refresh SAS em 403)        │
└───────────────────────────────────────────────────────────────────┘
                              │
                              ▼  HTTP / JSON
┌───────────────────────────────────────────────────────────────────┐
│  BACKEND (Laravel 12 + Octane/FrankenPHP)                          │
│                                                                    │
│  Modules/Treinamento/Controllers/Video/                            │
│    ├── UploadInitController                                        │
│    ├── UploadFinalizeController                                    │
│    ├── UploadDirectController (fallback)                           │
│    ├── VideoStatusController                                       │
│    ├── VideoUrlController                                          │
│    ├── ReprocessarVideoController                                  │
│    └── DeletarVideoController                                      │
│                                                                    │
│  Modules/Treinamento/Services/                                     │
│    ├── AzureSasService  (implements SasGeneratorInterface)         │
│    ├── VideoTranscodingService                                     │
│    └── VideoStorageService                                         │
│                                                                    │
│  Modules/Treinamento/Jobs/                                         │
│    ├── ProcessarVideoModulo                                        │
│    └── LimparUploadsOrfaosJob (scheduled)                          │
└───────────────────────────────────────────────────────────────────┘
                              │
                              ▼  Redis queue 'videos'
┌───────────────────────────────────────────────────────────────────┐
│  WORKER CONTAINER (dedicado)                                       │
│                                                                    │
│  Image: mesma do app + apt-get install ffmpeg                      │
│  Command: php artisan queue:work redis --queue=videos              │
│           --tries=3 --timeout=3600 --memory=2048                   │
│                                                                    │
│  Fluxo do job ProcessarVideoModulo:                                │
│    1. Stream-download do MP4 original do Azure → /tmp              │
│    2. ffprobe extrai duração e valida codec/formato                │
│    3. ffmpeg gera 3 qualidades (480p/720p/1080p)                   │
│    4. ffmpeg empacota HLS (segmentos .ts + manifests)              │
│    5. Stream-upload para Azure (prefix modulos/{id}/hls/)          │
│    6. Apaga /tmp e o MP4 original (se KEEP_ORIGINAL=false)         │
│    7. Update modulo: status=PRONTO + duracao + hls_manifest_path   │
└───────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────────────┐
│  STORAGE (Azure Blob — container 'sdc-treinamentos', NOVO)         │
│                                                                    │
│  modulos/{id}/original/{uuid}.mp4         (temp; apaga após HLS)   │
│  modulos/{id}/hls/manifest.m3u8           (master playlist)        │
│  modulos/{id}/hls/1080p/playlist.m3u8 + {n}.ts                     │
│  modulos/{id}/hls/720p/playlist.m3u8  + {n}.ts                     │
│  modulos/{id}/hls/480p/playlist.m3u8  + {n}.ts                     │
│  modulos/{id}/materiais/...               (via spatie/medialibrary)│
└───────────────────────────────────────────────────────────────────┘
```

### Decisões-chave de design

- **Container Azure novo `sdc-treinamentos`** (privado) — isola de `sdc-pae`, `sdc-compdec`, `sdc-exports`. Configurado em `config/filesystems.php` como disk `'treinamentos'`.
- **Worker dedicado** — mesma imagem do app + FFmpeg, comando de entrypoint diferente. Isola CPU pesada do FrankenPHP/Octane que serve requests.
- **Sem servir vídeo pelo PHP** — task.md original é explícito sobre isso. Backend só gera SAS URLs; bytes do vídeo sempre vão Azure ↔ navegador direto.
- **Materiais via spatie/laravel-medialibrary** — pacote já instalado (`composer.json`). Collection `'materiais'` no model `Modulo` cobre PDF/imagens/links sem reinventar upload.
- **Cleanup do MP4 original** configurável via `KEEP_ORIGINAL`. Default: apaga após HLS pronto, economiza ~30% storage.
- **Resiliência no código** — todos os pontos de falha (Azure indisponível, codec inválido, worker crash) têm fallback ou retry em código, não dependendo de orquestrador.
- **Refactor de `TreinamentoShow.vue`** — a página atual tem TODO de listagem de módulos; este spec implica refatorar para embutir o componente `<ModuloPlayer>` por módulo (visão do aluno) e `<VideoUploader>` (visão admin/instrutor) condicionalmente.

---

## 3. Data Model

### Migration: alterações em `modulos` (CONSOLIDADA na principal)

Por convenção do projeto (regra de ouro 9: consolidar migrations na principal), as alterações vão na migration existente `2025_12_28_140100_create_modulos_table.php`, não em uma nova migration.

```php
Schema::create('modulos', function (Blueprint $table) {
    $table->id();

    $table->foreignId('treinamento_id')
        ->constrained('treinamentos')
        ->cascadeOnDelete()
        ->cascadeOnUpdate();

    $table->string('titulo', 255);
    $table->text('descricao')->nullable();
    $table->integer('ordem')->default(0);
    $table->integer('carga_horaria');
    $table->date('data_prevista')->nullable();

    // --- NOVO: campos de vídeo ---
    $table->enum('video_status', [
        'SEM_VIDEO',
        'AGUARDANDO_UPLOAD',
        'PROCESSANDO',
        'PRONTO',
        'FALHOU',
    ])->default('SEM_VIDEO')->index();

    $table->string('video_disk', 50)->nullable();
    $table->string('video_original_path', 500)->nullable();
    $table->string('hls_manifest_path', 500)->nullable();
    $table->unsignedInteger('video_duracao_segundos')->nullable();
    $table->unsignedBigInteger('video_tamanho_bytes')->nullable();
    $table->timestamp('video_uploaded_at')->nullable();
    $table->timestamp('video_processed_at')->nullable();
    $table->text('video_error')->nullable();
    $table->unsignedTinyInteger('video_attempts')->default(0);

    $table->timestamps();
    $table->index(['treinamento_id', 'ordem']);
    $table->index('data_prevista');
});
```

### Migration: alteração em `treinamentos`

Consolidar na migration `2025_12_28_140000_create_treinamentos_table.php`:

```php
// Adicionar logo após a coluna 'instrutor' existente:
$table->foreignId('instrutor_id')
    ->nullable()
    ->after('instrutor')
    ->constrained('users')
    ->nullOnDelete();
```

A coluna `instrutor` (string) é mantida para casos de instrutor externo sem cadastro no sistema. O `instrutor_id` é o que dá poder de gerenciar vídeos.

### Enums (novos arquivos no módulo)

```php
// app/Modules/Treinamento/Enums/VideoStatus.php
enum VideoStatus: string
{
    case SEM_VIDEO = 'SEM_VIDEO';
    case AGUARDANDO_UPLOAD = 'AGUARDANDO_UPLOAD';
    case PROCESSANDO = 'PROCESSANDO';
    case PRONTO = 'PRONTO';
    case FALHOU = 'FALHOU';

    public function podeReprocessar(): bool
    {
        return $this === self::FALHOU;
    }

    public function estaPronto(): bool
    {
        return $this === self::PRONTO;
    }
}
```

### Permissões (Spatie)

Adicionar no seeder de permissões existente:

| Slug | Descrição | Atribuição padrão |
|------|-----------|---------------------|
| `treinamentos.videos.gerenciar` | Upload, reprocessar, deletar vídeos globalmente | super-admin, admin |
| `treinamentos.videos.assistir` | Assistir vídeos de treinamentos onde está inscrito | aluno (default) |

**Ownership check** (NÃO vira permissão Spatie — fica no model):

```php
// app/Modules/Treinamento/Models/Modulo.php
public function podeSerGerenciadoPor(User $user): bool
{
    if ($user->can('treinamentos.videos.gerenciar')) {
        return true;
    }
    return $this->treinamento->instrutor_id === $user->id;
}
```

### Spatie MediaLibrary — collection 'materiais' no Modulo

```php
// app/Modules/Treinamento/Models/Modulo.php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Modulo extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('materiais')
            ->useDisk('treinamentos')
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ]);
    }
}
```

---

## 4. Configuração

### Nova config: `config/treinamento.php`

```php
<?php

return [
    'video' => [
        'disk' => env('TREINAMENTO_VIDEO_DISK', 'treinamentos'),
        'container' => env('TREINAMENTO_VIDEO_CONTAINER', 'sdc-treinamentos'),

        'max_size_mb' => (int) env('TREINAMENTO_VIDEO_MAX_MB', 500),
        'extensoes_aceitas' => ['mp4', 'mov', 'mkv'],

        'keep_original' => env('TREINAMENTO_KEEP_ORIGINAL', false),

        'sas' => [
            'write_ttl_minutes' => (int) env('TREINAMENTO_SAS_WRITE_TTL', 30),
            'read_ttl_minutes'  => (int) env('TREINAMENTO_SAS_READ_TTL', 120),
        ],

        'transcoding' => [
            'queue' => env('TREINAMENTO_QUEUE', 'videos'),
            'qualities' => [
                '480p'  => ['width' => 854,  'height' => 480,  'bitrate' => '800k'],
                '720p'  => ['width' => 1280, 'height' => 720,  'bitrate' => '2500k'],
                '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => '5000k'],
            ],
            'segment_duration' => 6,
            'audio_bitrate' => '128k',
        ],

        'orphan_cleanup' => [
            'aguardando_upload_after_minutes' => 60,
            'original_after_days' => 7,
        ],
    ],
];
```

### `config/filesystems.php` — disk `'treinamentos'`

Adicionar no array de disks, seguindo o padrão Azure existente:

```php
'treinamentos' => $azureCs
    ? $azure(env('AZURE_STORAGE_CONTAINER_TREINAMENTOS', 'sdc-treinamentos'))
    : $local('treinamentos'),
```

### `config/queue.php`

Garantir que `videos` está como connection válida (já é redis por default — só usar `queue:work --queue=videos`).

### Variáveis `.env`

```
TREINAMENTO_VIDEO_DISK=treinamentos
TREINAMENTO_VIDEO_CONTAINER=sdc-treinamentos
TREINAMENTO_VIDEO_MAX_MB=500
TREINAMENTO_KEEP_ORIGINAL=false
TREINAMENTO_QUEUE=videos
TREINAMENTO_SAS_WRITE_TTL=30
TREINAMENTO_SAS_READ_TTL=120
AZURE_STORAGE_CONTAINER_TREINAMENTOS=sdc-treinamentos
```

---

## 5. API

### Endpoints (todos em `routes/api.php`, namespace Treinamento)

| Método | Rota | Controller | Quem |
|--------|------|------------|------|
| POST | `/api/v1/treinamentos/{t}/modulos/{m}/video/upload-init` | `UploadInitController` | admin/instrutor |
| POST | `/api/v1/treinamentos/{t}/modulos/{m}/video/upload-finalize` | `UploadFinalizeController` | admin/instrutor |
| POST | `/api/v1/treinamentos/{t}/modulos/{m}/video/upload-direct` | `UploadDirectController` | admin/instrutor |
| GET  | `/api/v1/treinamentos/{t}/modulos/{m}/video/status` | `VideoStatusController` | admin/instrutor |
| GET  | `/api/v1/treinamentos/{t}/modulos/{m}/video/url` | `VideoUrlController` | inscrito aprovado |
| POST | `/api/v1/treinamentos/{t}/modulos/{m}/video/reprocessar` | `ReprocessarVideoController` | admin/instrutor |
| DELETE | `/api/v1/treinamentos/{t}/modulos/{m}/video` | `DeletarVideoController` | admin/instrutor |

### Contratos detalhados

#### `POST upload-init`

```http
Request:
{
  "filename": "aula-1.mp4",
  "size_bytes": 245682341,
  "content_type": "video/mp4"
}

Response 200 (caminho SAS):
{
  "mode": "azure_direct",
  "upload_url": "https://sdcdefesa.blob.core.windows.net/sdc-treinamentos/modulos/42/original/abc-def.mp4?sv=...&sig=...",
  "blob_path": "modulos/42/original/abc-def.mp4",
  "expires_at": "2026-05-28T18:30:00Z",
  "max_size_bytes": 524288000
}

Response 200 (fallback acionado por backend):
{
  "mode": "php_fallback",
  "direct_upload_url": "/api/v1/treinamentos/42/modulos/100/video/upload-direct",
  "max_size_bytes": 524288000
}

Response 422 (validação):
{ "message": "Extensão inválida", "errors": { "filename": [...] } }

Response 503 (Azure indisponível, mas pode fazer fallback):
Frontend deve tentar mode=php_fallback automaticamente.
```

#### `POST upload-finalize`

```http
Request:
{ "blob_path": "modulos/42/original/abc-def.mp4" }

Response 200:
{
  "status": "PROCESSANDO",
  "modulo_id": 42,
  "video_uploaded_at": "2026-05-28T17:45:00Z"
}

Response 422:
{ "message": "Blob não encontrado no Azure" }
```

#### `POST upload-direct` (fallback)

Multipart/form-data com campo `video`. Backend recebe via stream, valida tamanho/extensão, sobe para Azure, despacha job. Mesmo response do upload-finalize.

#### `GET video/status`

```http
Response 200:
{
  "status": "PROCESSANDO",
  "duracao_segundos": null,
  "uploaded_at": "2026-05-28T17:45:00Z",
  "processed_at": null,
  "erro": null
}
```

#### `GET video/url`

```http
Response 200:
{
  "manifest_url": "https://sdcdefesa.blob.core.windows.net/sdc-treinamentos/modulos/42/hls/manifest.m3u8?...sas...",
  "expires_at": "2026-05-28T20:00:00Z",
  "duracao_segundos": 543
}

Response 403:
{ "message": "Você precisa estar inscrito e aprovado neste treinamento" }

Response 404 / 409:
{ "message": "Vídeo ainda processando ou indisponível", "status": "PROCESSANDO" }
```

---

## 6. Fluxos de Erro e Máquina de Estados

### Máquina de estados de `video_status`

```
SEM_VIDEO ─upload-init──▶ AGUARDANDO_UPLOAD ─upload-finalize──▶ PROCESSANDO
   ▲                              │                                  │
   │                              │ timeout TTL SAS (30min) sem      │
   │                              │ finalize → cleanup orfão         │
   │                              ▼                                   │
   │                          SEM_VIDEO                               │
   │                                                                  │
   │                              ┌──── job OK ──────▶ PRONTO ────────┤
   │ DELETE                       │                                   │
   │◀─────────────────────────────┤                                   │
   │                              └──── job falhou ──▶ FALHOU         │
   │                                                       │          │
   │                                                       └─ reprocessar
   └─────────────────────────────────── DELETE (de qualquer estado)
```

### Cenários de erro

| Cenário | Tratamento |
|---------|------------|
| Upload SAS falha (CORS, rede, Azure 5xx) | Frontend captura erro → chama `upload-direct` automaticamente com o mesmo arquivo |
| Ambos os caminhos falham | UI mostra "Tente novamente" + log estruturado em `Log::channel('treinamento')` |
| Job FFmpeg falha (codec, corrupção, disco cheio) | Laravel retry `tries=3`, `backoff=[60,300,900]`s. Após esgotar → `status=FALHOU`, `video_error` recebe stderr resumido (max 1000 chars) |
| Worker container cai durante processamento | `--timeout=3600` libera job; job é idempotente (deleta HLS parcial antes de regravar); unique job key `modulo:{id}:video` previne dupla execução |
| Aluno perde acesso (SAS expira em pleno stream) | Player Video.js detecta 403 → `useVideoPlayer` composable chama `video/url` de novo, atualiza source |
| Azure inteiro indisponível | `upload-init` devolve `php_fallback` automaticamente; player mostra "Conteúdo indisponível, tente em instantes" (sem fallback de read possível) |
| Uploads órfãos (AGUARDANDO_UPLOAD sem finalize) | Job agendado diário `LimparUploadsOrfaosJob` (3h) marca SEM_VIDEO + apaga blob temp se existir |
| MP4 originais antigos | Mesmo job cleanup apaga MP4s >7 dias quando `KEEP_ORIGINAL=false` |
| `DELETE` chamado enquanto status=PROCESSANDO | Marca `video_status=SEM_VIDEO` e dispara `flush()` de signal para o job (verifica antes de gravar HLS); HLS parcial é apagado pelo próprio job ao detectar cancelamento |

### Audit log

Toda ação (upload-finalize, reprocessar, deletar) gera registro em `audit_logs` (tabela já existente no projeto) com `user_id`, `acao`, `recurso_tipo='modulo_video'`, `recurso_id=modulo.id`, `meta` com tamanho/status anterior. Isso protege contra deleção indevida de conteúdo educacional.

---

## 7. Princípios SOLID/DRY

- **SRP** — cada controller faz uma coisa só (init, finalize, status, url). Sem mega-controller.
- **DIP** — `AzureSasService` implementa `SasGeneratorInterface`; testes podem injetar fake. Mesmo padrão para `VideoTranscodingService`.
- **OCP** — `VideoTranscodingService` recebe `TranscodingProfile` derivado de `config/treinamento.php`. Adicionar 1440p ou ajustar bitrate não muda código.
- **DRY** — composables `useVideoUpload` (frontend admin) e `useVideoPlayer` (frontend aluno) encapsulam toda lógica de SAS/refresh; Vue pages só consomem.
- **Resiliência no código** — fallback PHP, retries, refresh de SAS, cleanup de órfãos são todos código no app; não dependem de orquestrador, k8s, ou WAF.

---

## 8. Estratégia de Testes

Conforme convenção do projeto (memória `feedback_commit_granularity`): testes **rodam localmente para validar, mas não são commitados**.

### Feature tests (locais)
- `UploadInitTest` — permissão admin/instrutor, validação de tamanho/extensão, devolve SAS válida, fallback quando Azure mock retorna 5xx
- `UploadFinalizeTest` — valida blob existe antes de despachar job; idempotente em re-chamada
- `VideoUrlTest` — só inscritos APROVADOS recebem SAS; 403 caso contrário; TTL correto
- `ReprocessarTest` — só admin/instrutor; só permitido em estado FALHOU
- `DeletarVideoTest` — limpa HLS + original no Azure; status volta a SEM_VIDEO

### Unit tests (locais)
- `AzureSasServiceTest` — geração de SAS com TTL e permissões corretas (mock cliente Azure)
- `VideoTranscodingServiceTest` — chamadas FFmpeg corretas, paths corretos (mock `Symfony\Component\Process\Process`)
- `ProcessarVideoModuloJobTest` — fluxo feliz, falha por codec inválido, idempotência ao reiniciar mid-flight

### E2E manual (smoke)
- Upload .mp4 pequeno (~5MB) → conferir HLS gerado e player tocar com qualidade adaptativa
- Upload .mp4 grande (~200MB) → conferir worker dedicado processa sem afetar latência do app principal
- Simular queda Azure (toggle env temporário) → conferir fallback PHP funcionando

---

## 9. Observabilidade

- **Logs estruturados** em `Log::channel('treinamento')` (novo) com contexto: `modulo_id`, `stage`, `duracao_ms`, `tamanho_bytes`.
- **RequestTrace** (já existe) capta latência dos endpoints de upload/url.
- **Métricas custom**:
  - Contador `video_processed_total{result=success|failure}`
  - Histograma `video_processing_duration_seconds{quality}`
  - Gauge `video_storage_bytes` (somatório por treinamento)
- **FFmpeg logs** salvos em `storage/logs/ffmpeg/{modulo_id}-{timestamp}.log` para debugging de falhas.

---

## 10. Plano de Deploy

### Pré-requisitos de infra (Azure)
1. Criar container `sdc-treinamentos` no Storage Account existente
2. CORS no Storage Account: permitir `PUT` do domínio do frontend (para SAS direct)
3. Criar segundo App Service Plan (ou container slot) para worker `worker-videos`
4. Configurar variáveis `.env` no App Service (vars listadas em §4)

### Pré-requisitos de imagem Docker
1. Atualizar `SDC/docker/Dockerfile` (ou criar `Dockerfile.worker`) — adicionar `apt-get install ffmpeg`
2. ENTRYPOINT do worker: `php artisan queue:work redis --queue=videos --tries=3 --timeout=3600 --memory=2048`

### Ordem de subida
1. Migration consolidada (alterações em `modulos` e `treinamentos`)
2. Seeder de permissões novas
3. Deploy do app principal (Octane) com novos controllers/rotas
4. Deploy do worker container
5. Smoke test E2E em staging
6. Promote para produção

---

## 11. Out of Scope (explícito)

- **Tracking de progresso** (posição/percentual assistido) → Spec 2
- **Quiz / avaliação ao final do módulo** → Spec 3
- **Certificado PDF na conclusão completa** → Spec 4
- **DRM / encriptação de conteúdo**
- **Subtítulos / legendas**
- **Watermark personalizado por aluno**
- **Download offline**
- **Live streaming**
- **Migração de dados de treinamentos legados**
- **Interface admin para gerenciamento em massa de vídeos**
- **Notificações ao aluno quando vídeo fica pronto** (pode entrar em iteração de UX depois)

---

## 12. Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| Custo de Azure Blob escala com uso | Média | Médio | Default `KEEP_ORIGINAL=false`; monitorar `video_storage_bytes`; futuro: lifecycle policy archive após N dias sem acesso |
| Worker FFmpeg trava em vídeo malformado | Média | Médio | `--timeout=3600` + `tries=3`; FALHOU exposto no admin com mensagem |
| CORS do Storage Account bloqueia SAS direct | Alta na primeira deploy | Baixo (fallback) | Fallback PHP é transparente para o usuário; corrige config Azure depois |
| Vídeo HLS gigante (1h+) explode tempo de transcoding | Baixa | Alto | `timeout=3600` cobre até ~3h de transcoding em CPU média; vídeos maiores precisam de worker maior |
| Concorrência (múltiplos uploads do mesmo módulo) | Baixa | Médio | Unique job key `modulo:{id}:video` + estado de máquina previne dupla execução |
| Browser cliente sem suporte HLS | Baixa | Médio | `@videojs/http-streaming` cobre Chrome/Firefox/Edge; Safari tem HLS nativo |
