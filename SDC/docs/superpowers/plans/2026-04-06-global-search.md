# Global Search Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar busca em banco de dados no CommandPalette, cobrindo os módulos Decretações, RAT, Demandas e PAE, com cache Redis e sem sobrecarga no banco.

**Architecture:** Um único endpoint `GET /api/global-search?q=` alimentado por `GlobalSearchService`, que busca até 5 resultados por módulo usando `LIKE 'q%'` em campos indexados. Os resultados são cacheados no Redis por 60s com tags para flush seletivo. O `CommandPalette.vue` já tem a estrutura `db_results` pronta — só precisa da chamada real à API.

**Tech Stack:** PHP 8.x, Laravel 10/11, Eloquent ORM, Redis (phpredis), Vue 3 (Composition API), Axios, PHPUnit (DatabaseTransactions)

---

## Mapa de Arquivos

| Arquivo | Ação | Responsabilidade |
|---|---|---|
| `database/migrations/2026_04_06_000001_add_global_search_indexes.php` | Criar | 4 índices B-tree nos campos de busca |
| `app/Services/GlobalSearchService.php` | Criar | Busca nos 4 módulos + cache Redis |
| `app/Http/Controllers/GlobalSearchController.php` | Criar | Valida request, delega ao service, retorna JSON |
| `routes/api.php` | Modificar | +1 rota `GET /api/global-search` |
| `resources/js/Components/Organisms/CommandPalette.vue` | Modificar | Habilita chamada API, popula `db_results` |
| `tests/Feature/GlobalSearch/GlobalSearchServiceTest.php` | Criar | Testes de integração do service |
| `tests/Feature/GlobalSearch/GlobalSearchControllerTest.php` | Criar | Testes HTTP do endpoint |

---

## Task 1: Migration — Índices de banco

**Files:**
- Create: `database/migrations/2026_04_06_000001_add_global_search_indexes.php`

- [ ] **Step 1: Criar a migration**

```bash
cd /path/to/project
php artisan make:migration add_global_search_indexes
```

