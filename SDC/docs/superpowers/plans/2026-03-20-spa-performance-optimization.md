# SPA Performance Optimization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reduzir lentidao generalizada da SPA Laravel+Vue3 com banco legacy remoto e 500+ usuarios simultaneos.

**Architecture:** Cache-first com Redis (ja configurado no .env), cache inteligente por perfil de modulo, SSR seletivo para paginas publicas, hidratacao otimizada com lazy loading, e prefetch preditivo.

**Tech Stack:** Laravel 12, Octane (RoadRunner), Vue 3, Inertia.js, Redis, Vite, TanStack Query

**Spec:** `SDC/docs/superpowers/specs/2026-03-20-spa-performance-optimization-design.md`

---

### Task 1: Verificar Redis ativo e corrigir Octane listeners

**Files:**
- Modify: `SDC/config/octane.php:105-110`
- Read: `SDC/.env` (CACHE_DRIVER=redis, SESSION_DRIVER=redis ja configurados)

- [ ] **Step 1: Verificar que Redis esta respondendo**

Run: `cd SDC && php artisan tinker --execute="echo Cache::store('redis')->put('test', 'ok', 60) ? 'REDIS OK' : 'REDIS FAIL';"`
Expected: `REDIS OK`

- [ ] **Step 2: Descomentar CollectGarbage e DisconnectFromDatabases no Octane**

Em `SDC/config/octane.php` linhas 108-109, descomentar:

```php
OperationTerminated::class => [
    FlushOnce::class,
    FlushTemporaryContainerInstances::class,
    DisconnectFromDatabases::class,
    CollectGarbage::class,
],
```

- [ ] **Step 3: Verificar imports no topo do arquivo**

Confirmar que `DisconnectFromDatabases` e `CollectGarbage` estao importados. Se nao, adicionar:

```php
use Laravel\Octane\Listeners\DisconnectFromDatabases;
use Laravel\Octane\Listeners\CollectGarbage;
```

- [ ] **Step 4: Commit**

```bash
git add SDC/config/octane.php
git commit -m "perf: enable CollectGarbage and DisconnectFromDatabases in Octane"
```

---

### Task 2: CacheProfileService

**Files:**
- Create: `SDC/app/Services/Cache/CacheProfileService.php`
- Modify: `SDC/app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Criar CacheProfileService**

Criar `SDC/app/Services/Cache/CacheProfileService.php`:

```php
<?php

namespace App\Services\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheProfileService
{
    private array $profiles = [
        'aggressive' => ['ttl' => 1800, 'warmup' => true],
        'moderate'   => ['ttl' => 300,  'warmup' => false],
        'realtime'   => ['ttl' => 60,   'warmup' => false],
    ];

    private array $moduleProfiles = [
        'decretacoes'       => 'aggressive',
        'compdec'           => 'aggressive',
        'treinamento'       => 'aggressive',
        'inmet'             => 'aggressive',
        'ajuda_humanitaria' => 'moderate',
        'demandas'          => 'moderate',
        'plantao'           => 'moderate',
        'rat'               => 'realtime',
        'suporte'           => 'realtime',
    ];

    public function getProfile(string $module): array
    {
        $profileName = $this->moduleProfiles[$module] ?? 'moderate';
        return $this->profiles[$profileName];
    }

    public function remember(string $module, string $key, Closure $callback): mixed
    {
        $profile = $this->getProfile($module);
        $cacheKey = "{$module}:{$key}";

        return Cache::tags([$module])->remember($cacheKey, $profile['ttl'], $callback);
    }

    public function invalidate(string $module, ?string $key = null): void
    {
        if ($key) {
            Cache::tags([$module])->forget("{$module}:{$key}");
        } else {
            Cache::tags([$module])->flush();
        }
    }

    public function invalidateWithLock(string $module, string $key): void
    {
        $lockKey = "cache_refresh:{$module}:{$key}";

        Cache::lock($lockKey, 10)->get(function () use ($module, $key) {
            $this->invalidate($module, $key);
        });
    }

    public function isWarmupEnabled(string $module): bool
    {
        return $this->getProfile($module)['warmup'] ?? false;
    }

