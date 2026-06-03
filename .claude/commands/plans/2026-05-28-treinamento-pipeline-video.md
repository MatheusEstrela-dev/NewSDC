# Pipeline de Vídeo do Módulo Treinamento — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar upload + transcoding HLS + player Video.js para vídeos de módulos de treinamento, com fallback PHP quando SAS Azure direto falha.

**Architecture:** Híbrido — frontend tenta upload direto via SAS para Azure Blob; em caso de falha, cai para multipart via Laravel. Worker dedicado (supervisor program separado, mesma imagem) consome queue `videos` e roda FFmpeg gerando HLS multi-qualidade (480p/720p/1080p). Aluno assiste com Video.js + hls.js, SAS read renovada sob 403.

**Tech Stack:** Laravel 12 + Octane/FrankenPHP, PostgreSQL, Redis queues, Azure Blob Storage, Vue 3 + Inertia, Video.js + @videojs/http-streaming, FFmpeg em worker supervisor.

**Reference spec:** [docs/superpowers/specs/2026-05-28-treinamento-pipeline-video-design.md](../specs/2026-05-28-treinamento-pipeline-video-design.md)

---

## Convenções deste plano (importantes)

Per memória de feedback do usuário:

- **Commits agrupados por FASE**, não por task. Cada fase é 1 commit.
- **Sem `Co-Authored-By` trailer** nos commits.
- **Testes apenas locais (validar, não commitar)**. Os blocos de teste deste plano servem como validação manual; NÃO faça `git add` deles.
- **Migrations consolidadas** na principal (regra de ouro 9) — não criar nova migration `add_video_to_modulos`.
- **Sem emojis em código**.

Subagent executor: cada fase agrupa todas as suas tasks num único subagente; commit no final da fase, não no meio.

---

## File Structure (visão geral)

### Backend (Laravel)

| Arquivo | Ação | Responsabilidade |
|---------|------|------------------|
| `SDC/database/migrations/2025_12_28_140100_create_modulos_table.php` | MODIFY | Adicionar campos `video_*` |
| `SDC/database/migrations/2025_12_28_140000_create_treinamentos_table.php` | MODIFY | Adicionar `instrutor_id` FK |
| `SDC/config/treinamento.php` | CREATE | Config nova do módulo (vídeo, SAS, qualities) |
| `SDC/config/filesystems.php` | MODIFY | Adicionar disk `'treinamentos'` |
| `SDC/config/logging.php` | MODIFY | Adicionar channel `'treinamento'` |
| `SDC/app/Modules/Treinamento/Enums/VideoStatus.php` | CREATE | Enum de estado do vídeo |
| `SDC/app/Modules/Treinamento/Models/Modulo.php` | MODIFY | HasMedia, casts, ownership |
| `SDC/app/Modules/Treinamento/Models/Treinamento.php` | MODIFY | Relação `instrutor()` |
| `SDC/app/Modules/Treinamento/Contracts/SasGeneratorInterface.php` | CREATE | Contrato de geração de SAS |
| `SDC/app/Modules/Treinamento/Contracts/VideoTranscoderInterface.php` | CREATE | Contrato de transcoding |
| `SDC/app/Modules/Treinamento/Services/AzureSasService.php` | CREATE | Implementação SAS via Azure SDK |
| `SDC/app/Modules/Treinamento/Services/VideoStorageService.php` | CREATE | Paths, container, cleanup |
| `SDC/app/Modules/Treinamento/Services/VideoTranscodingService.php` | CREATE | Orquestra FFmpeg |
| `SDC/app/Modules/Treinamento/Jobs/ProcessarVideoModulo.php` | CREATE | Job principal de transcoding |
| `SDC/app/Modules/Treinamento/Jobs/LimparUploadsOrfaosJob.php` | CREATE | Cleanup agendado |
| `SDC/app/Modules/Treinamento/Controllers/Video/UploadInitController.php` | CREATE | POST upload-init |
| `SDC/app/Modules/Treinamento/Controllers/Video/UploadFinalizeController.php` | CREATE | POST upload-finalize |
| `SDC/app/Modules/Treinamento/Controllers/Video/UploadDirectController.php` | CREATE | POST upload-direct (fallback) |
| `SDC/app/Modules/Treinamento/Controllers/Video/VideoStatusController.php` | CREATE | GET status |
| `SDC/app/Modules/Treinamento/Controllers/Video/VideoUrlController.php` | CREATE | GET url (SAS read) |
| `SDC/app/Modules/Treinamento/Controllers/Video/ReprocessarVideoController.php` | CREATE | POST reprocessar |
| `SDC/app/Modules/Treinamento/Controllers/Video/DeletarVideoController.php` | CREATE | DELETE video |
| `SDC/app/Modules/Treinamento/Requests/UploadInitRequest.php` | CREATE | Form request com validação |
| `SDC/app/Modules/Treinamento/Requests/UploadFinalizeRequest.php` | CREATE | |
| `SDC/app/Modules/Treinamento/Requests/UploadDirectRequest.php` | CREATE | |
| `SDC/app/Modules/Treinamento/TreinamentoServiceProvider.php` | MODIFY | Bind interfaces + schedule cleanup |
| `SDC/database/seeders/TreinamentoPermissionsSeeder.php` | CREATE | Permissões `treinamento.videos.*` |
| `SDC/database/seeders/DatabaseSeeder.php` | MODIFY | Chamar seeder novo |
| `SDC/routes/modules/treinamento.php` | MODIFY | Adicionar grupo de rotas vídeo |

### Frontend (Vue/Inertia)

| Arquivo | Ação | Responsabilidade |
|---------|------|------------------|
| `SDC/package.json` | MODIFY | Adicionar `video.js`, `@videojs/http-streaming` |
| `SDC/resources/js/composables/useVideoUpload.js` | CREATE | Lógica de upload SAS + fallback |
| `SDC/resources/js/composables/useVideoPlayer.js` | CREATE | Lógica de player + refresh SAS |
| `SDC/resources/js/Components/Treinamento/VideoUploader.vue` | CREATE | Componente de upload + progress |
| `SDC/resources/js/Components/Treinamento/VideoPlayer.vue` | CREATE | Wrapper Video.js + HLS |
| `SDC/resources/js/Components/Treinamento/ModuloPlayer.vue` | CREATE | Card de módulo com player |
| `SDC/resources/js/Pages/Treinamento/TreinamentoShow.vue` | MODIFY | Embutir lista de módulos + ModuloPlayer |
| `SDC/resources/js/Pages/Treinamento/ModuloEdit.vue` | CREATE | Tela admin/instrutor para upload |

### Docker / Infra

| Arquivo | Ação | Responsabilidade |
|---------|------|------------------|
| `SDC/docker/Dockerfile.queue` | MODIFY | Instalar FFmpeg na imagem do worker |
| `SDC/docker/supervisor/laravel-worker.conf` | MODIFY | Adicionar program `videos-worker` |

---

## Fases (8 commits ao todo)

1. **Fase 1 — Foundation**: migrations, config, enums, model fields
2. **Fase 2 — Backend Services**: SAS, Storage, Transcoding com interfaces
3. **Fase 3 — Jobs**: ProcessarVideoModulo + LimparUploadsOrfaosJob
4. **Fase 4 — Backend API**: controllers, form requests, rotas, permissões
5. **Fase 5 — Frontend Foundation**: install Video.js, composables
6. **Fase 6 — Frontend Components**: VideoUploader, VideoPlayer, ModuloPlayer
7. **Fase 7 — Frontend Integration**: refactor TreinamentoShow + nova ModuloEdit
8. **Fase 8 — Worker Container**: Dockerfile.queue + supervisor

---

# FASE 1 — Foundation

**Goal:** Schema, config e enum prontos. Sem código de negócio ainda.

### Task 1.1: Atualizar migration `create_modulos_table` com campos de vídeo

**Files:**
- Modify: `SDC/database/migrations/2025_12_28_140100_create_modulos_table.php`

