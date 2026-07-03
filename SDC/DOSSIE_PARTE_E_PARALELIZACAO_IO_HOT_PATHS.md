# Dossie Tecnico - Parte E - Paralelizacao de I/O em Hot Paths

Data: 2026-07-03
Branch: `feat/a-bela-e-a-fera`
Escopo: reduzir latencia de endpoints com multiplas consultas independentes usando `App\Support\Concurrency\Concurrency`.

## 1. Resumo executivo

Esta atualizacao migrou dois caminhos criticos para fan-out controlado de I/O:

| Hot path | Antes | Depois |
| --- | --- | --- |
| Busca global (`GlobalSearchService`) | 4 consultas independentes executadas em serie: PAE, decretacoes, RAT e demandas | `Concurrency::parallel()` com PDO por coroutine quando hooks Swoole + pool estao ativos; fallback `Concurrency::tasks()` quando nao ha I/O paralelo no worker HTTP |
| Estatisticas RAT (`RatUnifiedController@index` e `statistics`) | 4 `count()` independentes executados em serie | Helper unico com `Concurrency::parallel()` para hooks + pool, `Concurrency::tasks()` como fallback e cache preservado no `index()` |

O contrato de closures foi mantido: closures concorrentes sao `static`, capturam apenas escalares de busca/datas e nao capturam `$this`, request ou instancias de model.

## 2. Arquivos alterados

| Arquivo | Alteracao |
| --- | --- |
| `app/Support/Concurrency/Concurrency.php` | Adicionado `databaseParallelAvailable()` para expor a condicao segura de paralelismo de I/O com hooks Swoole, pool PDO e fora de transacao |
| `app/Services/GlobalSearchService.php` | Busca global dividida em SQL reutilizavel + mapeadores; fan-out via `parallel()` quando possivel e `tasks()` como fallback |
| `app/Modules/Rat/Controllers/RatUnifiedController.php` | Estatisticas RAT centralizadas em helper estatico; counts independentes migrados para fan-out controlado; paginacao do `index()` preservada |
| `tests/Unit/GlobalSearchServiceTest.php` | Regressao funcional da busca global no fallback sem hooks |
| `tests/Unit/ConcurrencyIoFallbackTest.php` | Teste basico de gating/fallback de concorrencia |

## 3. Mapeamento dos endpoints auditados

| Area | Status |
| --- | --- |
| `DashboardStatisticsService` | Ja usava `Concurrency::tasks()` antes desta Parte E |
| `ProcessoStatsService` | Ja usava `Concurrency::tasks()` antes desta Parte E |
| `PmdaController` | Ja tinha uso de `Concurrency::tasks()` em pontos candidatos |
| Busca global | Migrado nesta Parte E |
| RAT index/statistics | Migrado nesta Parte E |

## 4. Estrategia aplicada

### 4.1 Hooks ON + pool ativo

Quando `Concurrency::databaseParallelAvailable()` retorna `true`, os hot paths usam `Concurrency::parallel()`.

Condicoes exigidas:

- extensao Swoole carregada;
- execucao dentro de coroutine;
- `hook_flags` diferente de zero;
- pool `swoole.pgsql.pool` registrado;
- nenhuma transacao aberta na conexao atual.

Cada closure recebe seu proprio `PDO`, evitando compartilhamento inseguro de socket entre coroutines.

### 4.2 Hooks OFF ou ambiente sem pool

Quando a condicao de I/O paralelo no worker HTTP nao existe, os hot paths usam `Concurrency::tasks()`.

Esse fallback preserva o comportamento ja existente do helper:

- usa task workers quando disponiveis;
- registra timeout/degradacao e recompoe sequencialmente quando necessario;
- cai para sequencial em CLI/testes/RoadRunner/sem Swoole;
- nao paraleliza dentro de transacao.

## 5. Detalhes por hot path

### 5.1 Busca global

Antes:

- `searchPae()`;
- `searchDecretacoes()`;
- `searchRat()`;
- `searchDemandas()`.

