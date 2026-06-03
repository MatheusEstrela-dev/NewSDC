# SPA Performance Optimization Design

**Date:** 2026-03-20
**Status:** Approved
**Stack:** Laravel 12 + Octane (RoadRunner) + Inertia.js + Vue 3 SSR + Redis

## Problem

Lentidao generalizada na aplicacao SPA afetando todos os fluxos: carregamento inicial, navegacao entre paginas e operacoes especificas. 500+ usuarios simultaneos com banco legacy externo em datacenter diferente (latencia >20ms) consultado frequentemente.

## Diagnostico

| Gargalo | Impacto | Severidade |
|---------|---------|------------|
| Cache default em `file` ao inves de Redis | Cada request le do disco; sem compartilhamento entre workers Octane | Critico |
| Banco legacy remoto com alta latencia | Round-trip >20ms multiplicado por N queries por request | Critico |
| SSR renderiza paginas autenticadas desnecessariamente | Node SSR sobrecarregado com 95%+ das paginas que nao precisam de SEO | Alto |
| Bundle inicial carrega componentes pesados nao vistos | Hidratacao lenta, mais JS parseado no load | Alto |
| Prefetch apenas reativo (hover) | Navegacao percebida como lenta | Medio |

## Solucao

Combinacao de **Cache-First Architecture** com **SSR Optimization seletivo**.

---

## Secao 1: Redis como Cache Default + Camada de Cache Inteligente

### 1.1 Migrar Cache e Sessao para Redis

Alterar `.env`:

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

Redis ja esta configurado em `config/cache.php` (store `redis`, connection `cache`, DB 1) e `config/database.php` (connections `default` e `cache`). Apenas trocar o driver default.

### 1.2 Perfis de Cache por Modulo

| Perfil | TTL | Modulos | Estrategia |
|--------|-----|---------|------------|
| `aggressive` | 15-30min | Decretacoes (leitura), Compdec, Treinamento, Inmet | Cache-aside com warm-up via schedule |
| `moderate` | 2-5min | AjudaHumanitaria, Demandas, Plantao | Cache-aside com invalidacao no write |
| `realtime` | 30-60s | RAT, Suporte | Cache-through com invalidacao por evento |

### 1.3 CacheProfileService

Servico central que encapsula a logica de TTL por modulo. Os services existentes (ex: `ProcessoQueryService`) recebem cache decorators sem reescrever logica de negocio.

```php
class CacheProfileService
{
    private array $profiles = [
        'aggressive' => ['ttl' => 1800, 'tags' => true, 'warmup' => true],
        'moderate'   => ['ttl' => 300,  'tags' => true, 'warmup' => false],
        'realtime'   => ['ttl' => 60,   'tags' => true, 'warmup' => false],
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

    public function remember(string $module, string $key, Closure $callback): mixed
    {
        $profile = $this->profiles[$this->moduleProfiles[$module]];
        return Cache::tags([$module])->remember($key, $profile['ttl'], $callback);
    }

    public function invalidate(string $module, ?string $key = null): void
    {
        if ($key) {
            Cache::tags([$module])->forget($key);
        } else {
            Cache::tags([$module])->flush();
        }
    }
}
```

### 1.4 Invalidacao via Model Observers

Para modulos `aggressive` e `moderate`: Observer generico que invalida cache no `saved()` e `deleted()`.

Para modulos `realtime`: Observer que invalida + dispatcha job de reload.

```php
class CacheInvalidationObserver
{
    public function saved(Model $model): void
    {
        $module = $this->resolveModule($model);
        $service = app(CacheProfileService::class);

        // Invalidar apenas a chave especifica do registro alterado
        $service->invalidate($module, "{$module}:{$model->getKey()}");

        // Para queries de listagem/agregacao, usar lock para evitar cache stampede
        $lockKey = "cache_refresh:{$module}:lists";
        Cache::lock($lockKey, 10)->get(function () use ($service, $module) {
            $service->invalidate($module, "{$module}:list");
        });
    }

    public function deleted(Model $model): void
    {
        $module = $this->resolveModule($model);
        // No delete, flush completo do modulo e aceitavel
        app(CacheProfileService::class)->invalidate($module);
    }
}
```

