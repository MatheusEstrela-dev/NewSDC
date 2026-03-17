# Swagger API Bridge — Decretacoes Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expor o módulo de Decretações via API REST documentada com Swagger, autenticação dupla (Sanctum + Power BI token), rate limiting `pro`, e link no sidebar.

**Architecture:** Novo `DecretacoesApiController` no padrão `Api/V1/`, delegando para `ProcessoQueryService` e `EntradaProcessoService` já existentes. Middleware `DecretacoesApiAuth` intercepta antes do guard Sanctum para suportar `X-PowerBI-Token`. Rotas independentes em `api.php` fora do grupo `auth:sanctum`.

**Tech Stack:** Laravel 10, PHP 8.2, l5-swagger (darkaonline/l5-swagger), Laravel Sanctum, Redis (ApiRateLimiter), Vue 3 (Sidebar.vue)

---

## Chunk 1: Infraestrutura — Kernel + Middleware de Auth

### Task 1: Registrar aliases no Kernel.php

**Files:**
- Modify: `app/Http/Kernel.php:84`

- [ ] Adicionar os dois aliases ao final de `$middlewareAliases`:

```php
'api-rate-limiter'      => \App\Http\Middleware\ApiRateLimiter::class,
'decretacoes.api.auth'  => \App\Http\Middleware\DecretacoesApiAuth::class,
```

- [ ] Commit:
```bash
git add app/Http/Kernel.php
git commit -m "feat(api): register api-rate-limiter and decretacoes.api.auth aliases"
```

---

### Task 2: Criar middleware DecretacoesApiAuth

**Files:**
- Create: `app/Http/Middleware/DecretacoesApiAuth.php`
- Test: `tests/Feature/Decretacoes/DecretacoesApiAuthTest.php`

- [ ] Criar o teste de feature primeiro:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Decretacoes;

use App\Models\User;
use App\Services\IntegrationTokenService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DecretacoesApiAuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_rejects_request_without_any_token(): void
    {
        $response = $this->getJson('/api/v1/decretacoes');
        $response->assertStatus(401);
    }

    public function test_accepts_request_with_valid_sanctum_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/decretacoes');

        $response->assertStatus(200);
    }

    public function test_accepts_request_with_valid_powerbi_token(): void
    {
        $powerBiToken = 'test-powerbi-token-abc123';
        Cache::put('powerbi_token_' . $powerBiToken, ['decretacoes' => 'token_value'], 3600);

        $response = $this->withHeader('X-PowerBI-Token', $powerBiToken)
            ->getJson('/api/v1/decretacoes');

        $response->assertStatus(200);
    }

    public function test_rejects_invalid_powerbi_token(): void
    {
        $response = $this->withHeader('X-PowerBI-Token', 'invalid-token')
            ->getJson('/api/v1/decretacoes');

        $response->assertStatus(401);
    }
}
```

- [ ] Rodar o teste para confirmar que falha (routes e middleware ainda não existem):
```bash
cd SDC && php artisan test tests/Feature/Decretacoes/DecretacoesApiAuthTest.php
```
Esperado: FAIL (route not found / 404)

- [ ] Criar o middleware:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\IntegrationTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DecretacoesApiAuth
{
    public function __construct(
        private readonly IntegrationTokenService $tokenService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->authenticateViaSanctum($request)) {
            return $next($request);
        }

        if ($this->authenticateViaPowerBIToken($request)) {
            return $next($request);
        }

        return response()->json([
            'error'   => 'Unauthorized',
            'message' => 'Valid Bearer token or X-PowerBI-Token required.',
        ], 401);
    }

    private function authenticateViaSanctum(Request $request): bool
    {
        Auth::guard('sanctum')->setRequest($request);
        $user = Auth::guard('sanctum')->user();

        if ($user) {
            Auth::setUser($user);
            return true;
        }

        return false;
    }

    private function authenticateViaPowerBIToken(Request $request): bool
    {
        $token = $request->header('X-PowerBI-Token');

        if (!$token) {
            return false;
        }

        $tokenData = $this->tokenService->validatePowerBIToken($token);

        return $tokenData !== null;
    }
}
```

- [ ] Commit:
```bash
git add app/Http/Middleware/DecretacoesApiAuth.php tests/Feature/Decretacoes/DecretacoesApiAuthTest.php
git commit -m "feat(api): add DecretacoesApiAuth middleware with dual-auth support"
```

---

## Chunk 2: FormRequest + Controller API

### Task 3: Criar ReceiveProcessoRequest