    public function getModulesByProfile(string $profileName): array
    {
        return array_keys(
            array_filter($this->moduleProfiles, fn($p) => $p === $profileName)
        );
    }
}
```

- [ ] **Step 2: Registrar como singleton no AppServiceProvider**

Em `SDC/app/Providers/AppServiceProvider.php`, no metodo `register()`, adicionar:

```php
$this->app->singleton(\App\Services\Cache\CacheProfileService::class);
```

- [ ] **Step 3: Testar no tinker**

Run: `cd SDC && php artisan tinker --execute="app(\App\Services\Cache\CacheProfileService::class)->remember('decretacoes', 'test', fn() => 'cached'); echo app(\App\Services\Cache\CacheProfileService::class)->remember('decretacoes', 'test', fn() => 'not-cached');"`
Expected: `cached`

- [ ] **Step 4: Commit**

```bash
git add SDC/app/Services/Cache/CacheProfileService.php SDC/app/Providers/AppServiceProvider.php
git commit -m "feat: add CacheProfileService with module-based cache profiles"
```

---

### Task 3: CacheInvalidationObserver

**Files:**
- Create: `SDC/app/Observers/CacheInvalidationObserver.php`
- Modify: `SDC/app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Criar o Observer**

Criar `SDC/app/Observers/CacheInvalidationObserver.php`:

```php
<?php

namespace App\Observers;

use App\Services\Cache\CacheProfileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CacheInvalidationObserver
{
    public function __construct(
        private CacheProfileService $cacheService
    ) {}

    public function saved(Model $model): void
    {
        $module = $this->resolveModule($model);
        if (!$module) {
            return;
        }

        // Flush completo do modulo via tags (garante que todas as chaves list:<hash> sejam invalidadas)
        $this->cacheService->invalidate($module);
    }

    public function deleted(Model $model): void
    {
        $module = $this->resolveModule($model);
        if (!$module) {
            return;
        }

        $this->cacheService->invalidate($module);
    }

    private function resolveModule(Model $model): ?string
    {
        $namespace = get_class($model);

        $moduleMap = [
            'Decretacoes'       => 'decretacoes',
            'AjudaHumanitaria'  => 'ajuda_humanitaria',
            'Compdec'           => 'compdec',
            'Treinamento'       => 'treinamento',
            'Inmet'             => 'inmet',
            'Demandas'          => 'demandas',
            'Plantao'           => 'plantao',
            'Rat'               => 'rat',
            'Suporte'           => 'suporte',
        ];

        foreach ($moduleMap as $nsFragment => $moduleName) {
            if (str_contains($namespace, "Modules\\{$nsFragment}\\")) {
                return $moduleName;
            }
        }

        return null;
    }
}
```

- [ ] **Step 2: Registrar o Observer no AppServiceProvider**

Em `SDC/app/Providers/AppServiceProvider.php`, no metodo `boot()`, adicionar registro dos Models principais.

> **Nota:** O model `Processo` esta definido dentro de `SDC/app/Modules/Decretacoes/Models/DecretacoesModels.php` (arquivo multi-class). Verificar que o autoloading resolve `App\Modules\Decretacoes\Models\Processo` corretamente antes de registrar.

```php
// Cache invalidation observers
$cacheObserver = \App\Observers\CacheInvalidationObserver::class;
\App\Modules\Decretacoes\Models\Processo::observe($cacheObserver);
// Adicionar outros Models conforme necessario
```

- [ ] **Step 3: Testar o Observer**

Run: `cd SDC && php artisan tinker --execute="use App\Services\Cache\CacheProfileService; app(CacheProfileService::class)->remember('decretacoes', 'observer-test', fn() => 'before'); echo 'Cache set: ' . app(CacheProfileService::class)->remember('decretacoes', 'observer-test', fn() => 'x'); app(CacheProfileService::class)->invalidate('decretacoes'); echo ' | After flush: ' . (app(CacheProfileService::class)->remember('decretacoes', 'observer-test', fn() => 'after'));"`
Expected: `Cache set: before | After flush: after`

- [ ] **Step 4: Commit**

```bash
git add SDC/app/Observers/CacheInvalidationObserver.php SDC/app/Providers/AppServiceProvider.php
git commit -m "feat: add CacheInvalidationObserver with stampede protection"
```

---

### Task 4: Integrar cache no ProcessoQueryService

**Files:**
- Modify: `SDC/app/Modules/Decretacoes/Services/ProcessoQueryService.php`

- [ ] **Step 1: Adicionar cache ao metodo list()**