Essas quatro consultas eram chamadas em serie dentro de `runSearch()`.

Depois:

- `runSearch()` e o callback de cache sao estaticos;
- SQL foi extraido para metodos reutilizaveis;
- consultas via `PDO` sao usadas no caminho `parallel()`;
- consultas via `DB::select()` sao usadas no fallback `tasks()`;
- os mapeadores foram mantidos para preservar o shape da resposta.

### 5.2 RAT index/statistics

Antes:

- listagem paginada executava normalmente;
- em seguida o cache miss de estatisticas executava 4 counts em serie;
- endpoint JSON `statistics()` repetia a mesma sequencia de 4 counts.

Depois:

- listagem e paginacao permanecem no worker HTTP;
- os 4 counts foram centralizados em `ratStatistics()`;
- `index()` continua usando cache de 300 segundos;
- `statistics()` passa a usar o mesmo helper;
- no caminho PDO foi mantido `deleted_at IS NULL`, preservando a semantica de `SoftDeletes`.

## 6. Preservacoes e restricoes atendidas

| Criterio | Resultado |
| --- | --- |
| Nao reescrever arquitetura | Atendido. Mudancas pequenas em service/controller/helper |
| Pelo menos 2 hot paths | Atendido: busca global e RAT stats |
| Hooks ON sem socket compartilhado | Atendido por `parallel()` com PDO por closure via pool |
| Hooks OFF funcional | Atendido por `tasks()` e teste de fallback |
| Sem capturar `$this`/request/models | Atendido nas closures concorrentes |
| Paginacao preservada | Atendido: query paginada do RAT nao foi movida para task/coroutine |
| Transacoes preservadas | Atendido: gating usa `transactionLevel() === 0` |
| Logs de timeout/degradacao | Mantidos em `Concurrency::tasks()` |

## 7. Validacoes executadas

Ambiente local:

- `php -r "echo extension_loaded('swoole') ? 'swoole=yes' : 'swoole=no';"` -> `swoole=no`

Lint:

- `php -l app/Support/Concurrency/Concurrency.php`
- `php -l app/Services/GlobalSearchService.php`
- `php -l app/Modules/Rat/Controllers/RatUnifiedController.php`
- `php -l tests/Unit/GlobalSearchServiceTest.php`
- `php -l tests/Unit/ConcurrencyIoFallbackTest.php`

Testes:

- `php artisan test --filter=GlobalSearchServiceTest` -> 2 passed
- `php artisan test --filter=ConcurrencyIoFallbackTest` -> 2 passed
- `php artisan test --filter=PasswordVerifierTest` -> 3 passed
- `php artisan test --filter=OctaneDiagnosticsCommandTest` -> 1 passed

## 8. Observacao de medicao de latencia

Neste ambiente local a extensao Swoole nao esta carregada, entao a validacao automatizada exercitou o fallback seguro. Em Octane/Swoole com hooks + pool ativo, o ganho esperado e mensuravel porque os grupos independentes deixam de somar latencia de banco:

- busca global: de 4 consultas em serie para fan-out de 4 consultas;
- RAT stats: de 4 counts em serie para fan-out de 4 counts.

Para medir no runtime alvo, comparar `time_total`/APM antes e depois em miss de cache:

```bash
curl -s -o /dev/null -w "time_total=%{time_total}\n" "https://HOST/ROTA_DA_BUSCA_GLOBAL?q=ABC"
curl -s -o /dev/null -w "time_total=%{time_total}\n" "https://HOST/rat/statistics"
```

## 9. Rollback objetivo

Rollback de baixo risco:

1. Remover chamadas a `Concurrency::parallel()`/`tasks()` dos dois hot paths.
2. Voltar `GlobalSearchService::runSearch()` para o array sequencial original.
3. Voltar `RatUnifiedController` para os 4 counts diretos.
4. Remover `databaseParallelAvailable()` se nao houver outro consumidor.