- [ ] **Step 1: Substituir o conteúdo da migration pelo block abaixo**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('treinamento_id')
                ->constrained('treinamentos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('titulo', 255);
            $table->text('descricao')->nullable();

            $table->integer('ordem')->default(0)->comment('Ordem de exibicao');
            $table->integer('carga_horaria')->comment('Carga horaria do modulo em horas');

            $table->date('data_prevista')->nullable();

            // Campos de video (Spec 1 Pipeline de Video)
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
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
```

### Task 1.2: Atualizar migration `create_treinamentos_table` com instrutor_id

**Files:**
- Modify: `SDC/database/migrations/2025_12_28_140000_create_treinamentos_table.php`

- [ ] **Step 1: Adicionar `instrutor_id` logo após a coluna `instrutor` existente**

Localize o bloco `// Instrutor e Local` e substitua por:

```php
            // Instrutor e Local
            $table->string('instrutor', 255)->nullable();
            $table->foreignId('instrutor_id')
                ->nullable()
                ->after('instrutor')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('local', 255)->nullable()->comment('Local fisico ou link para EAD');
```

### Task 1.3: Criar enum `VideoStatus`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Enums/VideoStatus.php`

- [ ] **Step 1: Escrever o enum**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

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

    public function podeReceberUpload(): bool
    {
        return in_array($this, [self::SEM_VIDEO, self::FALHOU], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SEM_VIDEO => 'Sem video',
            self::AGUARDANDO_UPLOAD => 'Aguardando upload',
            self::PROCESSANDO => 'Processando',
            self::PRONTO => 'Pronto',
            self::FALHOU => 'Falhou',
        };
    }
}
```

### Task 1.4: Criar config `treinamento.php`

**Files:**
- Create: `SDC/config/treinamento.php`

- [ ] **Step 1: Escrever a config**

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
            'read_ttl_minutes' => (int) env('TREINAMENTO_SAS_READ_TTL', 120),
        ],

        'transcoding' => [
            'queue' => env('TREINAMENTO_QUEUE', 'videos'),
            'qualities' => [
                '480p' => ['width' => 854, 'height' => 480, 'bitrate' => '800k'],
                '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => '2500k'],
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

### Task 1.5: Adicionar disk `'treinamentos'` em filesystems.php

**Files:**
- Modify: `SDC/config/filesystems.php`

- [ ] **Step 1: Adicionar dentro do array retornado pela closure `(function () { ... })()`, ao lado de `'exports'`**

Localize o bloco:

```php
            'exports' => $azureCs
                ? $azure(env('AZURE_STORAGE_CONTAINER_EXPORTS', 'sdc-exports'))
                : $local('exports'),
        ];
```

E substitua por:

```php
            'exports' => $azureCs
                ? $azure(env('AZURE_STORAGE_CONTAINER_EXPORTS', 'sdc-exports'))
                : $local('exports'),

            'treinamentos' => $azureCs
                ? $azure(env('AZURE_STORAGE_CONTAINER_TREINAMENTOS', 'sdc-treinamentos'))
                : $local('treinamentos'),
        ];
