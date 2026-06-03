# Padrão: AsynchronousResponse com trace persistido

Guia rápido para aplicar o padrão de resposta assíncrona em novos
controllers heavy/expensive do NewSDC. Foundation entregue em
`feat/db-arquitetura-f1` (commits `9467365d` e `be5375d2`).

## Quando usar

Aplique sempre que a rota:

- Gera artefato pesado (Excel/CSV/PDF/ZIP) com mais de ~1 segundo de
  processamento.
- Faz agregação de dashboard que escaneia muitas linhas.
- É classificada como `heavy` ou `expensive` por `ApiRateLimiter::getRouteCost`
  (paths que contêm `export`, `relatorio`, `report`, `dashboard`,
  `analytics`, `batch`, `import`).

Não aplique para:

- Downloads de arquivo já existente (`Storage::download($path)`).
- Rotas leves de leitura (`GET /resource/{id}`).
- Endpoints com SLA estrito de resposta síncrona (webhooks invertidos).

## Estratégia: rota nova lado a lado, NÃO breaking change

Sempre adicione **uma rota nova async** preservando a síncrona legacy
quando a sync já está em produção e tem clientes integrados. Ex.: Power BI,
mobile, integrações externas. Marque a sync como `@deprecated` em PHPDoc
e migre clientes incrementalmente.

Em rotas novas (greenfield), pode nascer já async.

## Pieces envolvidas

- `App\Http\Controllers\Traits\AsynchronousResponse` — trait do controller.
- `App\Models\RequestTrace` — Eloquent model do trace.
- `App\Jobs\Concerns\TracksAsyncProgress` — trait do job que atualiza o trace.
- `App\Http\Controllers\Api\V1\TraceController` — endpoints `GET /api/v1/traces/{id}`
  (status) e `GET /api/v1/traces/{id}/download` (artefato).
- `Storage::disk('exports')` — onde os artefatos vivem.

## Receita em 4 passos

### 1. Crie o job em `app/Jobs/<Modulo>/<Acao>Job.php`

```php
<?php

declare(strict_types=1);

namespace App\Jobs\MeuModulo;

use App\Jobs\Concerns\TracksAsyncProgress;
use App\Modules\MeuModulo\Services\MeuService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GerarRelatorioJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksAsyncProgress;

    public int $tries = 2;
    public int $timeout = 600;

    /** @param array<string, mixed> $filters */
    public function __construct(string $traceId, public array $filters)
    {
        $this->setTrace($traceId);
    }

    public function handle(MeuService $service): void
    {
        $this->runTracked(function () use ($service): array {
            $bytes = $service->gerarRelatorio($this->filters);
            $path = sprintf(
                'meumodulo/relatorio_%s_%s.xlsx',
                now()->format('Ymd_His'),
                substr($this->traceId ?? 'x', 0, 8),
            );

            Storage::disk('exports')->put($path, $bytes);

            return ['disk' => 'exports', 'path' => $path];
        });
    }
}
```

### 2. No controller, adicione o método async lado a lado

```php
use App\Http\Controllers\Traits\AsynchronousResponse;
use App\Jobs\MeuModulo\GerarRelatorioJob;

class MeuController extends Controller
{
    use AsynchronousResponse;

    public function gerarRelatorioAsync(Request $request): JsonResponse
    {
        return $this->dispatchAsyncJob(
            jobClass: GerarRelatorioJob::class,
            type: 'meumodulo_relatorio',
            args: [$request->query()],
            meta: ['filters' => $request->query()],
            queue: 'bulk',
            estimatedSeconds: 90,
        );
    }
}
```

### 3. Adicione a rota em `routes/api.php`

```php
Route::get('/meu-modulo/relatorio/async', [MeuController::class, 'gerarRelatorioAsync'])
    ->name('api.v1.meu-modulo.relatorio.async');
```

### 4. Cliente faz polling

```text
GET  /api/v1/meu-modulo/relatorio/async?filtro=x
  -> 202 { trace_id, type, status:'accepted', ... }

GET  /api/v1/traces/{trace_id}
  -> 200 { status:'pending'|'processing'|'completed'|'failed', ... }
  // quando completed, vem download_url no payload

GET  /api/v1/traces/{trace_id}/download
  -> 200 binary (artefato) ou 404/409 se ainda não pronto
```

## Boas práticas

- **Sempre use `queue: 'bulk'`** (ou `low`) para exports/relatórios — não
  congestiona a queue `default` usada por jobs críticos.
- **Set `timeout` alto** no job (>5min) — é trabalho pesado por definição.
- **Limit `tries` a 2** — retry de export longo desperdiça recursos.
- **Filtros como array, não Request** — Request não serializa bem em jobs;
  reconstrua via `Request::create('/', 'GET', $filters)` se o service exigir
  `Request`.
- **Path inclui timestamp + trace_id** — evita colisão entre exports concorrentes.
- **Cliente deve ter timeout de polling** — não polar pra sempre; respeitar
  `estimated_processing` retornado no 202.

## Auditoria de candidatos no projeto

Endpoints atualmente síncronos heavy/expensive (sem AsynchronousResponse)
identificados na auditoria de 2026-05-22:

- `DecretacoesApiController::exportPowerBI` — versão async já criada (this PR).
- `LogViewerController` — exports de log via streamDownload.
- `EstoqueController::exportExcel` — stub, refatorar quando módulo
  for implementado.
- `PaeFormularioController` — verificar volumes em produção.
- Vários módulos com `Excel::download`/`response()->streamDownload` —
  varredura completa em PR separado.

## Follow-ups não implementados nesta entrega

- **Pint/PHPStan rule** que detecta rotas classificadas como `heavy` ou
  `expensive` (via `ApiRateLimiter::getRouteCost`) sem uso do trait
  `AsynchronousResponse`. Exige rule customizada PHPStan — fora do
  escopo desta foundation.
- **Job de retenção** que limpa `storage/app/exports/*` mais antigos que
  N dias. Implementar quando volume de exports crescer.
- **Notificação ao usuário** quando trace finaliza (websocket/email) —
  hoje o cliente faz polling.