**Files:**
- Create: `app/Modules/Decretacoes/Requests/ReceiveProcessoRequest.php`

- [ ] Criar o FormRequest:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_entrada'                      => 'required|date',
            'origem'                            => 'required|string|in:municipal,estadual',
            'municipio_id'                      => 'required|integer',
            'cobrade_id'                        => 'nullable|integer',
            'tipo_desastre_id'                  => 'nullable|integer',
            'situacao_anormalidade'             => 'nullable|string|in:N1,SE',
            'data_ocorrencia'                   => 'nullable|date',
            'analista_id'                       => 'nullable|string|max:255',
            'n_protocolo_fide'                  => 'nullable|string|max:50',
            'redec_id'                          => 'nullable|integer',
            'n_decreto_municipal'               => 'nullable|string|max:255',
            'data_decreto_municipal'            => 'nullable|date',
            'observacoes'                       => 'nullable|string',
        ];
    }
}
```

- [ ] Commit:
```bash
git add app/Modules/Decretacoes/Requests/ReceiveProcessoRequest.php
git commit -m "feat(api): add ReceiveProcessoRequest for external data intake"
```

---

### Task 4: Criar DecretacoesApiController

**Files:**
- Create: `app/Http/Controllers/Api/V1/Decretacoes/DecretacoesApiController.php`
- Test: `tests/Feature/Decretacoes/DecretacoesApiControllerTest.php`

- [ ] Criar o teste de feature:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Decretacoes;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DecretacoesApiControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsApiUser(): static
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-test')->plainTextToken;
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_index_returns_paginated_list(): void
    {
        $response = $this->actingAsApiUser()->getJson('/api/v1/decretacoes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data', 'meta'],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_processo(): void
    {
        $response = $this->actingAsApiUser()->getJson('/api/v1/decretacoes/999999');

        $response->assertStatus(404);
    }

    public function test_export_powerbi_returns_array(): void
    {
        $response = $this->actingAsApiUser()->getJson('/api/v1/decretacoes/export/power-bi');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_receive_validates_required_fields(): void
    {
        $response = $this->actingAsApiUser()->postJson('/api/v1/decretacoes/receive', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data_entrada', 'origem', 'municipio_id']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/decretacoes')->assertStatus(401);
    }
}
```

- [ ] Rodar para confirmar falha:
```bash
cd SDC && php artisan test tests/Feature/Decretacoes/DecretacoesApiControllerTest.php
```
Esperado: FAIL (routes not found)

- [ ] Criar o controller:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Decretacoes;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Modules\Decretacoes\DTO\ProcessoRequestDTO;
use App\Modules\Decretacoes\Requests\ReceiveProcessoRequest;
use App\Modules\Decretacoes\Services\EntradaProcessoService;
use App\Modules\Decretacoes\Services\ProcessoQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller para o modulo de Decretacoes.
 *
 * FLUXO: Request -> Controller -> Service -> JSON
 *
 * RESPONSABILIDADES:
 * - Expor dados de Processos via API REST
 * - Receber dados externos (FIDE, Hexagon) via POST
 * - Exportar dados normalizados para Power BI
 *
 * @OA\Tag(
 *     name="Decretacoes",
 *     description="Endpoints do modulo de Decretacoes — listagem, detalhe, export BI e recebimento externo"
 * )
 */
class DecretacoesApiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ProcessoQueryService $queryService,
        private readonly EntradaProcessoService $entradaService
    ) {
    }

    /**
     * Lista processos com filtros e paginacao.
     *
     * @OA\Get(
     *     path="/api/v1/decretacoes",
     *     summary="Lista processos de decretacoes",
     *     operationId="decretacoesIndex",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="reconhecimento", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de processos",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessoDecretacaoList")
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'data_entrada_inicio', 'data_entrada_fim',
            'processo', 'reconhecimento', 'analista', 'situacao_anormalidade',
            'data_decreto_inicio', 'data_decreto_fim', 'vigencia_status',
            'tipo_desastre_id', 'municipio_id', 'n_protocolo_fide',
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $data = $this->queryService->listForApi($filters, $perPage);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Retorna detalhes de um processo.
     *
     * @OA\Get(
     *     path="/api/v1/decretacoes/{id}",
     *     summary="Detalhe de um processo de decretacao",
     *     operationId="decretacoesShow",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Dados completos do processo",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessoDecretacaoItem")
     *     ),
     *     @OA\Response(response=404, description="Processo nao encontrado"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $data = $this->queryService->showForApi($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Processo nao encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Exporta dados normalizados para Power BI.
     *
     * @OA\Get(
     *     path="/api/v1/decretacoes/export/power-bi",
     *     summary="Exporta dados normalizados para Power BI",
     *     operationId="decretacoesExportPowerBI",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="include_deleted", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(
     *         response=200,
     *         description="Dados normalizados para BI",
     *         @OA\JsonContent(ref="#/components/schemas/DecretacaoPowerBIExport")
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function exportPowerBI(Request $request): JsonResponse
    {
        $data = $this->entradaService->getNormalizedDataForPowerBI($request);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Recebe dados de sistemas externos (FIDE, Hexagon).
     *
     * @OA\Post(
     *     path="/api/v1/decretacoes/receive",
     *     summary="Recebe dados externos de decretacao",
     *     operationId="decretacoesReceive",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReceiveProcessoRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Processo criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessoDecretacaoItem")
     *     ),
     *     @OA\Response(response=422, description="Dados invalidos"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function receive(ReceiveProcessoRequest $request): JsonResponse
    {
        $dto = ProcessoRequestDTO::fromRequest($request);
        $processo = $this->entradaService->createProcesso($dto);

        $data = $this->queryService->showForApi($processo->id);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 201);
    }
}
```

- [ ] Commit:
```bash
git add app/Http/Controllers/Api/V1/Decretacoes/DecretacoesApiController.php tests/Feature/Decretacoes/DecretacoesApiControllerTest.php
git commit -m "feat(api): add DecretacoesApiController with OA annotations"
```

---

## Chunk 3: Rotas + Schemas Swagger + Sidebar

### Task 5: Registrar rotas em api.php

**Files:**
- Modify: `routes/api.php`

- [ ] Adicionar ao final do arquivo (antes do bloco `if (app()->environment...)`), fora do grupo `auth:sanctum`:

```php
// ============================================================================
// DECRETACOES API — Autenticacao dupla (Sanctum + Power BI token)
// ============================================================================
use App\Http\Controllers\Api\V1\Decretacoes\DecretacoesApiController;

Route::prefix('v1/decretacoes')
    ->name('api.v1.decretacoes.')
    ->middleware(['decretacoes.api.auth', 'api-rate-limiter:pro'])
    ->group(function () {
        Route::get('/',                [DecretacoesApiController::class, 'index'])->name('index');
        Route::get('/export/power-bi', [DecretacoesApiController::class, 'exportPowerBI'])->name('export.power-bi');
        Route::post('/receive',        [DecretacoesApiController::class, 'receive'])->name('receive');
        Route::get('/{id}',            [DecretacoesApiController::class, 'show'])->name('show');
    });
```

- [ ] Verificar que as rotas foram registradas:
```bash
cd SDC && php artisan route:list --path=v1/decretacoes
```
Esperado: 4 rotas listadas (GET index, GET export/power-bi, POST receive, GET {id})

- [ ] Rodar os testes:
```bash
cd SDC && php artisan test tests/Feature/Decretacoes/
```
Esperado: todos passando

- [ ] Commit:
```bash
git add routes/api.php
git commit -m "feat(api): register v1/decretacoes routes with dual-auth and rate limiting"
```

---

### Task 6: Adicionar tag Decretacoes e schemas ao Swagger

**Files:**
- Modify: `app/Http/Controllers/Api/SwaggerController.php`
- Modify: `app/Http/Controllers/Api/Schemas.php`

- [ ] Adicionar a tag `"Decretacoes"` no `SwaggerController.php`, após a tag `"High Performance"`:

```php
 * @OA\Tag(
 *     name="Decretacoes",
 *     description="Endpoints do modulo de Decretacoes — processos, export Power BI e recebimento externo"
 * )
```

- [ ] Adicionar os 4 schemas ao final de `Schemas.php` (dentro do bloco de comentários PHPDoc existente, SEM namespace):

```php
 * @OA\Schema(
 *     schema="ProcessoDecretacaoItem",
 *     type="object",
 *     title="Processo de Decretacao",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="municipio_id", type="integer", example=123),
 *     @OA\Property(property="processo", type="string", example="MUNICIPAL"),
 *     @OA\Property(property="reconhecimento", type="string", example="SE"),
 *     @OA\Property(property="tipo_desastre_id", type="integer", example=5),
 *     @OA\Property(property="situacao_anormalidade", type="string", example="SE"),
 *     @OA\Property(property="data_entrada", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="n_protocolo_fide", type="string", nullable=true, example="2025.001.001"),
 *     @OA\Property(property="analista", type="string", nullable=true, example="joao.silva"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ProcessoDecretacaoList",
 *     type="object",
 *     title="Lista Paginada de Processos",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/ProcessoDecretacaoItem")
 *         ),
 *         @OA\Property(
 *             property="meta",
 *             type="object",
 *             @OA\Property(property="current_page", type="integer", example=1),
 *             @OA\Property(property="last_page", type="integer", example=5),
 *             @OA\Property(property="per_page", type="integer", example=15),
 *             @OA\Property(property="total", type="integer", example=75)
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ReceiveProcessoRequest",
 *     type="object",
 *     title="Request de Recepcao de Processo Externo",
 *     required={"data_entrada", "origem", "municipio_id"},
 *     @OA\Property(property="data_entrada", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="origem", type="string", enum={"municipal", "estadual"}, example="municipal"),
 *     @OA\Property(property="municipio_id", type="integer", example=123),
 *     @OA\Property(property="cobrade_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="situacao_anormalidade", type="string", nullable=true, enum={"N1", "SE"}, example="SE"),
 *     @OA\Property(property="n_protocolo_fide", type="string", nullable=true, example="2025.001.001"),
 *     @OA\Property(property="observacoes", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="DecretacaoPowerBIExport",
 *     type="object",
 *     title="Export Power BI — Decretacoes",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="municipio_id", type="integer"),
 *             @OA\Property(property="municipio_nome", type="string"),
 *             @OA\Property(property="tipo_decreto", type="string"),
 *             @OA\Property(property="data_entrada", type="string", format="date"),
 *             @OA\Property(property="obitos", type="integer"),
 *             @OA\Property(property="feridos", type="integer"),
 *             @OA\Property(property="desabrigados", type="integer"),
 *             @OA\Property(property="desalojados", type="integer")
 *         )
 *     )
 * )
```

- [ ] Regenerar o Swagger:
```bash
cd SDC && php artisan l5-swagger:generate
```
Esperado: sem erros, arquivo `storage/api-docs/api-docs.json` atualizado

- [ ] Commit:
```bash
git add app/Http/Controllers/Api/SwaggerController.php app/Http/Controllers/Api/Schemas.php
git commit -m "feat(swagger): add Decretacoes tag and schemas"
```

---

### Task 7: Adicionar link API Docs na Sidebar

**Files:**
- Modify: `resources/js/Components/Sidebar.vue`

- [ ] Verificar se o ícone `code` existe no `NavItem.vue`:
```bash
cd SDC && grep -n "code" resources/js/Components/NavItem.vue
```

- [ ] Se `code` não existir, adicionar o bloco `v-else-if` no `NavItem.vue` com o ícone code:

```html
<!-- Adicionar junto aos outros ícones existentes -->
<svg v-else-if="icon === 'code'" class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
</svg>
```

- [ ] Adicionar ao final do `Sidebar.vue`, após o bloco `<!-- ADMINISTRACAO -->` e antes do fechamento `</nav>`:

```html
<!-- INTEGRAÇÕES / API -->
<div class="nav-section">
  <div v-show="!isCollapsed" class="nav-section-title">INTEGRACOES</div>
  <NavItem
    href="/api/documentation"
    :active="false"
    icon="code"
    :collapsed="isCollapsed"
  >
    API Docs
  </NavItem>
</div>
```

**Nota:** O `href` é um link externo — o `NavItem` usa `<a>` tag nativa, então não precisa de `route()`. Confirmar que `NavItem` aceita `href` como string simples (já confirmado no código existente para rotas).

- [ ] Commit:
```bash
git add resources/js/Components/Sidebar.vue resources/js/Components/NavItem.vue
git commit -m "feat(sidebar): add API Docs link to integracoes section"
```

---

## Chunk 4: Verificação Final

### Task 8: Rodar todos os testes

- [ ] Rodar suite completa de Decretacoes:
```bash
cd SDC && php artisan test tests/Feature/Decretacoes/ --verbose
```
Esperado: todos PASS

- [ ] Rodar suite completa para garantir regressão zero:
```bash
cd SDC && php artisan test --verbose
```
Esperado: todos PASS

- [ ] Verificar Swagger UI acessível:
```bash
cd SDC && php artisan serve &
# Abrir http://localhost:8000/api/documentation
# Confirmar tag "Decretacoes" presente com 4 endpoints
```

- [ ] Commit final se todos passarem:
```bash
git add -A
git commit -m "feat(decretacoes): complete API bridge with Swagger, dual-auth, rate limiting"
```