### 1.5 CacheProfileService como Singleton

Registrar no ServiceProvider para evitar instancias duplicadas sob Octane:

```php
// AppServiceProvider.php
$this->app->singleton(CacheProfileService::class);
```

### 1.6 Warm-up Scheduled para Modulos Aggressive

```php
// Console/Kernel.php ou routes/console.php
Schedule::call(function () {
    try {
        app(DecretacoesWarmupService::class)->warmup();
    } catch (\Throwable $e) {
        Log::channel('critical')->error('Cache warmup failed', [
            'error' => $e->getMessage(),
        ]);
    }
})->everyFifteenMinutes();
```

---

## Secao 2: SSR Seletivo - Apenas Landing/SEO

### 2.1 Regra

- **Com SSR:** Landing page, Login, paginas publicas
- **Sem SSR (SPA puro):** Tudo pos-autenticacao (Dashboard, todos os 11 modulos)

### 2.2 Implementacao

Middleware dedicado que desabilita SSR para usuarios autenticados via `config()` runtime (pattern ja usado no `AppServiceProvider.php` para NativePHP):

```php
// app/Http/Middleware/DisableSsrForAuthenticated.php
class DisableSsrForAuthenticated
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()) {
            config(['inertia.ssr.enabled' => false]);
        }
        return $next($request);
    }
}
```

Registrar o middleware no grupo `web` para rotas autenticadas. SSR permanece ativo para rotas publicas (Login, Landing).

> **Nota:** `Inertia::setSsr()` nao existe no pacote instalado. A abordagem via `config()` e a correta e ja e precedente no codebase.

### 2.3 Ganho Esperado

O servidor Node de SSR deixa de processar ~95% das requisicoes. Reducao significativa de memoria e CPU no container Node. Paginas autenticadas respondem mais rapido (sem round-trip ao Node SSR).

---

## Secao 3: Hidratacao Otimizada com defineAsyncComponent

### 3.1 Alvos Prioritarios

| Componente | Razao | Estrategia |
|------------|-------|------------|
| `DecretacaoDetailModal` | Modal pesado com 4 tabs | `defineAsyncComponent` - carrega ao abrir |
| `ProcessoGrid` | Tabela com filtros complexos | Skeleton + lazy load |
| Graficos (Chart.js) | vendor-charts pesado | `defineAsyncComponent` + chunk separado |
| Mapas (Leaflet) | vendor-maps pesado | `defineAsyncComponent` + chunk separado |
| `AIAssistantModal` | IA Core modal | `defineAsyncComponent` |
| Componentes Admin | Raramente acessados | Lazy route-level |

### 3.2 Pattern

```js
import { defineAsyncComponent } from 'vue'
import SkeletonModal from '@/Components/Atoms/Skeleton/SkeletonModal.vue'

const DecretacaoDetailModal = defineAsyncComponent({
  loader: () => import('@/Components/Organisms/Decretacoes/Details/DecretacaoDetailModal.vue'),
  loadingComponent: SkeletonModal,
  delay: 100,
})
```

### 3.3 Skeleton Components

Criar skeletons leves para feedback visual durante lazy load:
- `SkeletonModal.vue` - placeholder para modais
- `SkeletonTable.vue` - placeholder para tabelas/grids
- `SkeletonChart.vue` - placeholder para graficos

### 3.4 Ganho Esperado

Bundle inicial menor, hidratacao mais rapida, menos JavaScript parseado no carregamento. Componentes pesados carregam sob demanda.

---

## Secao 4: Prefetch Inteligente Baseado em Navegacao

### 4.1 Estrategia em 3 Niveis

| Nivel | Trigger | Acao |
|-------|---------|------|
| **Imediato** | Pagina carregou | Prefetch das 2-3 rotas mais provaveis |
| **Hover** | Mouse sobre link | Comportamento existente do Inertia (manter) |
| **Idle** | `requestIdleCallback` | Pre-carrega chunks dos modulos frequentes |