```

### Task 1.6: Adicionar channel `'treinamento'` em logging.php

**Files:**
- Modify: `SDC/config/logging.php`

- [ ] **Step 1: Adicionar dentro do array `channels`**

Adicione a entrada:

```php
        'treinamento' => [
            'driver' => 'daily',
            'path' => storage_path('logs/treinamento.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],
```

### Task 1.7: Atualizar model `Modulo` (video casts + HasMedia + ownership)

**Files:**
- Modify: `SDC/app/Modules/Treinamento/Models/Modulo.php`

- [ ] **Step 1: Substituir conteúdo completo**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Treinamento\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Modulo extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'modulos';

    protected $fillable = [
        'treinamento_id',
        'titulo',
        'descricao',
        'ordem',
        'carga_horaria',
        'data_prevista',
        'video_status',
        'video_disk',
        'video_original_path',
        'hls_manifest_path',
        'video_duracao_segundos',
        'video_tamanho_bytes',
        'video_uploaded_at',
        'video_processed_at',
        'video_error',
        'video_attempts',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'carga_horaria' => 'integer',
        'data_prevista' => 'date',
        'video_status' => VideoStatus::class,
        'video_duracao_segundos' => 'integer',
        'video_tamanho_bytes' => 'integer',
        'video_uploaded_at' => 'datetime',
        'video_processed_at' => 'datetime',
        'video_attempts' => 'integer',
    ];

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function frequencias(): HasMany
    {
        return $this->hasMany(Frequencia::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('materiais')
            ->useDisk(config('treinamento.video.disk'))
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }

    public function podeSerGerenciadoPor(User $user): bool
    {
        if ($user->can('treinamento.videos.gerenciar')) {
            return true;
        }
        return $this->treinamento->instrutor_id === $user->id;
    }

    public function scopePorTreinamento($query, int $treinamentoId)
    {
        return $query->where('treinamento_id', $treinamentoId);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('ordem');
    }
}
```

### Task 1.8: Atualizar model `Treinamento` (relação instrutor)

**Files:**
- Modify: `SDC/app/Modules/Treinamento/Models/Treinamento.php`

- [ ] **Step 1: Adicionar `instrutor_id` em `$fillable` e o método de relação**

Procure o array `$fillable` e adicione `'instrutor_id'` logo após `'instrutor'`:

```php
        'instrutor',
        'instrutor_id',
```

Procure a seção `// Relationships` e adicione, logo após o método `createdBy()`:

```php
    public function instrutorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instrutor_id');
    }
```

### Task 1.9: Validar localmente (NÃO commitar testes)

- [ ] **Step 1: Rodar fresh migration e verificar schema**

Comando:

```bash
cd SDC && php artisan migrate:fresh --seed
```

Expected: sem erros. Verificar com psql que a tabela `modulos` tem todas as colunas `video_*` e que `treinamentos` tem `instrutor_id`.

- [ ] **Step 2: Validar enum em Tinker**

```bash
cd SDC && php artisan tinker --execute="use App\Modules\Treinamento\Enums\VideoStatus; var_dump(VideoStatus::SEM_VIDEO->podeReceberUpload(), VideoStatus::FALHOU->podeReprocessar());"
```

Expected: `bool(true)` `bool(true)`.

### Task 1.10: Commit da Fase 1

- [ ] **Step 1: Commit**

```bash
git add SDC/database/migrations/2025_12_28_140100_create_modulos_table.php \
        SDC/database/migrations/2025_12_28_140000_create_treinamentos_table.php \
        SDC/app/Modules/Treinamento/Enums/VideoStatus.php \
        SDC/app/Modules/Treinamento/Models/Modulo.php \
        SDC/app/Modules/Treinamento/Models/Treinamento.php \
        SDC/config/treinamento.php \
        SDC/config/filesystems.php \
        SDC/config/logging.php

git commit -m "feat(treinamento): foundation do pipeline de video (schema, config, enum)"
```

---

# FASE 2 — Backend Services

**Goal:** Serviços com interfaces (DIP) prontos para SAS, storage e transcoding. Sem invocação ainda.

### Task 2.1: Criar `SasGeneratorInterface`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Contracts/SasGeneratorInterface.php`

- [ ] **Step 1: Escrever o contrato**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Contracts;

interface SasGeneratorInterface
{
    /**
     * Gera URL SAS com permissão de escrita para upload direto do browser.
     * @return array{url: string, expires_at: \DateTimeImmutable, blob_path: string}
     */
    public function generateWriteUrl(string $blobPath, int $ttlMinutes): array;

    /**
     * Gera URL SAS com permissão de leitura para o player.
     * @return array{url: string, expires_at: \DateTimeImmutable}
     */
    public function generateReadUrl(string $blobPath, int $ttlMinutes): array;

    /**
     * Checa se o backend Azure está respondendo (para fallback).
     */
    public function isAvailable(): bool;
}
```

### Task 2.2: Criar `VideoTranscoderInterface`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Contracts/VideoTranscoderInterface.php`

- [ ] **Step 1: Escrever o contrato**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Contracts;

interface VideoTranscoderInterface
{
    /**
     * Transcodifica um MP4 local para HLS multi-qualidade.
     * @param string $inputPath  caminho local do MP4
     * @param string $outputDir  diretorio local onde gravar HLS
     * @return array{duracao_segundos: int, manifest_path: string}
     */
    public function transcodeToHls(string $inputPath, string $outputDir): array;
}
```

### Task 2.3: Implementar `AzureSasService`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Services/AzureSasService.php`

- [ ] **Step 1: Escrever a implementação**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Contracts\SasGeneratorInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AzureSasService implements SasGeneratorInterface
{
    public function generateWriteUrl(string $blobPath, int $ttlMinutes): array
    {
        $disk = Storage::disk(config('treinamento.video.disk'));
        $expiresAt = new DateTimeImmutable("+{$ttlMinutes} minutes");

        $url = $disk->temporaryUploadUrl($blobPath, $expiresAt)['url'];

        return [
            'url' => $url,
            'expires_at' => $expiresAt,
            'blob_path' => $blobPath,
        ];
    }

    public function generateReadUrl(string $blobPath, int $ttlMinutes): array
    {
        $disk = Storage::disk(config('treinamento.video.disk'));
        $expiresAt = new DateTimeImmutable("+{$ttlMinutes} minutes");

        $url = $disk->temporaryUrl($blobPath, $expiresAt);

        return [
            'url' => $url,
            'expires_at' => $expiresAt,
        ];
    }

    public function isAvailable(): bool
    {
        try {
            $disk = Storage::disk(config('treinamento.video.disk'));
            $disk->exists('healthcheck.txt');
            return true;
        } catch (\Throwable $e) {
            Log::channel('treinamento')->warning('Azure SAS indisponivel', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
```

NOTA: `temporaryUploadUrl` é nativo do flysystem-azure-blob ≥3.x. Se a versão instalada não tiver, há que cair para `BlobSharedAccessSignatureHelper` do SDK. Adicione TODO se o método não existir e testes da Task 2.6 falharem.

### Task 2.4: Implementar `VideoStorageService`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Services/VideoStorageService.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoStorageService
{
    public function originalPathFor(int $moduloId, string $extension): string
    {
        $uuid = (string) Str::uuid();
        return "modulos/{$moduloId}/original/{$uuid}.{$extension}";
    }

    public function hlsPrefixFor(int $moduloId): string
    {
        return "modulos/{$moduloId}/hls";
    }

    public function manifestPathFor(int $moduloId): string
    {
        return "{$this->hlsPrefixFor($moduloId)}/manifest.m3u8";
    }

    public function deleteOriginal(string $blobPath): void
    {
        $disk = Storage::disk(config('treinamento.video.disk'));
        if ($disk->exists($blobPath)) {
            $disk->delete($blobPath);
        }
    }

    public function deleteHlsTree(int $moduloId): void
    {
        $disk = Storage::disk(config('treinamento.video.disk'));
        $disk->deleteDirectory($this->hlsPrefixFor($moduloId));
    }

    public function uploadLocalDirectory(string $localDir, int $moduloId): void
    {
        $disk = Storage::disk(config('treinamento.video.disk'));
        $prefix = $this->hlsPrefixFor($moduloId);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($localDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative = ltrim(str_replace($localDir, '', $file->getPathname()), '/\\');
                $blobPath = "{$prefix}/" . str_replace('\\', '/', $relative);
                $stream = fopen($file->getPathname(), 'r');
                $disk->writeStream($blobPath, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }

    public function downloadOriginalToLocal(string $blobPath, string $localPath): void
    {
        $disk = Storage::disk(config('treinamento.video.disk'));
        $stream = $disk->readStream($blobPath);
        if (!is_resource($stream)) {
            throw new \RuntimeException("Nao foi possivel baixar blob {$blobPath}");
        }
        $local = fopen($localPath, 'w');
        stream_copy_to_stream($stream, $local);
        fclose($local);
        fclose($stream);
    }
}
```

### Task 2.5: Implementar `VideoTranscodingService`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Services/VideoTranscodingService.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Contracts\VideoTranscoderInterface;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class VideoTranscodingService implements VideoTranscoderInterface
{
    public function transcodeToHls(string $inputPath, string $outputDir): array
    {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $duracao = $this->probeDuration($inputPath);
        $qualities = config('treinamento.video.transcoding.qualities');
        $segDur = config('treinamento.video.transcoding.segment_duration');
        $audioBr = config('treinamento.video.transcoding.audio_bitrate');

        foreach ($qualities as $key => $q) {
            $variantDir = "{$outputDir}/{$key}";
            if (!is_dir($variantDir)) {
                mkdir($variantDir, 0755, true);
            }
            $this->transcodeVariant($inputPath, $variantDir, $key, $q, $segDur, $audioBr);
        }

        $manifestPath = "{$outputDir}/manifest.m3u8";
        $this->writeMasterManifest($manifestPath, $qualities);

        return [
            'duracao_segundos' => $duracao,
            'manifest_path' => $manifestPath,
        ];
    }

    private function probeDuration(string $inputPath): int
    {
        $process = new Process([
            'ffprobe', '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $inputPath,
        ]);
        $process->setTimeout(60);
        $process->mustRun();
        return (int) round((float) trim($process->getOutput()));
    }

    private function transcodeVariant(
        string $inputPath,
        string $outDir,
        string $label,
        array $q,
        int $segDur,
        string $audioBr,
    ): void {
        $process = new Process([
            'ffmpeg', '-y',
            '-i', $inputPath,
            '-vf', "scale=w={$q['width']}:h={$q['height']}:force_original_aspect_ratio=decrease",
            '-c:v', 'libx264', '-profile:v', 'main', '-preset', 'veryfast',
            '-b:v', $q['bitrate'],
            '-c:a', 'aac', '-b:a', $audioBr,
            '-hls_time', (string) $segDur,
            '-hls_playlist_type', 'vod',
            '-hls_segment_filename', "{$outDir}/seg_%03d.ts",
            "{$outDir}/playlist.m3u8",
        ]);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            $err = substr($process->getErrorOutput(), -1000);
            Log::channel('treinamento')->error('FFmpeg falhou', [
                'variant' => $label,
                'stderr' => $err,
            ]);
            throw new \RuntimeException("FFmpeg falhou em {$label}: {$err}");
        }
    }

    private function writeMasterManifest(string $manifestPath, array $qualities): void
    {
        $lines = ['#EXTM3U', '#EXT-X-VERSION:3'];
        foreach ($qualities as $key => $q) {
            $bandwidth = (int) str_replace('k', '', $q['bitrate']) * 1000;
            $lines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$q['width']}x{$q['height']}";
            $lines[] = "{$key}/playlist.m3u8";
        }
        file_put_contents($manifestPath, implode("\n", $lines) . "\n");
    }
}
```

### Task 2.6: Bind interfaces no `TreinamentoServiceProvider`

**Files:**
- Modify: `SDC/app/Modules/Treinamento/TreinamentoServiceProvider.php`

- [ ] **Step 1: Substituir conteúdo**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento;

use App\Modules\Treinamento\Contracts\SasGeneratorInterface;
use App\Modules\Treinamento\Contracts\VideoTranscoderInterface;
use App\Modules\Treinamento\Services\AzureSasService;
use App\Modules\Treinamento\Services\VideoTranscodingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class TreinamentoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SasGeneratorInterface::class, AzureSasService::class);
        $this->app->bind(VideoTranscoderInterface::class, VideoTranscodingService::class);
    }

    public function boot(): void
    {
        // Rotas via routes/web.php -> routes/modules/treinamento.php
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->job(new \App\Modules\Treinamento\Jobs\LimparUploadsOrfaosJob())
                ->dailyAt('03:00')
                ->name('treinamento:limpar-uploads-orfaos')
                ->withoutOverlapping();
        });
    }
}
```

NOTA: O Provider original tinha referências quebradas a `Application/UseCases` e `Infrastructure/Persistence`. Este patch limpa tudo e foca no necessário.

### Task 2.7: Validar localmente

- [ ] **Step 1: Verificar binding**

```bash
cd SDC && php artisan tinker --execute="dd(app(App\\Modules\\Treinamento\\Contracts\\SasGeneratorInterface::class));"
```

Expected: instância de `AzureSasService`.

- [ ] **Step 2: Testar `VideoStorageService` paths**

```bash
cd SDC && php artisan tinker --execute="\$s = app(App\\Modules\\Treinamento\\Services\\VideoStorageService::class); dd(\$s->originalPathFor(42, 'mp4'), \$s->manifestPathFor(42));"
```

Expected: `'modulos/42/original/<uuid>.mp4'`, `'modulos/42/hls/manifest.m3u8'`.

### Task 2.8: Commit da Fase 2

- [ ] **Step 1: Commit**

```bash
git add SDC/app/Modules/Treinamento/Contracts/ \
        SDC/app/Modules/Treinamento/Services/AzureSasService.php \
        SDC/app/Modules/Treinamento/Services/VideoStorageService.php \
        SDC/app/Modules/Treinamento/Services/VideoTranscodingService.php \
        SDC/app/Modules/Treinamento/TreinamentoServiceProvider.php

git commit -m "feat(treinamento): services de SAS, storage e transcoding HLS"
```

---

# FASE 3 — Jobs (transcoding + cleanup)

**Goal:** Worker enfileira e processa vídeos. Cleanup agendado de órfãos.

### Task 3.1: Criar `ProcessarVideoModulo`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Jobs/ProcessarVideoModulo.php`

- [ ] **Step 1: Escrever o job**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Contracts\VideoTranscoderInterface;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Services\VideoStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarVideoModulo implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;
    public int $uniqueFor = 3600;

    public function __construct(public int $moduloId)
    {
        $this->onQueue(config('treinamento.video.transcoding.queue'));
    }

    public function uniqueId(): string
    {
        return "modulo:{$this->moduloId}:video";
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(
        VideoStorageService $storage,
        VideoTranscoderInterface $transcoder,
    ): void {
        $modulo = Modulo::find($this->moduloId);
        if (!$modulo) {
            Log::channel('treinamento')->warning('Modulo nao encontrado', ['modulo_id' => $this->moduloId]);
            return;
        }

        if ($modulo->video_status === VideoStatus::SEM_VIDEO) {
            // Cancelado entre enfileirar e processar
            return;
        }

        $modulo->update([
            'video_status' => VideoStatus::PROCESSANDO,
            'video_attempts' => $modulo->video_attempts + 1,
        ]);

        $tmpRoot = sys_get_temp_dir() . "/treinamento_video_{$modulo->id}_" . uniqid();
        mkdir($tmpRoot, 0755, true);
        $localOriginal = "{$tmpRoot}/original.mp4";
        $hlsLocalDir = "{$tmpRoot}/hls";

        try {
            $storage->downloadOriginalToLocal($modulo->video_original_path, $localOriginal);

            $result = $transcoder->transcodeToHls($localOriginal, $hlsLocalDir);

            $storage->deleteHlsTree($modulo->id);
            $storage->uploadLocalDirectory($hlsLocalDir, $modulo->id);

            if (!config('treinamento.video.keep_original')) {
                $storage->deleteOriginal($modulo->video_original_path);
            }

            $modulo->update([
                'video_status' => VideoStatus::PRONTO,
                'video_duracao_segundos' => $result['duracao_segundos'],
                'hls_manifest_path' => $storage->manifestPathFor($modulo->id),
                'video_processed_at' => now(),
                'video_error' => null,
            ]);

            Log::channel('treinamento')->info('Video processado', [
                'modulo_id' => $modulo->id,
                'duracao' => $result['duracao_segundos'],
            ]);
        } catch (\Throwable $e) {
            Log::channel('treinamento')->error('Erro no processamento de video', [
                'modulo_id' => $modulo->id,
                'erro' => $e->getMessage(),
            ]);
            $modulo->update([
                'video_status' => VideoStatus::FALHOU,
                'video_error' => substr($e->getMessage(), 0, 1000),
            ]);
            throw $e;
        } finally {
            $this->cleanupTmp($tmpRoot);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Modulo::where('id', $this->moduloId)->update([
            'video_status' => VideoStatus::FALHOU->value,
            'video_error' => substr($exception->getMessage(), 0, 1000),
        ]);
    }

    private function cleanupTmp(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
```

### Task 3.2: Criar `LimparUploadsOrfaosJob`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Jobs/LimparUploadsOrfaosJob.php`

- [ ] **Step 1: Escrever o job**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Services\VideoStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LimparUploadsOrfaosJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(VideoStorageService $storage): void
    {
        $cutoffMinutes = config('treinamento.video.orphan_cleanup.aguardando_upload_after_minutes');
        $cutoff = now()->subMinutes($cutoffMinutes);

        $orfaos = Modulo::where('video_status', VideoStatus::AGUARDANDO_UPLOAD->value)
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($orfaos as $modulo) {
            if ($modulo->video_original_path) {
                $storage->deleteOriginal($modulo->video_original_path);
            }
            $modulo->update([
                'video_status' => VideoStatus::SEM_VIDEO,
                'video_original_path' => null,
                'video_disk' => null,
                'video_tamanho_bytes' => null,
            ]);
        }

        Log::channel('treinamento')->info('Cleanup orfaos AGUARDANDO_UPLOAD', [
            'count' => $orfaos->count(),
        ]);

        if (!config('treinamento.video.keep_original')) {
            $dias = config('treinamento.video.orphan_cleanup.original_after_days');
            $cutoffOrig = now()->subDays($dias);

            $velhos = Modulo::where('video_status', VideoStatus::PRONTO->value)
                ->whereNotNull('video_original_path')
                ->where('video_processed_at', '<', $cutoffOrig)
                ->get();

            foreach ($velhos as $modulo) {
                $storage->deleteOriginal($modulo->video_original_path);
                $modulo->update(['video_original_path' => null]);
            }

            Log::channel('treinamento')->info('Cleanup originais antigos', [
                'count' => $velhos->count(),
            ]);
        }
    }
}
```

### Task 3.3: Validar localmente

- [ ] **Step 1: Despachar job manualmente em sync (sem worker)**

```bash
cd SDC && php artisan tinker --execute="config(['queue.default' => 'sync']); App\\Modules\\Treinamento\\Jobs\\ProcessarVideoModulo::dispatch(999);"
```

Expected: log mostra "Modulo nao encontrado" (id 999 não existe) — confirma o early return funciona.

- [ ] **Step 2: Verificar agendamento**

```bash
cd SDC && php artisan schedule:list
```

Expected: ver "treinamento:limpar-uploads-orfaos" listado às 03:00.

### Task 3.4: Commit da Fase 3

- [ ] **Step 1: Commit**

```bash
git add SDC/app/Modules/Treinamento/Jobs/

git commit -m "feat(treinamento): jobs de processamento HLS e cleanup de orfaos"
```

---

# FASE 4 — Backend API (controllers, requests, rotas, permissões)

**Goal:** Endpoints REST consumíveis pelo frontend; permissões instaladas.

### Task 4.1: Form Request `UploadInitRequest`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Requests/UploadInitRequest.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadInitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // checado por can: middleware + Modulo::podeSerGerenciadoPor
    }

    public function rules(): array
    {
        $maxBytes = (int) config('treinamento.video.max_size_mb') * 1024 * 1024;
        $extensoes = config('treinamento.video.extensoes_aceitas');

        return [
            'filename' => ['required', 'string', 'max:255'],
            'size_bytes' => ['required', 'integer', 'min:1', "max:{$maxBytes}"],
            'content_type' => ['required', 'string', 'max:120'],
            'extension' => ['required', 'string', Rule::in($extensoes)],
        ];
    }
}
```

### Task 4.2: Form Request `UploadFinalizeRequest`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Requests/UploadFinalizeRequest.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFinalizeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'blob_path' => ['required', 'string', 'starts_with:modulos/'],
        ];
    }
}
```

### Task 4.3: Form Request `UploadDirectRequest`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Requests/UploadDirectRequest.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDirectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $maxKb = (int) config('treinamento.video.max_size_mb') * 1024;
        $extensoes = implode(',', config('treinamento.video.extensoes_aceitas'));

        return [
            'video' => ['required', 'file', "mimes:{$extensoes}", "max:{$maxKb}"],
        ];
    }
}
```

### Task 4.4: `UploadInitController`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Video/UploadInitController.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Contracts\SasGeneratorInterface;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\UploadInitRequest;
use App\Modules\Treinamento\Services\VideoStorageService;
use Illuminate\Http\JsonResponse;

class UploadInitController extends Controller
{
    public function __invoke(
        UploadInitRequest $request,
        Treinamento $treinamento,
        Modulo $modulo,
        SasGeneratorInterface $sas,
        VideoStorageService $storage,
    ): JsonResponse {
        abort_unless($modulo->podeSerGerenciadoPor($request->user()), 403);
        abort_unless($modulo->treinamento_id === $treinamento->id, 404);
        abort_unless($modulo->video_status->podeReceberUpload(), 409, 'Modulo ja tem video ou esta processando');

        $blobPath = $storage->originalPathFor($modulo->id, $request->string('extension'));
        $ttl = (int) config('treinamento.video.sas.write_ttl_minutes');

        $modulo->update([
            'video_status' => VideoStatus::AGUARDANDO_UPLOAD,
            'video_disk' => config('treinamento.video.disk'),
            'video_original_path' => $blobPath,
            'video_tamanho_bytes' => $request->integer('size_bytes'),
            'video_error' => null,
        ]);

        if ($sas->isAvailable()) {
            $result = $sas->generateWriteUrl($blobPath, $ttl);
            return response()->json([
                'mode' => 'azure_direct',
                'upload_url' => $result['url'],
                'blob_path' => $result['blob_path'],
                'expires_at' => $result['expires_at']->format(\DateTimeInterface::ATOM),
                'max_size_bytes' => (int) config('treinamento.video.max_size_mb') * 1024 * 1024,
            ]);
        }

        return response()->json([
            'mode' => 'php_fallback',
            'direct_upload_url' => route('treinamentos.modulos.video.upload-direct', [
                'treinamento' => $treinamento->id,
                'modulo' => $modulo->id,
            ]),
            'max_size_bytes' => (int) config('treinamento.video.max_size_mb') * 1024 * 1024,
        ]);
    }
}
```

### Task 4.5: `UploadFinalizeController`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Video/UploadFinalizeController.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Jobs\ProcessarVideoModulo;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\UploadFinalizeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadFinalizeController extends Controller
{
    public function __invoke(
        UploadFinalizeRequest $request,
        Treinamento $treinamento,
        Modulo $modulo,
    ): JsonResponse {
        abort_unless($modulo->podeSerGerenciadoPor($request->user()), 403);
        abort_unless($modulo->treinamento_id === $treinamento->id, 404);
        abort_unless($modulo->video_status === VideoStatus::AGUARDANDO_UPLOAD, 409);

        $blobPath = $request->string('blob_path');
        abort_unless((string) $blobPath === (string) $modulo->video_original_path, 422, 'blob_path nao bate com o esperado');

        $disk = Storage::disk(config('treinamento.video.disk'));
        abort_unless($disk->exists((string) $blobPath), 422, 'Blob nao encontrado no Azure');

        $modulo->update([
            'video_status' => VideoStatus::PROCESSANDO,
            'video_uploaded_at' => now(),
        ]);

        ProcessarVideoModulo::dispatch($modulo->id);

        return response()->json([
            'status' => 'PROCESSANDO',
            'modulo_id' => $modulo->id,
            'video_uploaded_at' => $modulo->video_uploaded_at->toIso8601String(),
        ]);
    }
}
```

### Task 4.6: `UploadDirectController` (fallback)

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Video/UploadDirectController.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Jobs\ProcessarVideoModulo;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\UploadDirectRequest;
use App\Modules\Treinamento\Services\VideoStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadDirectController extends Controller
{
    public function __invoke(
        UploadDirectRequest $request,
        Treinamento $treinamento,
        Modulo $modulo,
        VideoStorageService $storage,
    ): JsonResponse {
        abort_unless($modulo->podeSerGerenciadoPor($request->user()), 403);
        abort_unless($modulo->treinamento_id === $treinamento->id, 404);
        abort_unless($modulo->video_status->podeReceberUpload(), 409);

        $file = $request->file('video');
        $blobPath = $storage->originalPathFor($modulo->id, $file->getClientOriginalExtension());

        $stream = fopen($file->getRealPath(), 'r');
        Storage::disk(config('treinamento.video.disk'))->writeStream($blobPath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $modulo->update([
            'video_status' => VideoStatus::PROCESSANDO,
            'video_disk' => config('treinamento.video.disk'),
            'video_original_path' => $blobPath,
            'video_tamanho_bytes' => $file->getSize(),
            'video_uploaded_at' => now(),
            'video_error' => null,
        ]);

        ProcessarVideoModulo::dispatch($modulo->id);

        return response()->json([
            'status' => 'PROCESSANDO',
            'modulo_id' => $modulo->id,
            'mode' => 'php_fallback',
        ]);
    }
}
```

### Task 4.7: `VideoStatusController`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Video/VideoStatusController.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoStatusController extends Controller
{
    public function __invoke(
        Request $request,
        Treinamento $treinamento,
        Modulo $modulo,
    ): JsonResponse {
        abort_unless($modulo->podeSerGerenciadoPor($request->user()), 403);
        abort_unless($modulo->treinamento_id === $treinamento->id, 404);

        return response()->json([
            'status' => $modulo->video_status->value,
            'status_label' => $modulo->video_status->label(),
            'duracao_segundos' => $modulo->video_duracao_segundos,
            'uploaded_at' => $modulo->video_uploaded_at?->toIso8601String(),
            'processed_at' => $modulo->video_processed_at?->toIso8601String(),
            'erro' => $modulo->video_error,
            'attempts' => $modulo->video_attempts,
        ]);
    }
}
```

### Task 4.8: `VideoUrlController`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Video/VideoUrlController.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Contracts\SasGeneratorInterface;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoUrlController extends Controller
{
    public function __invoke(
        Request $request,
        Treinamento $treinamento,
        Modulo $modulo,
        SasGeneratorInterface $sas,
    ): JsonResponse {
        abort_unless($modulo->treinamento_id === $treinamento->id, 404);

        $user = $request->user();
        $podeAssistir = $modulo->podeSerGerenciadoPor($user)
            || Inscricao::where('treinamento_id', $treinamento->id)
                ->where('user_id', $user->id)
                ->where('status', 'APROVADA')
                ->exists();

        abort_unless($podeAssistir, 403, 'Voce precisa estar inscrito e aprovado neste treinamento');
        abort_unless($modulo->video_status === VideoStatus::PRONTO, 409, 'Video ainda processando ou indisponivel');

        $ttl = (int) config('treinamento.video.sas.read_ttl_minutes');
        $result = $sas->generateReadUrl($modulo->hls_manifest_path, $ttl);

        return response()->json([
            'manifest_url' => $result['url'],
            'expires_at' => $result['expires_at']->format(\DateTimeInterface::ATOM),
            'duracao_segundos' => $modulo->video_duracao_segundos,
        ]);
    }
}
```

### Task 4.9: `ReprocessarVideoController`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Video/ReprocessarVideoController.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Jobs\ProcessarVideoModulo;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReprocessarVideoController extends Controller
{
    public function __invoke(
        Request $request,
        Treinamento $treinamento,
        Modulo $modulo,
    ): JsonResponse {
        abort_unless($modulo->podeSerGerenciadoPor($request->user()), 403);
        abort_unless($modulo->treinamento_id === $treinamento->id, 404);
        abort_unless($modulo->video_status->podeReprocessar(), 409, 'Reprocessar so em estado FALHOU');

        $modulo->update([
            'video_status' => VideoStatus::PROCESSANDO,
            'video_error' => null,
        ]);

        ProcessarVideoModulo::dispatch($modulo->id);

        return response()->json(['status' => 'PROCESSANDO']);
    }
}
```

### Task 4.10: `DeletarVideoController`

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Video/DeletarVideoController.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\VideoStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeletarVideoController extends Controller
{
    public function __invoke(
        Request $request,
        Treinamento $treinamento,
        Modulo $modulo,
        VideoStorageService $storage,
    ): JsonResponse {
        abort_unless($modulo->podeSerGerenciadoPor($request->user()), 403);
        abort_unless($modulo->treinamento_id === $treinamento->id, 404);

        if ($modulo->video_original_path) {
            $storage->deleteOriginal($modulo->video_original_path);
        }
        $storage->deleteHlsTree($modulo->id);

        $modulo->update([
            'video_status' => VideoStatus::SEM_VIDEO,
            'video_disk' => null,
            'video_original_path' => null,
            'hls_manifest_path' => null,
            'video_duracao_segundos' => null,
            'video_tamanho_bytes' => null,
            'video_uploaded_at' => null,
            'video_processed_at' => null,
            'video_error' => null,
            'video_attempts' => 0,
        ]);

        return response()->json(['status' => 'SEM_VIDEO']);
    }
}
```

### Task 4.11: Criar `TreinamentoPermissionsSeeder`

**Files:**
- Create: `SDC/database/seeders/TreinamentoPermissionsSeeder.php`

- [ ] **Step 1: Escrever**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TreinamentoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissoes = [
            'treinamento.videos.gerenciar' => 'Upload, reprocessar e deletar videos de modulos',
            'treinamento.videos.assistir' => 'Assistir videos dos treinamentos inscritos',
        ];

        foreach ($permissoes as $slug => $descricao) {
            Permission::firstOrCreate(
                ['name' => $slug, 'guard_name' => 'web'],
                ['description' => $descricao]
            );
        }

        $admin = Role::where('name', 'admin')->first();
        $superAdmin = Role::where('name', 'super-admin')->first();

        $admin?->givePermissionTo('treinamento.videos.gerenciar');
        $superAdmin?->givePermissionTo('treinamento.videos.gerenciar');
    }
}
```

### Task 4.12: Registrar seeder no `DatabaseSeeder`

**Files:**
- Modify: `SDC/database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Adicionar chamada do novo seeder**

Localize o método `run()` e adicione, junto aos outros `$this->call(...)`:

```php
        $this->call(TreinamentoPermissionsSeeder::class);
```

### Task 4.13: Adicionar rotas de vídeo

**Files:**
- Modify: `SDC/routes/modules/treinamento.php`

- [ ] **Step 1: Substituir conteúdo completo**

```php
<?php

use App\Modules\Treinamento\Controllers\TreinamentoIndexController;
use App\Modules\Treinamento\Controllers\TreinamentoShowController;
use App\Modules\Treinamento\Controllers\TreinamentoStoreController;
use App\Modules\Treinamento\Controllers\Video\DeletarVideoController;
use App\Modules\Treinamento\Controllers\Video\ReprocessarVideoController;
use App\Modules\Treinamento\Controllers\Video\UploadDirectController;
use App\Modules\Treinamento\Controllers\Video\UploadFinalizeController;
use App\Modules\Treinamento\Controllers\Video\UploadInitController;
use App\Modules\Treinamento\Controllers\Video\VideoStatusController;
use App\Modules\Treinamento\Controllers\Video\VideoUrlController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Modulo Treinamento
|--------------------------------------------------------------------------
*/

Route::prefix('treinamentos')->name('treinamentos.')->group(function () {

    Route::get('/', TreinamentoIndexController::class)
        ->name('index')
        ->middleware('can:treinamento.cursos.view');
    Route::get('/export', \App\Modules\Treinamento\Controllers\TreinamentoExportController::class)
        ->name('export')
        ->middleware('can:treinamento.cursos.export');
    Route::get('/{id}', TreinamentoShowController::class)
        ->name('show')
        ->middleware('can:treinamento.cursos.view');

    Route::middleware('can:treinamento.cursos.create')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::post('/', TreinamentoStoreController::class)->name('store');
        });

    // ------------------------------------------------------------------
    // Modulos / Video Pipeline (Spec 1)
    // ------------------------------------------------------------------
    Route::prefix('{treinamento}/modulos/{modulo}/video')
        ->name('modulos.video.')
        ->scopeBindings()
        ->group(function () {
            Route::post('upload-init', UploadInitController::class)->name('upload-init');
            Route::post('upload-finalize', UploadFinalizeController::class)->name('upload-finalize');
            Route::post('upload-direct', UploadDirectController::class)->name('upload-direct');
            Route::get('status', VideoStatusController::class)->name('status');
            Route::get('url', VideoUrlController::class)->name('url');
            Route::post('reprocessar', ReprocessarVideoController::class)->name('reprocessar');
            Route::delete('/', DeletarVideoController::class)->name('delete');
        });
});
```

### Task 4.14: Validar localmente

- [ ] **Step 1: Rodar seeder**

```bash
cd SDC && php artisan db:seed --class=TreinamentoPermissionsSeeder
```

Expected: cria 2 permissões; sem erro.

- [ ] **Step 2: Verificar rotas registradas**

```bash
cd SDC && php artisan route:list | grep video
```

Expected: ver 7 linhas com `treinamentos.modulos.video.*`.

- [ ] **Step 3: Smoke do endpoint status**

Crie um módulo de teste no Tinker e chame o endpoint:

```bash
cd SDC && php artisan tinker --execute="\$m = App\\Modules\\Treinamento\\Models\\Modulo::first(); dd(\$m?->video_status);"
```

Expected: instância de `VideoStatus::SEM_VIDEO`.

### Task 4.15: Adicionar audit log em upload-finalize, reprocessar e deletar

**Files:**
- Modify: `SDC/app/Modules/Treinamento/Controllers/Video/UploadFinalizeController.php`
- Modify: `SDC/app/Modules/Treinamento/Controllers/Video/ReprocessarVideoController.php`
- Modify: `SDC/app/Modules/Treinamento/Controllers/Video/DeletarVideoController.php`

O `AuditLog` do projeto usa API estática `AuditLog::log($event, $tableName, $rowId, $old, $new)` definida em [SDC/app/Models/AuditLog.php](SDC/app/Models/AuditLog.php).

- [ ] **Step 1: `UploadFinalizeController` — adicionar log após o `dispatch`**

Adicione `use App\Models\AuditLog;` e, logo antes do `return response()->json(...)`:

```php
AuditLog::log(
    event: 'video_upload_finalize',
    tableName: 'modulos',
    rowId: $modulo->id,
    oldValues: ['video_status' => 'AGUARDANDO_UPLOAD'],
    newValues: [
        'video_status' => 'PROCESSANDO',
        'video_tamanho_bytes' => $modulo->video_tamanho_bytes,
    ],
);
```

- [ ] **Step 2: `ReprocessarVideoController` — adicionar log antes do `return`**

Adicione `use App\Models\AuditLog;` e:

```php
AuditLog::log(
    event: 'video_reprocessar',
    tableName: 'modulos',
    rowId: $modulo->id,
    oldValues: ['video_status' => 'FALHOU'],
    newValues: ['video_status' => 'PROCESSANDO'],
);
```

- [ ] **Step 3: `DeletarVideoController` — adicionar log antes do `update` (para capturar valores antigos)**

Adicione `use App\Models\AuditLog;` e, logo após os `abort_unless(...)`:

```php
AuditLog::log(
    event: 'video_delete',
    tableName: 'modulos',
    rowId: $modulo->id,
    oldValues: [
        'video_status' => $modulo->video_status->value,
        'hls_manifest_path' => $modulo->hls_manifest_path,
        'video_tamanho_bytes' => $modulo->video_tamanho_bytes,
    ],
    newValues: ['video_status' => 'SEM_VIDEO'],
);
```

### Task 4.16: Commit da Fase 4

- [ ] **Step 1: Commit**

```bash
git add SDC/app/Modules/Treinamento/Controllers/Video/ \
        SDC/app/Modules/Treinamento/Requests/ \
        SDC/database/seeders/TreinamentoPermissionsSeeder.php \
        SDC/database/seeders/DatabaseSeeder.php \
        SDC/routes/modules/treinamento.php

git commit -m "feat(treinamento): API de pipeline de video (controllers, requests, rotas, permissoes)"
```

---

# FASE 5 — Frontend Foundation (npm packages + composables)

**Goal:** Dependências de player e lógica reutilizável de upload/play.

### Task 5.1: Instalar Video.js e plugin HLS

**Files:**
- Modify: `SDC/package.json` (via npm)

- [ ] **Step 1: Instalar**

```bash
cd SDC && npm install video.js@8 @videojs/http-streaming@3
```

Expected: `package.json` ganha `video.js` e `@videojs/http-streaming` em `dependencies`.

### Task 5.2: Composable `useVideoUpload`

**Files:**
- Create: `SDC/resources/js/composables/useVideoUpload.js`

- [ ] **Step 1: Escrever**

```javascript
import { ref } from 'vue';
import axios from 'axios';

export function useVideoUpload({ treinamentoId, moduloId }) {
  const status = ref('idle');
  const progress = ref(0);
  const errorMessage = ref(null);

  const initRoute = route('treinamentos.modulos.video.upload-init', {
    treinamento: treinamentoId,
    modulo: moduloId,
  });

  async function startUpload(file) {
    status.value = 'initiating';
    progress.value = 0;
    errorMessage.value = null;

    let initResp;
    try {
      initResp = await axios.post(initRoute, {
        filename: file.name,
        size_bytes: file.size,
        content_type: file.type || 'application/octet-stream',
        extension: extractExtension(file.name),
      });
    } catch (e) {
      status.value = 'error';
      errorMessage.value = e.response?.data?.message || 'Falha ao iniciar upload';
      return;
    }

    const data = initResp.data;

    if (data.mode === 'azure_direct') {
      const ok = await tryAzureDirect(data, file);
      if (!ok) {
        await runPhpFallback(file);
      }
    } else if (data.mode === 'php_fallback') {
      await uploadDirect(data.direct_upload_url, file);
    }
  }

  async function tryAzureDirect(initData, file) {
    status.value = 'uploading_azure';
    try {
      await axios.put(initData.upload_url, file, {
        headers: {
          'x-ms-blob-type': 'BlockBlob',
          'Content-Type': file.type || 'application/octet-stream',
        },
        onUploadProgress: (e) => {
          if (e.total) progress.value = Math.round((e.loaded / e.total) * 100);
        },
      });
      await finalizeAzure(initData.blob_path);
      return true;
    } catch (e) {
      console.warn('Upload SAS falhou, caindo para PHP fallback', e);
      return false;
    }
  }

  async function finalizeAzure(blobPath) {
    const finalizeRoute = route('treinamentos.modulos.video.upload-finalize', {
      treinamento: treinamentoId,
      modulo: moduloId,
    });
    status.value = 'finalizing';
    try {
      await axios.post(finalizeRoute, { blob_path: blobPath });
      status.value = 'processing';
    } catch (e) {
      status.value = 'error';
      errorMessage.value = e.response?.data?.message || 'Falha ao finalizar upload';
    }
  }

  async function runPhpFallback(file) {
    const directRoute = route('treinamentos.modulos.video.upload-direct', {
      treinamento: treinamentoId,
      modulo: moduloId,
    });
    await uploadDirect(directRoute, file);
  }

  async function uploadDirect(url, file) {
    status.value = 'uploading_php';
    progress.value = 0;
    const formData = new FormData();
    formData.append('video', file);
    try {
      await axios.post(url, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (e) => {
          if (e.total) progress.value = Math.round((e.loaded / e.total) * 100);
        },
      });
      status.value = 'processing';
    } catch (e) {
      status.value = 'error';
      errorMessage.value = e.response?.data?.message || 'Falha no upload via PHP';
    }
  }

  function extractExtension(filename) {
    const idx = filename.lastIndexOf('.');
    return idx >= 0 ? filename.substring(idx + 1).toLowerCase() : '';
  }

  return { status, progress, errorMessage, startUpload };
}
```

### Task 5.3: Composable `useVideoPlayer`

**Files:**
- Create: `SDC/resources/js/composables/useVideoPlayer.js`

- [ ] **Step 1: Escrever**

```javascript
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import videojs from 'video.js';
import '@videojs/http-streaming';

export function useVideoPlayer({ treinamentoId, moduloId, videoElRef }) {
  const player = ref(null);
  const errorMessage = ref(null);
  const isLoading = ref(true);

  async function fetchSource() {
    const url = route('treinamentos.modulos.video.url', {
      treinamento: treinamentoId,
      modulo: moduloId,
    });
    const { data } = await axios.get(url);
    return data;
  }

  async function init() {
    try {
      const src = await fetchSource();
      player.value = videojs(videoElRef.value, {
        controls: true,
        preload: 'metadata',
        fluid: true,
        sources: [{ src: src.manifest_url, type: 'application/x-mpegURL' }],
      });

      player.value.on('error', async () => {
        const err = player.value.error();
        if (err && (err.code === 4 || err.code === 2)) {
          // network / source error -> tentar renovar SAS
          try {
            const fresh = await fetchSource();
            player.value.src({ src: fresh.manifest_url, type: 'application/x-mpegURL' });
            player.value.load();
            player.value.play();
          } catch (e) {
            errorMessage.value = 'Nao foi possivel renovar acesso ao video';
          }
        }
      });

      isLoading.value = false;
    } catch (e) {
      errorMessage.value = e.response?.data?.message || 'Erro ao carregar video';
      isLoading.value = false;
    }
  }

  onMounted(init);
  onBeforeUnmount(() => {
    if (player.value) {
      player.value.dispose();
      player.value = null;
    }
  });

  return { player, errorMessage, isLoading };
}
```

### Task 5.4: Validar localmente

- [ ] **Step 1: Compilar frontend para verificar imports**

```bash
cd SDC && npm run build
```

Expected: build sem erros; `dist` gerado.

### Task 5.5: Commit da Fase 5

- [ ] **Step 1: Commit**

```bash
git add SDC/package.json SDC/package-lock.json \
        SDC/resources/js/composables/useVideoUpload.js \
        SDC/resources/js/composables/useVideoPlayer.js

git commit -m "feat(treinamento): instala Video.js e composables de upload e player"
```

---

# FASE 6 — Frontend Components

**Goal:** 3 componentes Vue prontos para serem embutidos.

### Task 6.1: `VideoPlayer.vue`

**Files:**
- Create: `SDC/resources/js/Components/Treinamento/VideoPlayer.vue`

- [ ] **Step 1: Escrever**

```vue
<script setup>
import { ref } from 'vue';
import { useVideoPlayer } from '@/composables/useVideoPlayer';
import 'video.js/dist/video-js.css';

const props = defineProps({
  treinamentoId: { type: [Number, String], required: true },
  moduloId: { type: [Number, String], required: true },
});

const videoEl = ref(null);
const { errorMessage, isLoading } = useVideoPlayer({
  treinamentoId: props.treinamentoId,
  moduloId: props.moduloId,
  videoElRef: videoEl,
});
</script>

<template>
  <div class="w-full">
    <div v-if="errorMessage" class="p-4 text-red-600 bg-red-50 border border-red-200 rounded">
      {{ errorMessage }}
    </div>
    <div v-else>
      <div v-if="isLoading" class="p-8 text-center text-slate-500">
        Carregando video...
      </div>
      <video
        ref="videoEl"
        class="video-js vjs-default-skin vjs-big-play-centered w-full"
        playsinline
      />
    </div>
  </div>
</template>
```

### Task 6.2: `VideoUploader.vue`

**Files:**
- Create: `SDC/resources/js/Components/Treinamento/VideoUploader.vue`

- [ ] **Step 1: Escrever**

```vue
<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useVideoUpload } from '@/composables/useVideoUpload';

