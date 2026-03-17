# Design: API Bridge Swagger — Módulo Decretações

**Data:** 2026-03-17
**Status:** Aprovado pelo usuário
**Projeto:** NewSDC — Sistema Integrado de Defesa Civil

---

## Objetivo

Expor os dados do módulo de Decretações via API REST documentada com Swagger (l5-swagger), com suporte a autenticação dupla (Sanctum + token Power BI dedicado), rate limiting `pro` via `ApiRateLimiter` existente, e acesso rápido ao Swagger UI pela sidebar.

---

## Arquitetura

### Novo Controller

```
app/Http/Controllers/Api/V1/Decretacoes/DecretacoesApiController.php
```

Segue o padrão `Api/V1/` já adotado para RAT, PAE e BI. Delega para services existentes. Não altera o `DecretacoesController` web.

### Endpoints

| Método | Rota | Nome | Descrição |
|--------|------|------|-----------|
| GET | `/api/v1/decretacoes` | `api.v1.decretacoes.index` | Lista paginada com filtros |
| GET | `/api/v1/decretacoes/export/power-bi` | `api.v1.decretacoes.export.power-bi` | Dados normalizados para BI |
| POST | `/api/v1/decretacoes/receive` | `api.v1.decretacoes.receive` | Recebe dados externos (FIDE, Hexagon) |
| GET | `/api/v1/decretacoes/{id}` | `api.v1.decretacoes.show` | Detalhe de um processo |

**Nota de ordenamento:** rotas estáticas (`/export/power-bi`, `/receive`) ANTES da rota dinâmica `/{id}` para evitar shadowing.

### Middleware Stack

O grupo `v1/decretacoes` deve ser definido **fora** do grupo pai `auth:sanctum` (ou usar `->withoutMiddleware('auth:sanctum')`), pois `DecretacoesApiAuth` é o único portão de autenticação e precisa interceptar tanto Sanctum quanto o token Power BI antes que o guard padrão rejeite a request.

```
decretacoes.api.auth  (novo middleware — autenticação dupla)
api-rate-limiter:pro  (alias do ApiRateLimiter existente — 1.000 créditos/min)
```

Custo por endpoint (resolvido pelo `ApiRateLimiter` existente via path pattern):
- GET export/power-bi → `heavy` (custo 10 — path contém "export")
- GET lista → `light` (custo 0.5 — GET com "list" pattern)
- GET detalhe → `normal` (custo 1)
- POST receive → `normal` (custo 1)

---

## Autenticação Dupla

Middleware `DecretacoesApiAuth` (novo, lightweight):

1. Verifica `Authorization: Bearer <sanctum_token>` — autentica via `Auth::guard('sanctum')`
2. Se falhar, verifica `X-PowerBI-Token: <token>` — valida via `IntegrationTokenService::validatePowerBIToken(string $token): ?array` (retorna array de tokens se válido, `null` se inválido/expirado — validação por Cache lookup)
3. Se nenhum válido → `401 Unauthorized`

**Injeção de dependência:** `DecretacoesApiAuth` injeta `App\Services\IntegrationTokenService` no construtor.

O middleware é registrado em `app/Http/Kernel.php` como alias `decretacoes.api.auth`.

**Conflito resolvido:** o grupo `v1/decretacoes` NÃO herda `auth:sanctum` do grupo pai. Usa apenas `decretacoes.api.auth` como portão, garantindo que o fallback para `X-PowerBI-Token` funcione.

---

## Delegação para Services Existentes

| Endpoint | Service | Método |
|----------|---------|--------|
| GET index | `ProcessoQueryService` | `listForApi()` ou `listForApiCompact()` |
| GET show | `ProcessoQueryService` | `showForApi(int $id)` |
| GET export/power-bi | `ProcessoExportService` | `getNormalizedDataForPowerBI()` |
| POST receive | `EntradaProcessoService` | `createProcesso()` via `ProcessoRequestDTO` |

**Nota:** `listForApi()` e `showForApi()` são os métodos específicos para API, já passam por `ProcessoResource` e retornam payloads sem dados de UI web. `getNormalizedDataForPowerBI()` está em `ProcessoExportService`, não em `ProcessoQueryService`.

---

## FormRequest para POST /receive

Criar `app/Modules/Decretacoes/Requests/ReceiveProcessoRequest.php` (novo), baseado em `StoreProcessoRequest` existente, com regras de validação adaptadas para recebimento de dados externos (FIDE/Hexagon). Não reutilizar `StoreProcessoRequest` diretamente, pois o contexto de origem externa pode ter campos e regras diferentes.

---

## Swagger Annotations

Anotações `@OA` no `DecretacoesApiController.php` com:
- Tag `"Decretacoes"` (nova tag adicionada ao `SwaggerController.php`)
- Security `bearerAuth` (já definido)
- Novos schemas em `Schemas.php` (arquivo bare sem namespace/class — manter este padrão)

Novos schemas:
- `ProcessoDecretacaoItem` — item individual
- `ProcessoDecretacaoList` — resposta paginada (referencia `PaginationMeta` existente)
- `ReceiveProcessoRequest` — body do POST /receive
- `DecretacaoPowerBIExport` — resposta do export normalizado

**Nota:** `Schemas.php` é um arquivo de anotações PHPDoc puras, sem `namespace` ou `class`. Ao adicionar novos schemas, NÃO adicionar namespace — manter o padrão atual.

---

## Sidebar

Adicionar ao final do `Sidebar.vue`, após a seção "ADMINISTRACAO":

```vue
<!-- API DOCS -->
<div class="nav-section">
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

- Ícone: `code` (verificar disponibilidade no sistema de ícones do `NavItem`)
- Link abre o Swagger UI do l5-swagger (rota `/api/documentation` já configurada)
- Sem `v-if` de permissão — visível para todos os usuários autenticados

---

## Rotas em `api.php`

Adicionar **fora** do grupo `prefix('v1')->middleware('auth:sanctum')` existente, como grupo independente:

```php
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

---

## Arquivos a Criar

1. `app/Http/Controllers/Api/V1/Decretacoes/DecretacoesApiController.php`
2. `app/Http/Middleware/DecretacoesApiAuth.php`
3. `app/Modules/Decretacoes/Requests/ReceiveProcessoRequest.php`

## Arquivos a Modificar

1. `routes/api.php` — adicionar grupo `v1/decretacoes` fora do grupo `auth:sanctum`
2. `app/Http/Controllers/Api/Schemas.php` — adicionar 4 novos schemas (sem namespace)
3. `app/Http/Controllers/Api/SwaggerController.php` — adicionar tag `"Decretacoes"`
4. `resources/js/Components/Sidebar.vue` — adicionar item API Docs ao final
5. `app/Http/Kernel.php` — registrar aliases:
   - `'api-rate-limiter' => \App\Http\Middleware\ApiRateLimiter::class`
   - `'decretacoes.api.auth' => \App\Http\Middleware\DecretacoesApiAuth::class`

---

## Fora do Escopo

- Alterar o `DecretacoesController` web
- Criar página Vue para o Swagger (usa o Swagger UI do l5-swagger)
- Migrations ou alterações no banco
- Autenticação OAuth2 (fora do padrão atual do projeto)
- Refatorar services existentes