Renomear o arquivo gerado para `2026_04_06_000001_add_global_search_indexes.php` e substituir o conteúdo por:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $table->index('n_protocolo_fide', 'idx_dec_n_protocolo_fide');
        });

        Schema::table('rats', function (Blueprint $table) {
            $table->index('protocolo', 'idx_rats_protocolo');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('titulo', 'idx_tasks_titulo');
        });

        Schema::table('pae_protocolos', function (Blueprint $table) {
            $table->index('num_protocolo', 'idx_pae_num_protocolo');
        });
    }

    public function down(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $table->dropIndex('idx_dec_n_protocolo_fide');
        });

        Schema::table('rats', function (Blueprint $table) {
            $table->dropIndex('idx_rats_protocolo');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_titulo');
        });

        Schema::table('pae_protocolos', function (Blueprint $table) {
            $table->dropIndex('idx_pae_num_protocolo');
        });
    }
};
```

- [ ] **Step 2: Rodar a migration**

```bash
php artisan migrate
```

Saída esperada:
```
Running migrations.
2026_04_06_000001_add_global_search_indexes .............. DONE
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_06_000001_add_global_search_indexes.php
git commit -m "feat: add db indexes for global search"
```

---

## Task 2: GlobalSearchService

**Files:**
- Create: `app/Services/GlobalSearchService.php`
- Create: `tests/Feature/GlobalSearch/GlobalSearchServiceTest.php`

- [ ] **Step 1: Escrever o teste com falha**

Criar `tests/Feature/GlobalSearch/GlobalSearchServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\GlobalSearch;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Rat\Models\Rat;
use App\Modules\Demandas\Models\Task;
use App\Modules\Demandas\Enums\TaskStatus;
use App\Modules\Demandas\Enums\TipoTask;
use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Enums\Prioridade;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Services\GlobalSearchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GlobalSearchServiceTest extends TestCase
{
    use DatabaseTransactions;

    private GlobalSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GlobalSearchService::class);
        Cache::tags(['global_search'])->flush();
    }

    public function test_returns_decretacao_by_protocolo_prefix(): void
    {
        Processo::create([
            'n_protocolo_fide' => 'MG-F-3101607-12300-20251231',
            'data_entrada'     => now(),
            'processo'         => 'Federal',
            'tipo_desastre'    => 'SE',
        ]);

        $results = $this->service->search('MG-F-3101607');

        $this->assertNotEmpty($results['decretacoes']);
        $this->assertEquals('MG-F-3101607-12300-20251231', $results['decretacoes'][0]['title']);
        $this->assertEquals('scale', $results['decretacoes'][0]['icon']);
        $this->assertEquals('DECRETO', $results['decretacoes'][0]['tag']);
    }

    public function test_returns_rat_by_protocolo_prefix(): void
    {
        Rat::create([
            'protocolo'    => 'RAT-2025-00042',
            'status'       => 'rascunho',
            'dados_gerais' => [],
            'local'        => [],
            'endereco'     => [],
            'comunicacao'  => [],
            'recursos'     => [],
            'envolvidos'   => [],
            'vistoria'     => [],
            'historico'    => [],
            'anexos'       => [],
        ]);

        $results = $this->service->search('RAT-2025');

        $this->assertNotEmpty($results['rat']);
        $this->assertEquals('RAT-2025-00042', $results['rat'][0]['title']);
        $this->assertEquals('document', $results['rat'][0]['icon']);
    }

    public function test_returns_demanda_by_titulo(): void
    {
        Task::create([
            'protocolo'  => 'SDC-2025-000001',
            'tipo'       => TipoTask::INCIDENTE->value,
            'titulo'     => 'Problema com sistema de alertas',
            'status'     => TaskStatus::ABERTA->value,
            'impacto'    => Impacto::MEDIO->value,
            'urgencia'   => Urgencia::MEDIA->value,
            'prioridade' => Prioridade::MEDIA->value,
        ]);

        $results = $this->service->search('alertas');

        $this->assertNotEmpty($results['demandas']);
        $this->assertStringContainsString('alertas', strtolower($results['demandas'][0]['title']));
    }

    public function test_returns_pae_by_num_protocolo_prefix(): void
    {
        PaeProtocolo::create([
            'num_protocolo' => 'PAE-2025-0099',
            'status'        => 'em_analise',
            'sei_numero'    => '1234567',
        ]);

        $results = $this->service->search('PAE-2025');

        $this->assertNotEmpty($results['pae']);
        $this->assertEquals('PAE-2025-0099', $results['pae'][0]['title']);
    }

    public function test_limits_to_5_results_per_module(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Processo::create([
                'n_protocolo_fide' => "LIMIT-TEST-{$i}",
                'data_entrada'     => now(),
                'processo'         => 'Federal',
                'tipo_desastre'    => 'SE',
            ]);
        }

        $results = $this->service->search('LIMIT-TEST');

        $this->assertCount(5, $results['decretacoes']);
    }

    public function test_caches_result_in_redis(): void
    {
        Cache::tags(['global_search'])->flush();

        $this->service->search('CACHE-HIT-TEST');

        $key = 'global_search:' . md5('cache-hit-test');
        $this->assertTrue(Cache::store('redis')->tags(['global_search'])->has($key));
    }

    public function test_returns_empty_arrays_when_no_match(): void
    {
        $results = $this->service->search('ZZZNOTEXISTS999');

        $this->assertEmpty($results['decretacoes']);
        $this->assertEmpty($results['rat']);
        $this->assertEmpty($results['demandas']);
        $this->assertEmpty($results['pae']);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar falha**

```bash
php artisan test tests/Feature/GlobalSearch/GlobalSearchServiceTest.php
```

Saída esperada: `FAIL` com `Class "App\Services\GlobalSearchService" not found`

- [ ] **Step 3: Criar o GlobalSearchService**

Criar `app/Services/GlobalSearchService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Rat\Models\Rat;
use App\Modules\Demandas\Models\Task;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Support\Facades\Cache;

class GlobalSearchService
{
    private const LIMIT = 5;
    private const CACHE_TTL = 60;

    public function search(string $query): array
    {
        $normalized = strtolower(trim($query));
        $key = 'global_search:' . md5($normalized);

        return Cache::store('redis')
            ->tags(['global_search'])
            ->remember($key, self::CACHE_TTL, fn () => $this->runSearch($query));
    }

    private function runSearch(string $query): array
    {
        return [
            'decretacoes' => $this->searchDecretacoes($query),
            'rat'         => $this->searchRat($query),
            'demandas'    => $this->searchDemandas($query),
            'pae'         => $this->searchPae($query),
        ];
    }

    private function searchDecretacoes(string $query): array
    {
        return Processo::without(['municipios', 'desastres'])
            ->select(['id', 'n_protocolo_fide', 'tipo_desastre'])
            ->where('n_protocolo_fide', 'like', $query . '%')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'title'    => $p->n_protocolo_fide,
                'subtitle' => $p->tipo_desastre ?? 'Decretacao',
                'url'      => route('decretacoes.show', $p->id),
                'icon'     => 'scale',
                'tag'      => 'DECRETO',
            ])
            ->toArray();
    }

    private function searchRat(string $query): array
    {
        return Rat::select(['id', 'protocolo', 'status'])
            ->where('protocolo', 'like', $query . '%')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($r) => [
                'id'       => $r->id,
                'title'    => $r->protocolo,
                'subtitle' => ucfirst($r->status ?? 'RAT'),
                'url'      => route('rat.show', $r->id),
                'icon'     => 'document',
                'tag'      => 'RAT',
            ])
            ->toArray();
    }

    private function searchDemandas(string $query): array
    {
        return Task::select(['id', 'protocolo', 'titulo', 'status'])
            ->where('titulo', 'like', '%' . $query . '%')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($t) => [
                'id'       => $t->id,
                'title'    => $t->titulo,
                'subtitle' => $t->protocolo . ' · ' . ($t->status instanceof \BackedEnum ? $t->status->value : $t->status),
                'url'      => route('demandas.show', $t->id),
                'icon'     => 'checkbadge',
                'tag'      => 'DEMANDA',
            ])
            ->toArray();
    }

    private function searchPae(string $query): array
    {
        return PaeProtocolo::select(['id', 'num_protocolo', 'sei_numero', 'status'])
            ->where('num_protocolo', 'like', $query . '%')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'title'    => $p->num_protocolo,
                'subtitle' => $p->sei_numero ? 'SEI: ' . $p->sei_numero : 'PAE',
                'url'      => route('pae.protocolos.index'),
                'icon'     => 'document',
                'tag'      => 'PAE',
            ])
            ->toArray();
    }
}
```

- [ ] **Step 4: Rodar os testes e confirmar passagem**

```bash
php artisan test tests/Feature/GlobalSearch/GlobalSearchServiceTest.php
```

Saída esperada: `PASS` com todos os testes verdes.

- [ ] **Step 5: Commit**

```bash
git add app/Services/GlobalSearchService.php tests/Feature/GlobalSearch/GlobalSearchServiceTest.php
git commit -m "feat: add GlobalSearchService with Redis cache"
```

---

## Task 3: GlobalSearchController

**Files:**
- Create: `app/Http/Controllers/GlobalSearchController.php`
- Create: `tests/Feature/GlobalSearch/GlobalSearchControllerTest.php`

- [ ] **Step 1: Escrever o teste com falha**

Criar `tests/Feature/GlobalSearch/GlobalSearchControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\GlobalSearch;

use App\Models\User;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GlobalSearchControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::tags(['global_search'])->flush();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/global-search?q=test');

        $response->assertStatus(401);
    }

    public function test_query_shorter_than_3_chars_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/global-search?q=ab');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['q']);
    }

    public function test_missing_query_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/global-search');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['q']);
    }

    public function test_valid_query_returns_200_with_correct_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/global-search?q=abc');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'query',
                     'results' => [
                         'decretacoes',
                         'rat',
                         'demandas',
                         'pae',
                     ],
                 ]);
    }

    public function test_returns_matching_decretacao(): void
    {
        $user = User::factory()->create();

        Processo::create([
            'n_protocolo_fide' => 'MG-F-9999999-00000-20251231',
            'data_entrada'     => now(),
            'processo'         => 'Federal',
            'tipo_desastre'    => 'SE',
        ]);

        $response = $this->actingAs($user)->getJson('/api/global-search?q=MG-F-9999999');

        $response->assertStatus(200)
                 ->assertJsonPath('results.decretacoes.0.title', 'MG-F-9999999-00000-20251231')
                 ->assertJsonPath('results.decretacoes.0.tag', 'DECRETO');
    }
}
```

- [ ] **Step 2: Rodar e confirmar falha**

```bash
php artisan test tests/Feature/GlobalSearch/GlobalSearchControllerTest.php
```

Saída esperada: `FAIL` com `Class "App\Http\Controllers\GlobalSearchController" not found` ou rota 404.

- [ ] **Step 3: Criar o GlobalSearchController**

Criar `app/Http/Controllers/GlobalSearchController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __construct(private readonly GlobalSearchService $searchService)
    {
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $query = $request->string('q')->toString();

        return response()->json([
            'query'   => $query,
            'results' => $this->searchService->search($query),
        ]);
    }
}
```

- [ ] **Step 4: Adicionar a rota em `routes/api.php`**

Abrir `routes/api.php` e adicionar o import e a rota após a linha do `use App\Http\Controllers\Api\HealthCheckController;`:

```php
// No bloco de imports no topo do arquivo, adicionar:
use App\Http\Controllers\GlobalSearchController;
```

E adicionar a rota antes da seção `// MONITORING & HEALTH CHECK`:

```php
// ============================================================================
// GLOBAL SEARCH
// ============================================================================

Route::middleware(['auth:sanctum'])
    ->get('/global-search', [GlobalSearchController::class, 'search'])
    ->middleware('throttle:30,1')
    ->name('global.search');
```

- [ ] **Step 5: Rodar os testes e confirmar passagem**

```bash
php artisan test tests/Feature/GlobalSearch/GlobalSearchControllerTest.php
```

Saída esperada: `PASS` com todos os testes verdes.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/GlobalSearchController.php \
        routes/api.php \
        tests/Feature/GlobalSearch/GlobalSearchControllerTest.php
git commit -m "feat: add GlobalSearchController and API route"
```

---

## Task 4: CommandPalette.vue — Habilitar busca no banco

**Files:**
- Modify: `resources/js/Components/Organisms/CommandPalette.vue`

- [ ] **Step 1: Localizar o bloco comentado no `handleInput`**

No arquivo `resources/js/Components/Organisms/CommandPalette.vue`, localizar o bloco `// 2. Perform Backend Search (Debounced)` por volta da linha 416. O bloco atual é:

```js
searchTimeout = setTimeout(async () => {
  try {
    // Simulate/Real API call
    // const response = await window.axios.get(route('global.search'), { params: { query: query.value } });
    // Merge results... 
    // For now, we stick to local results as requested by user environment constraints
    
    // If we had DB results, we would append them:
    // results.value.db_results = response.data;
  } catch (error) {
    // console.error('API Search skipped');
  } finally {
    isLoading.value = false;
  }
}, 300);
```

- [ ] **Step 2: Substituir o bloco pelo código real**

Substituir o bloco inteiro acima por:

```js
searchTimeout = setTimeout(async () => {
  try {
    const response = await window.axios.get(route('global.search'), {
      params: { q: query.value }
    });
    results.value = { ...results.value, db_results: response.data.results };
  } catch (error) {
    // Falha silenciosa — resultados locais continuam visíveis
  } finally {
    isLoading.value = false;
  }
}, 300);
```

- [ ] **Step 3: Verificar que `db_results` já está no label map**

Confirmar que no método `getCategoryLabel` já existe a entrada `db_results`:

```js
const getCategoryLabel = (cat) => {
     const labels = {
        actions: 'Ações',
        navigation: 'Navegação',
        admin: 'Administração',
        db_results: 'Registros do Sistema'   // <-- deve existir
    };
    return labels[cat] || cat;
};
```

Se não existir, adicionar `db_results: 'Registros do Sistema'` no objeto `labels`.

- [ ] **Step 4: Build do frontend**

```bash
npm run dev
```

ou se ambiente de produção:

```bash
npm run build
```

- [ ] **Step 5: Testar manualmente**

1. Acessar `localhost/decretacoes`
2. Pressionar `Ctrl+K` para abrir o CommandPalette
3. Digitar um protocolo FIDE que existe no banco (ex: `MG-F-3101607`)
4. Aguardar ~300ms
5. Verificar que aparece na seção "Registros do Sistema"
6. Clicar no resultado e confirmar que navega para `/decretacoes/{id}`

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/Organisms/CommandPalette.vue
git commit -m "feat: enable db search in CommandPalette via GlobalSearch API"
```

---

## Task 5: Rodar suite completa e validar

- [ ] **Step 1: Rodar todos os testes do GlobalSearch**

```bash
php artisan test tests/Feature/GlobalSearch/
```

Saída esperada:
```
PASS  Tests\Feature\GlobalSearch\GlobalSearchServiceTest
PASS  Tests\Feature\GlobalSearch\GlobalSearchControllerTest
```

- [ ] **Step 2: Rodar suite completa para garantir regressão zero**

```bash
php artisan test
```

Saída esperada: todos os testes passando.

- [ ] **Step 3: Commit final de fechamento**

```bash
git commit --allow-empty -m "feat: global search complete - Decretacoes, RAT, Demandas, PAE"
```

---

## Self-Review

### Cobertura do spec

| Requisito | Task |
|---|---|
| Endpoint `GET /api/global-search?q=` | Task 3 |
| Min 3 chars validation | Task 3 |
| Cache Redis com tags | Task 2 |
| TTL 60s | Task 2 |
| LIMIT 5 por módulo | Task 2 |
| `LIKE 'q%'` prefixo | Task 2 |
| Índices de banco | Task 1 |
| throttle:30,1 | Task 3 |
| CommandPalette popula db_results | Task 4 |
| Navega para .show ao clicar | Task 2 (url via route()) |
| Demandas usa `%q%` | Task 2 |

### Notas de implementação

- `Rat` usa UUID como PK — `route('rat.show', $r->id)` passa o UUID corretamente.
- `Processo` tem `protected $with = ['municipios', 'desastres']` — usar `->without(['municipios', 'desastres'])` para evitar eager load desnecessário na busca.
- `TaskStatus` é um `BackedEnum` — o cast para string na subtitle usa `$t->status->value` via operador ternário.
- PAE não tem rota `.show` individual — usa `pae.protocolos.index` como fallback; pode ser refinado depois.