No `ProcessoQueryService.php`, injetar `CacheProfileService` no construtor e envolver a query do `list()` com cache.

> **Importante:** NAO cachear o `LengthAwarePaginator` diretamente (contem closures e estado do request que nao serializam bem, especialmente sob Octane). Cachear os dados brutos e reconstruir o paginator.

```php
use App\Services\Cache\CacheProfileService;
use Illuminate\Pagination\LengthAwarePaginator;

// No construtor:
public function __construct(
    private CacheProfileService $cacheService
) {}

// No metodo list(), cachear dados brutos e reconstruir paginator:
public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
{
    $page = (int) request()->get('page', 1);
    $cacheKey = 'list:' . md5(serialize($filters) . $perPage . $page);

    $cached = $this->cacheService->remember('decretacoes', $cacheKey, function () use ($filters, $perPage, $page) {
        // Executar a query existente aqui e capturar resultado
        $paginator = /* corpo original da query */;

        return [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
        ];
    });

    return new LengthAwarePaginator(
        $cached['items'],
        $cached['total'],
        $perPage,
        $page,
        ['path' => request()->url(), 'query' => request()->query()]
    );
}
```

- [ ] **Step 2: Testar que a listagem ainda funciona**

Run: `cd SDC && php artisan tinker --execute="app(\App\Modules\Decretacoes\Services\ProcessoQueryService::class)->list([], 5);"`
Expected: Retorna LengthAwarePaginator sem erros

- [ ] **Step 3: Commit**

```bash
git add SDC/app/Modules/Decretacoes/Services/ProcessoQueryService.php
git commit -m "perf: add Redis cache to ProcessoQueryService list method"
```

---

### Task 5: Cache warm-up scheduled

**Files:**
- Modify: `SDC/app/Console/Kernel.php:14-24`

- [ ] **Step 1: Adicionar warm-up ao Kernel schedule**

Em `SDC/app/Console/Kernel.php`, no metodo `schedule()`, adicionar:

```php
$schedule->call(function () {
    try {
        $queryService = app(\App\Modules\Decretacoes\Services\ProcessoQueryService::class);
        $queryService->list([], 15);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::channel('critical')->error('Cache warmup failed', [
            'error' => $e->getMessage(),
        ]);
    }
})->everyFifteenMinutes()->name('cache-warmup-decretacoes');
```

- [ ] **Step 2: Verificar que o schedule esta registrado**

Run: `cd SDC && php artisan schedule:list`
Expected: Lista inclui `cache-warmup-decretacoes` a cada 15 minutos

- [ ] **Step 3: Commit**

```bash
git add SDC/app/Console/Kernel.php
git commit -m "perf: add scheduled cache warmup for Decretacoes"
```

---

### Task 6: Middleware DisableSsrForAuthenticated

**Files:**
- Create: `SDC/app/Http/Middleware/DisableSsrForAuthenticated.php`
- Modify: `SDC/app/Http/Kernel.php` (adicionar ao `$middlewareGroups['web']`)

- [ ] **Step 1: Criar o middleware**

Criar `SDC/app/Http/Middleware/DisableSsrForAuthenticated.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableSsrForAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            config(['inertia.ssr.enabled' => false]);
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Registrar o middleware no Kernel**

Em `SDC/app/Http/Kernel.php`, adicionar `\App\Http\Middleware\DisableSsrForAuthenticated::class` ao array `$middlewareGroups['web']`, apos o middleware de autenticacao.

- [ ] **Step 3: Verificar que paginas autenticadas nao passam pelo SSR**

Run: `cd SDC && php artisan tinker --execute="Auth::loginUsingId(1); echo config('inertia.ssr.enabled') ? 'SSR ON' : 'SSR OFF';"`

Alternativamente, acessar no browser uma pagina autenticada e verificar nos logs/headers que o SSR nao foi invocado. Acessar `/login` e verificar que SSR ainda funciona.

- [ ] **Step 4: Commit**

```bash
git add SDC/app/Http/Middleware/DisableSsrForAuthenticated.php SDC/app/Http/Kernel.php
git commit -m "perf: disable SSR for authenticated pages, keep for public/SEO"
```

---

### Task 7: Skeleton components para lazy loading

**Files:**
- Create: `SDC/resources/js/Components/Atoms/Skeleton/SkeletonModal.vue`
- Create: `SDC/resources/js/Components/Atoms/Skeleton/SkeletonTable.vue`
- Create: `SDC/resources/js/Components/Atoms/Skeleton/SkeletonChart.vue`
- Modify: `SDC/resources/js/Components/Atoms/Skeleton/index.js`

- [ ] **Step 1: Criar SkeletonModal.vue**

Criar `SDC/resources/js/Components/Atoms/Skeleton/SkeletonModal.vue`:

```vue
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
      <div class="mb-4 h-6 w-48 animate-pulse rounded bg-gray-200"></div>
      <div class="space-y-3">
        <div class="h-4 w-full animate-pulse rounded bg-gray-200"></div>
        <div class="h-4 w-3/4 animate-pulse rounded bg-gray-200"></div>
        <div class="h-4 w-5/6 animate-pulse rounded bg-gray-200"></div>
      </div>
      <div class="mt-6 flex gap-2">
        <div class="h-8 w-20 animate-pulse rounded bg-gray-200"></div>
        <div class="h-8 w-20 animate-pulse rounded bg-gray-200"></div>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Criar SkeletonTable.vue**

