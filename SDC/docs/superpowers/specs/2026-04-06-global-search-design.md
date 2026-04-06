# Global Search — Design Spec
**Data:** 2026-04-06  
**Status:** Aprovado  
**Escopo:** CommandPalette + Backend API — busca em banco de dados nos módulos Decretações, RAT, Demandas e PAE

---

## Problema

O `CommandPalette.vue` (busca global da TopBar) realiza apenas busca client-side em itens de navegação estáticos. Protocolos reais do banco de dados — como `MG-F-3101607-12300-20251231` (campo `n_protocolo_fide` de Decretações) — não são encontrados, mesmo existindo.

---

## Objetivo

Implementar busca em banco de dados no CommandPalette sem sobrecarga, usando Redis para cache, índices de banco e LIMIT por módulo.

---

## Arquitetura

### Fluxo

```
[User digita] → debounce 300ms (frontend) → GET /api/global-search?q=...
  → throttle:30,1 (middleware)
  → GlobalSearchController::search()
      → valida: min 3 chars
      → Cache::store('redis')->tags(['global_search'])->remember(key, 60s)
          → GlobalSearchService::search()
                ├── searchDecretacoes()  LIMIT 5
                ├── searchRat()         LIMIT 5
                ├── searchDemandas()    LIMIT 5
                └── searchPae()         LIMIT 5
      → retorna JSON { query, results: { decretacoes[], rat[], demandas[], pae[] } }
  → CommandPalette popula db_results
```

---

## Contrato da API

### Request
```
GET /api/global-search?q={query}
Middleware: auth, throttle:30,1
Validação: q obrigatório, min:3
```

### Response
```json
{
  "query": "MG-F-3101607",
  "results": {
    "decretacoes": [
      {
        "id": 142,
        "title": "MG-F-3101607-12300-20251231",
        "subtitle": "Belo Horizonte · SE",
        "url": "/decretacoes/142",
        "icon": "scale",
        "tag": "DECRETO"
      }
    ],
    "rat": [],
    "demandas": [],
    "pae": []
  }
}
```

Cada item segue o shape existente do `CommandPalette.vue` — sem alteração no template.

---

## Campos de Busca por Módulo

| Módulo | Tabela | Campo(s) | Operador | title | subtitle |
|---|---|---|---|---|---|
| Decretações | `dec_entrada_processos` | `n_protocolo_fide` | `LIKE 'q%'` | `n_protocolo_fide` | município + tipo (SE/ECP) |
| RAT | `rats` | `protocolo` | `LIKE 'q%'` | `protocolo` | município + data |
| Demandas | `tasks` | `titulo` | `LIKE '%q%'` | `titulo` | status + número |
| PAE | `pae_protocolos` | `num_protocolo` | `LIKE 'q%'` | `num_protocolo` | `sei_numero` |

> Decretações, RAT e PAE usam `q%` (prefixo) — compatível com B-tree index.  
> Demandas usa `%q%` pois é busca por título de texto livre.

---

## Estratégia de Performance

### Cache Redis
- Driver: `redis` (store `cache`, já configurado com `phpredis`)
- Tags: `['global_search']` — permite flush seletivo
- TTL: 60 segundos
- Key: `global_search:` + `md5(strtolower(trim($query)))`

```php
Cache::store('redis')
    ->tags(['global_search'])
    ->remember('global_search:' . md5(strtolower(trim($query))), 60, fn() => $this->runSearch($query));
```

### Proteções contra sobrecarga

| Proteção | Camada | Detalhe |
|---|---|---|
| Min 3 chars | Backend + Frontend | Rejeita antes de qualquer query |
| LIMIT 5 por módulo | `GlobalSearchService` | `->limit(5)` em cada subquery |
| `LIKE 'q%'` (prefixo) | Queries SQL | Usa B-tree index — não faz full scan |
| Cache Redis 60s | `GlobalSearchService` | Mesma query = 0 hits no banco |
| Debounce 300ms | `CommandPalette.vue` | Já existe, mantido |
| Rate limit | Middleware | `throttle:30,1` na rota |

### Índices de Banco
Migration única adicionando os 4 índices necessários:

```php
Schema::table('dec_entrada_processos', fn($t) => $t->index('n_protocolo_fide'));
Schema::table('rats',                  fn($t) => $t->index('protocolo'));
Schema::table('tasks',                 fn($t) => $t->index('titulo'));
Schema::table('pae_protocolos',        fn($t) => $t->index('num_protocolo'));
```

---

## Arquivos

### Novos
| Arquivo | Responsabilidade |
|---|---|
| `app/Http/Controllers/GlobalSearchController.php` | Valida request, delega ao service, retorna JSON |
| `app/Services/GlobalSearchService.php` | Busca nos 4 módulos + cache Redis |
| `database/migrations/xxxx_add_global_search_indexes.php` | 4 índices de banco |

### Modificados
| Arquivo | Mudança |
|---|---|
| `routes/api.php` | +1 rota `GET /api/global-search` |
| `resources/js/Components/Organisms/CommandPalette.vue` | ~5 linhas: descomenta chamada API, popula `db_results` |

---

## Comportamento no Frontend

Ao receber resposta da API, o `CommandPalette.vue` mescla `db_results` nos resultados existentes:

```js
const response = await window.axios.get(route('global.search'), {
    params: { q: query.value }
});
results.value = { ...results.value, db_results: response.data.results };
```

A categoria `db_results` já existe em `priorityOrder` e `getCategoryLabel` no componente — nenhuma mudança no template Vue.

---

## O que está fora do escopo

- Busca fuzzy / full-text (Scout/Meilisearch) — não necessário no volume atual
- Preview inline de registros — navega direto para `.show`
- Busca em outros módulos (TDAP, Treinamentos, etc.) — pode ser adicionado depois ao `GlobalSearchService`