const props = defineProps({
  treinamentoId: { type: [Number, String], required: true },
  moduloId: { type: [Number, String], required: true },
  maxMb: { type: Number, default: 500 },
});

const emit = defineEmits(['processing-started', 'completed']);

const fileInput = ref(null);
const fileName = ref(null);
const processingStatus = ref(null);
const pollHandle = ref(null);

const { status, progress, errorMessage, startUpload } = useVideoUpload({
  treinamentoId: props.treinamentoId,
  moduloId: props.moduloId,
});

function onFileChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  fileName.value = file.name;
  startUpload(file).then(() => {
    if (status.value === 'processing') {
      emit('processing-started');
      pollProcessing();
    }
  });
}

async function pollProcessing() {
  const url = route('treinamentos.modulos.video.status', {
    treinamento: props.treinamentoId,
    modulo: props.moduloId,
  });
  pollHandle.value = setInterval(async () => {
    try {
      const { data } = await axios.get(url);
      processingStatus.value = data;
      if (data.status === 'PRONTO' || data.status === 'FALHOU') {
        clearInterval(pollHandle.value);
        emit('completed', data);
      }
    } catch (e) {
      clearInterval(pollHandle.value);
    }
  }, 4000);
}
</script>

<template>
  <div class="space-y-3">
    <input
      ref="fileInput"
      type="file"
      accept="video/mp4,video/quicktime,video/x-matroska"
      class="block w-full text-sm text-slate-500
             file:mr-4 file:py-2 file:px-4 file:rounded-lg
             file:border-0 file:bg-blue-50 file:text-blue-700
             hover:file:bg-blue-100"
      @change="onFileChange"
    />

    <div v-if="fileName" class="text-sm text-slate-700">
      Arquivo: {{ fileName }}
    </div>

    <div v-if="status === 'uploading_azure' || status === 'uploading_php'" class="space-y-1">
      <div class="text-sm">
        Enviando ({{ status === 'uploading_azure' ? 'Azure' : 'PHP fallback' }})... {{ progress }}%
      </div>
      <div class="w-full bg-slate-200 rounded h-2 overflow-hidden">
        <div class="bg-blue-600 h-2" :style="{ width: progress + '%' }"></div>
      </div>
    </div>

    <div v-else-if="status === 'finalizing'" class="text-sm text-slate-600">
      Finalizando upload...
    </div>

    <div v-else-if="status === 'processing'" class="text-sm text-amber-700">
      Processando video (transcoding HLS). Pode levar alguns minutos...
      <div v-if="processingStatus" class="text-xs text-slate-500">
        Status: {{ processingStatus.status }}
      </div>
    </div>

    <div v-else-if="status === 'error'" class="text-sm text-red-700">
      {{ errorMessage }}
    </div>
  </div>