Criar `SDC/resources/js/Components/Atoms/Skeleton/SkeletonTable.vue`:

```vue
<template>
  <div class="w-full space-y-2">
    <div class="flex gap-4 border-b pb-2">
      <div v-for="i in 5" :key="i" class="h-4 flex-1 animate-pulse rounded bg-gray-200"></div>
    </div>
    <div v-for="row in 8" :key="row" class="flex gap-4 py-2">
      <div v-for="col in 5" :key="col" class="h-4 flex-1 animate-pulse rounded bg-gray-200"></div>
    </div>
  </div>
</template>
```

- [ ] **Step 3: Criar SkeletonChart.vue**

Criar `SDC/resources/js/Components/Atoms/Skeleton/SkeletonChart.vue`:

```vue
<template>
  <div class="flex h-64 w-full items-end gap-2 rounded-lg border p-4">
    <div v-for="(h, i) in barHeights" :key="i"
      class="flex-1 animate-pulse rounded-t bg-gray-200"
      :style="{ height: h }"
    ></div>
  </div>
</template>

<script setup>
const barHeights = ['45%', '70%', '30%', '85%', '55%', '40%', '75%', '60%']
</script>
```

- [ ] **Step 4: Atualizar index.js com os novos exports**

Em `SDC/resources/js/Components/Atoms/Skeleton/index.js`, adicionar:

```js
export { default as SkeletonModal } from './SkeletonModal.vue'
export { default as SkeletonTable } from './SkeletonTable.vue'
export { default as SkeletonChart } from './SkeletonChart.vue'
```

- [ ] **Step 5: Commit**

```bash
git add SDC/resources/js/Components/Atoms/Skeleton/
git commit -m "feat: add SkeletonModal, SkeletonTable, SkeletonChart components"
```

---

### Task 8: defineAsyncComponent nos componentes pesados

**Files:**
- Modify: `SDC/resources/js/Components/Organisms/Decretacoes/ProcessoGrid.vue:1-10`
- Modify: `SDC/resources/js/Layouts/AuthenticatedLayout.vue` (se importa componentes pesados)

- [ ] **Step 1: Lazy load DecretacaoDetailModal no ProcessoGrid**

Em `SDC/resources/js/Components/Organisms/Decretacoes/ProcessoGrid.vue`, substituir o import direto do modal:

```js
// Antes:
import DecretacaoDetailModal from './Details/DecretacaoDetailModal.vue'

// Depois:
import { defineAsyncComponent } from 'vue'
import { SkeletonModal } from '@/Components/Atoms/Skeleton'

const DecretacaoDetailModal = defineAsyncComponent({
  loader: () => import('./Details/DecretacaoDetailModal.vue'),
  loadingComponent: SkeletonModal,
  delay: 100,
})
```

- [ ] **Step 2: Lazy load EditChoiceModal no ProcessoGrid (se importado diretamente)**

Mesmo pattern para `EditChoiceModal` se for um import direto:

```js
const EditChoiceModal = defineAsyncComponent({
  loader: () => import('./EditChoiceModal.vue'),
  delay: 100,
})
```

- [ ] **Step 3: Verificar que o modal ainda abre corretamente**