### 4.2 Composable useSmartPrefetch

> **Nota:** Este composable substitui a funcao `setupPrefetching()` existente em `app.js` (linhas 50-78). A funcao antiga deve ser removida para evitar prefetches duplicados.

```js
// composables/useSmartPrefetch.js
import { onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const navigationMap = {
  'Dashboard':          ['/decretacoes', '/demandas'],
  'Decretacoes/Index':  ['/ajuda-humanitaria'],
  'Admin/Users/Index':  ['/admin/roles', '/admin/permissions'],
}

export function useSmartPrefetch() {
  const page = usePage()
  const currentPage = page.component

  onMounted(() => {
    const probableRoutes = navigationMap[currentPage] || []

    // Nivel 1: Prefetch imediato das rotas provaveis
    probableRoutes.slice(0, 2).forEach(route => {
      router.prefetch(route, { cacheFor: 30000 })
    })

    // Nivel 3: Idle-time prefetch de chunks restantes
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

### 4.3 Integracao

Usar o composable no layout principal (autenticado):

```js
// Layouts/AuthenticatedLayout.vue
import { useSmartPrefetch } from '@/composables/useSmartPrefetch'

useSmartPrefetch()
```

### 4.4 Ganho Esperado

Navegacao entre paginas parece instantanea. Dados e chunks pre-carregados antes do clique.

---

## Secao 5: Verificacao do Octane

### 5.1 Checklist

- Verificar se Octane esta ativo em producao (`php artisan octane:status`)
- Se nao estiver, ativar com RoadRunner (ja configurado em `config/octane.php`)
- **Descomentar `CollectGarbage::class`** em `config/octane.php` linha 109 (listener `OperationTerminated`) - atualmente comentado, o threshold de 50MB nao esta sendo enforced
- **Descomentar `DisconnectFromDatabases::class`** em `config/octane.php` linha 108 - com banco legacy remoto de alta latencia, conexoes stale/quebradas persistem entre requests sob Octane
- Garantir que o Octane nao tem memory leaks com os cache decorators novos (CacheProfileService registrado como singleton)
- Verificar `config/session.php` campo `connection` aponta para connection Redis valida ao trocar `SESSION_DRIVER=redis`

### 5.2 Ganho Esperado

Elimina bootstrap overhead do Laravel por request. Aplicacao fica residente em memoria. GC ativo previne memory leaks. Disconnect previne conexoes stale ao banco legacy.

---

## Ordem de Implementacao Sugerida

1. Redis como cache default (menor esforco, maior impacto)
2. Verificar/ativar Octane
3. CacheProfileService + Observers (resolve gargalo do banco legacy)
4. SSR seletivo (reduz carga do Node)
5. defineAsyncComponent nos alvos prioritarios
6. Skeleton components
7. useSmartPrefetch composable
8. Warm-up scheduled

## Riscos e Mitigacoes

| Risco | Mitigacao |
|-------|----------|
| Redis indisponivel derruba cache e sessoes | Fallback para file cache; Redis Sentinel em prod |
| Cache stale mostra dados desatualizados | Invalidacao via Observer + TTL como safety net |
| SSR desabilitado afeta SEO de alguma pagina | Mapa explicito de paginas SSR; revisar antes de deploy |
| defineAsyncComponent causa flash de loading | Skeleton components + delay baixo (100ms) |
| Octane memory leak com cache service | Monitorar memoria; CollectGarbage ativo; CacheProfileService como singleton |
| Cache stampede em modulos com muitos writes | Lock por modulo no Observer; invalidacao granular por key |
| Conexoes stale ao banco legacy sob Octane | DisconnectFromDatabases ativo no OperationTerminated |

## Metricas de Sucesso

- TTFB < 200ms para paginas autenticadas
- LCP < 1.5s
- Reducao de 70%+ nas queries ao banco legacy
- Node SSR CPU usage < 20% (vs atual)
- Bundle inicial < 200KB gzipped
- **Cache hit ratio > 85%** para modulos aggressive, > 70% para moderate