</template>
```

### Task 6.3: `ModuloPlayer.vue`

**Files:**
- Create: `SDC/resources/js/Components/Treinamento/ModuloPlayer.vue`

- [ ] **Step 1: Escrever**

```vue
<script setup>
import VideoPlayer from '@/Components/Treinamento/VideoPlayer.vue';

const props = defineProps({
  treinamentoId: { type: [Number, String], required: true },
  modulo: { type: Object, required: true },
});

const status = props.modulo.videoStatus || 'SEM_VIDEO';
</script>

<template>
  <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-lg font-semibold">{{ modulo.titulo }}</h3>
      <span class="text-sm text-slate-500">{{ modulo.cargaHoraria }}h</span>
    </div>

    <p v-if="modulo.descricao" class="text-sm text-slate-600 mb-3">
      {{ modulo.descricao }}
    </p>

    <VideoPlayer
      v-if="status === 'PRONTO'"
      :treinamento-id="treinamentoId"
      :modulo-id="modulo.id"
    />

    <div v-else-if="status === 'PROCESSANDO'" class="p-4 text-amber-700 bg-amber-50 border border-amber-200 rounded">
      Video em processamento. Atualize a pagina em instantes.
    </div>

    <div v-else-if="status === 'FALHOU'" class="p-4 text-red-700 bg-red-50 border border-red-200 rounded">
      Erro ao processar video. Contate o instrutor.
    </div>

    <div v-else class="p-4 text-slate-500 bg-slate-50 border border-slate-200 rounded">
      Sem video disponivel.
    </div>
  </div>