Testar no browser: abrir a pagina de Decretacoes, clicar em um processo, verificar que o modal carrega com skeleton e depois mostra o conteudo.

- [ ] **Step 4: Commit**

```bash
git add SDC/resources/js/Components/Organisms/Decretacoes/ProcessoGrid.vue
git commit -m "perf: lazy load DecretacaoDetailModal and EditChoiceModal"
```

> **Follow-up:** Aplicar o mesmo pattern de `defineAsyncComponent` nos demais alvos do spec (Charts/Chart.js, Maps/Leaflet, AIAssistantModal, componentes Admin). Esses podem ser feitos em tasks separadas apos validar que o pattern funciona no ProcessoGrid.

---

### Task 9: useSmartPrefetch composable

**Files:**
- Create: `SDC/resources/js/composables/core/useSmartPrefetch.js`
- Modify: `SDC/resources/js/app.js:52-78` (remover setupPrefetching)
- Modify: `SDC/resources/js/Layouts/AuthenticatedLayout.vue`

- [ ] **Step 1: Criar o composable**

Criar `SDC/resources/js/composables/core/useSmartPrefetch.js`:

```js
import { onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const navigationMap = {
  'Dashboard':            ['/decretacoes', '/demandas'],
  'Decretacoes/Index':    ['/ajuda-humanitaria'],
  'Admin/Users/Index':    ['/admin/roles', '/admin/permissions'],
  'AjudaHumanitaria/Index': ['/decretacoes'],
  'Demandas/Index':       ['/decretacoes'],
}

export function useSmartPrefetch() {
  const page = usePage()

  onMounted(() => {
    const currentPage = page.component
    const probableRoutes = navigationMap[currentPage] || []

    probableRoutes.slice(0, 2).forEach(route => {
      router.prefetch(route, { cacheFor: 30000 })
    })

    if ('requestIdleCallback' in window) {
      requestIdleCallback(() => {
        probableRoutes.slice(2).forEach(route => {
          router.prefetch(route, { cacheFor: 60000 })
        })
      }, { timeout: 3000 })
    }
  })
}
```

- [ ] **Step 2: Remover setupPrefetching do app.js**

Em `SDC/resources/js/app.js`, remover a funcao `setupPrefetching()` (linhas 52-78) e sua invocacao. O prefetch agora e feito pelo composable no layout.

- [ ] **Step 3: Integrar no AuthenticatedLayout**

Em `SDC/resources/js/Layouts/AuthenticatedLayout.vue`, no bloco `<script setup>`, adicionar:

```js
import { useSmartPrefetch } from '@/composables/core/useSmartPrefetch'

useSmartPrefetch()
```

- [ ] **Step 4: Testar navegacao**

No browser, abrir o Dashboard e verificar no DevTools (Network tab) que as rotas provaveis sao prefetchadas automaticamente.

- [ ] **Step 5: Commit**

```bash
git add SDC/resources/js/composables/core/useSmartPrefetch.js SDC/resources/js/app.js SDC/resources/js/Layouts/AuthenticatedLayout.vue
git commit -m "perf: replace setupPrefetching with useSmartPrefetch composable"
```

---

### Task 10: Verificacao final e metricas

**Files:**
- Read-only verification

- [ ] **Step 1: Verificar Redis cache esta funcionando**

Run: `cd SDC && php artisan tinker --execute="echo Cache::store('redis')->get('decretacoes:list:' . md5('')) ? 'HIT' : 'MISS';"`

- [ ] **Step 2: Verificar Octane config**

Run: `cd SDC && php artisan config:show octane | grep -A5 OperationTerminated`
Expected: `DisconnectFromDatabases` e `CollectGarbage` listados (nao comentados)

- [ ] **Step 3: Verificar SSR seletivo**

Acessar `/login` -> verificar SSR ativo (source HTML renderizado server-side)
Acessar `/dashboard` autenticado -> verificar SSR desativado (SPA puro)

- [ ] **Step 4: Medir bundle size**

Run: `cd SDC && npx vite build --mode production 2>&1 | tail -20`
Verificar que o bundle inicial esta menor que antes (componentes pesados em chunks separados)

- [ ] **Step 5: Commit final com config:cache**

```bash
cd SDC && php artisan optimize
git add -A
git commit -m "perf: SPA performance optimization - cache-first + selective SSR + lazy loading"
```