</template>
```

### Task 6.4: Validar localmente

- [ ] **Step 1: Build de novo**

```bash
cd SDC && npm run build
```

Expected: build sem erros.

### Task 6.5: Commit da Fase 6

- [ ] **Step 1: Commit**

```bash
git add SDC/resources/js/Components/Treinamento/

git commit -m "feat(treinamento): componentes VideoPlayer, VideoUploader e ModuloPlayer"
```

---

# FASE 7 — Frontend Integration

**Goal:** Embutir player na view do aluno e criar tela de upload para admin/instrutor.

### Task 7.1: Refatorar `TreinamentoShow.vue` para listar módulos com player

**Files:**
- Modify: `SDC/resources/js/Pages/Treinamento/TreinamentoShow.vue`

- [ ] **Step 1: Substituir conteúdo**

```vue
<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import ModuloPlayer from '@/Components/Treinamento/ModuloPlayer.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  treinamento: { type: Object, required: true },
  modulos: { type: Array, default: () => [] },
});

const formatDate = (dateValue) => {
  if (!dateValue) return null;
  const str = String(dateValue).trim();
  if (str.includes('/')) return str;
  const d = new Date(dateValue);
  if (isNaN(d.getTime())) return str;
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

const goBack = () => router.visit(route('treinamentos.index'));
</script>

<template>
  <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <button
      @click="goBack"
      class="mb-4 flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      Voltar
    </button>

    <CardBase class="p-6 mb-6">
      <div class="mb-6">
        <div class="flex items-start justify-between mb-3">
          <Heading :level="1" class="text-2xl font-bold text-slate-800 dark:text-slate-100">
            {{ treinamento.titulo }}
          </Heading>
          <Badge :color="treinamento.statusColor" class="text-sm">
            {{ treinamento.statusLabel }}
          </Badge>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
          <Badge :color="treinamento.tipoColor">{{ treinamento.tipoLabel }}</Badge>
          <Text size="sm" color="muted">{{ treinamento.cargaHoraria }}h de carga horaria</Text>
        </div>
      </div>

      <div v-if="treinamento.descricao" class="mb-6">
        <Heading :level="3" class="text-lg font-semibold mb-2">Descricao</Heading>
        <Text size="base" class="text-slate-700 dark:text-slate-300">{{ treinamento.descricao }}</Text>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div v-if="treinamento.instrutor">
          <Text size="sm" color="muted" class="mb-1">Instrutor</Text>
          <Text size="base" class="font-medium">{{ treinamento.instrutor }}</Text>
        </div>
        <div v-if="treinamento.local">
          <Text size="sm" color="muted" class="mb-1">Local</Text>
          <Text size="base" class="font-medium">{{ treinamento.local }}</Text>
        </div>
        <div v-if="treinamento.dataInicio">
          <Text size="sm" color="muted" class="mb-1">Data de Inicio</Text>
          <Text size="base" class="font-medium">{{ formatDate(treinamento.dataInicio) }}</Text>
        </div>
        <div v-if="treinamento.dataFim">
          <Text size="sm" color="muted" class="mb-1">Data de Termino</Text>
          <Text size="base" class="font-medium">{{ formatDate(treinamento.dataFim) }}</Text>
        </div>
      </div>
    </CardBase>

    <div v-if="modulos.length > 0">
      <Heading :level="2" class="text-xl font-semibold mb-4">Modulos</Heading>
      <ModuloPlayer
        v-for="modulo in modulos"
        :key="modulo.id"
        :treinamento-id="treinamento.id"
        :modulo="modulo"
      />
    </div>
    <div v-else class="text-slate-500 text-center py-8">
      Nenhum modulo cadastrado.
    </div>
  </div>
</template>
```

### Task 7.2: Atualizar `TreinamentoShowController` para enviar módulos

**Files:**
- Modify: `SDC/app/Modules/Treinamento/Controllers/TreinamentoShowController.php`

- [ ] **Step 1: Substituir conteúdo completo do controller**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Services\TreinamentoService;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoShowController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $treinamento = $this->treinamentoService->findById($id);

        if (!$treinamento) {
            abort(404, 'Treinamento nao encontrado');
        }

        $modulos = Modulo::porTreinamento($id)
            ->ordenados()
            ->get()
            ->map(fn (Modulo $m) => [
                'id' => $m->id,
                'titulo' => $m->titulo,
                'descricao' => $m->descricao,
                'cargaHoraria' => $m->carga_horaria,
                'ordem' => $m->ordem,
                'videoStatus' => $m->video_status?->value ?? 'SEM_VIDEO',
                'duracaoSegundos' => $m->video_duracao_segundos,
            ])
            ->toArray();

        return Inertia::render('Treinamento/TreinamentoShow', [
            'treinamento' => $treinamento,
            'modulos' => $modulos,
        ]);
    }
}
```

### Task 7.3: Criar `ModuloEdit.vue` (tela admin/instrutor)

**Files:**
- Create: `SDC/resources/js/Pages/Treinamento/ModuloEdit.vue`

- [ ] **Step 1: Escrever**

```vue
<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import VideoUploader from '@/Components/Treinamento/VideoUploader.vue';
import VideoPlayer from '@/Components/Treinamento/VideoPlayer.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  treinamento: { type: Object, required: true },
  modulo: { type: Object, required: true },
});

const videoStatus = ref(props.modulo.videoStatus || 'SEM_VIDEO');

function onCompleted(data) {
  videoStatus.value = data.status;
}

async function reprocessar() {
  await axios.post(route('treinamentos.modulos.video.reprocessar', {
    treinamento: props.treinamento.id,
    modulo: props.modulo.id,
  }));
  videoStatus.value = 'PROCESSANDO';
}

async function deletar() {
  if (!confirm('Deletar o video deste modulo? Acao irreversivel.')) return;
  await axios.delete(route('treinamentos.modulos.video.delete', {
    treinamento: props.treinamento.id,
    modulo: props.modulo.id,
  }));
  videoStatus.value = 'SEM_VIDEO';
}
</script>

<template>
  <div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <CardBase class="p-6 mb-6">
      <Heading :level="1" class="text-xl font-bold mb-1">
        {{ treinamento.titulo }} — {{ modulo.titulo }}
      </Heading>
      <p class="text-sm text-slate-500 mb-4">Gerenciar video do modulo</p>

      <div class="mb-4">
        Status atual:
        <span class="font-medium">{{ videoStatus }}</span>
      </div>

      <div v-if="videoStatus === 'SEM_VIDEO' || videoStatus === 'FALHOU'">
        <VideoUploader
          :treinamento-id="treinamento.id"
          :modulo-id="modulo.id"
          @completed="onCompleted"
        />
      </div>

      <div v-if="videoStatus === 'PRONTO'" class="space-y-4">
        <VideoPlayer
          :treinamento-id="treinamento.id"
          :modulo-id="modulo.id"
        />
        <button
          class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm"
          @click="deletar"
        >
          Deletar video
        </button>
      </div>

      <div v-if="videoStatus === 'FALHOU'" class="mt-3">
        <button
          class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded text-sm"
          @click="reprocessar"
        >
          Reprocessar
        </button>
      </div>
    </CardBase>
  </div>
</template>
```

NOTA: A rota Inertia que renderiza esta página (`treinamentos.admin.modulos.edit`) precisa de um controller. Se ainda não existir, crie um `ModuloEditController` em `Controllers/ModuloEditController.php` que retorna `Inertia::render('Treinamento/ModuloEdit', [...])`. Adicione a rota dentro do grupo admin existente em `routes/modules/treinamento.php`. Como é boilerplate, mantido como TODO específico se controllers admin de módulo ainda não foram implementados nesta fase do projeto.

### Task 7.4: Validar localmente

- [ ] **Step 1: Build + serve**

```bash
cd SDC && npm run build && php artisan octane:start
```

Expected: app sobe, navegar até /treinamentos/{id} mostra lista de módulos.

- [ ] **Step 2: Smoke E2E manual**

Como admin, abrir um módulo, fazer upload de um .mp4 pequeno (~10MB), aguardar processamento, conferir player aparece.

### Task 7.5: Commit da Fase 7

- [ ] **Step 1: Commit**

```bash
git add SDC/resources/js/Pages/Treinamento/TreinamentoShow.vue \
        SDC/resources/js/Pages/Treinamento/ModuloEdit.vue \
        SDC/app/Modules/Treinamento/Controllers/TreinamentoShowController.php

git commit -m "feat(treinamento): integra player e uploader nas paginas de show e edit"
```

---

# FASE 8 — Worker Container (Docker + Supervisor)

**Goal:** Worker dedicado para queue `videos` rodando FFmpeg, isolado do worker de queues padrão.

### Task 8.1: Adicionar FFmpeg no `Dockerfile.queue`

**Files:**
- Modify: `SDC/docker/Dockerfile.queue`

- [ ] **Step 1: Adicionar `ffmpeg` na lista de pacotes apk**

Localize o bloco `RUN apk add --no-cache ...` e adicione `ffmpeg`:

```dockerfile
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    mysql-client \
    postgresql-client \
    postgresql-dev \
    composer \
    ffmpeg
```

### Task 8.2: Adicionar program `videos-worker` no supervisor

**Files:**
- Modify: `SDC/docker/supervisor/laravel-worker.conf`

- [ ] **Step 1: Adicionar bloco abaixo do `laravel-worker` existente**

```ini
[program:videos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=videos --sleep=3 --tries=3 --max-time=3600 --timeout=3600 --memory=2048
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/videos-worker.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=3
stopwaitsecs=3600
startsecs=0
```

### Task 8.3: Validar imagem (build local)

- [ ] **Step 1: Build da imagem do queue**

```bash
cd SDC && docker build -f docker/Dockerfile.queue -t sdc-queue:test .
```

Expected: build OK. Verificar que FFmpeg está disponível:

```bash
docker run --rm sdc-queue:test ffmpeg -version
```

Expected: imprime versão do FFmpeg.

### Task 8.4: Commit da Fase 8

- [ ] **Step 1: Commit**

```bash
git add SDC/docker/Dockerfile.queue SDC/docker/supervisor/laravel-worker.conf

git commit -m "feat(treinamento): worker dedicado de videos com FFmpeg via supervisor"
```

---

## Pós-implementação — Checklist de deploy em Azure

Estes itens NÃO são parte do plano de código, mas precisam ser executados em conjunto pelo time de infra antes do release:

- [ ] Criar container `sdc-treinamentos` no Storage Account de produção (`sdcdefesa`)
- [ ] Habilitar CORS no Storage Account: permitir método `PUT` do domínio do frontend
- [ ] Configurar variáveis no App Service:
  - `TREINAMENTO_VIDEO_DISK=treinamentos`
  - `TREINAMENTO_VIDEO_CONTAINER=sdc-treinamentos`
  - `TREINAMENTO_VIDEO_MAX_MB=500`
  - `TREINAMENTO_KEEP_ORIGINAL=false`
  - `TREINAMENTO_QUEUE=videos`
  - `TREINAMENTO_SAS_WRITE_TTL=30`
  - `TREINAMENTO_SAS_READ_TTL=120`
  - `AZURE_STORAGE_CONTAINER_TREINAMENTOS=sdc-treinamentos`
- [ ] Aumentar `upload_max_filesize=500M`, `post_max_size=550M`, `max_execution_time=600` no `php.ini` do FrankenPHP em produção (necessário para o fallback PHP)
- [ ] Subir o container `queue` rebuilt com FFmpeg
- [ ] Verificar que `php artisan schedule:list` mostra `treinamento:limpar-uploads-orfaos`
- [ ] Smoke test E2E em staging antes de promote
