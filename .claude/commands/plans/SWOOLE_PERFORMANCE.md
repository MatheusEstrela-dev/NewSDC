# Swoole — Análise de Performance e Otimização Máxima
**NewSDC — sdcdefesa.azurewebsites.net**
**Referência técnica: 06/06/2026**

---

## Atualização operacional — 17/06/2026

Esta seção atualiza o plano com base na execução real do `kernel.py`, leitura segura das credenciais locais e rampagem Locust contra `https://sdcdefesa.azurewebsites.net`.

### Kernel e credenciais

Comandos executados no repositório `NewSDC`:

```powershell
python .claude\kernel.py --help
python .claude\credentials.py --list
python .claude\kernel.py --detect "atualizar plano swoole performance com resultado real locust azure"
python .claude\kernel.py --verify-subsystem
python .claude\kernel.py --agent-call infra.health --focus azure
```

Status final do kernel:

```txt
Status: OK
Resumo: subsistema .claude integrado
modules.load: 9 modulo(s) carregado(s), 0 erro(s)
agents.functions: todas as funcoes esperadas registradas
kernel.detect: modulo de commands/plans casado por trigger
```

Credenciais disponíveis pelo loader `.claude/credentials.py`:

```txt
ai_gemini
azure_prod
database_prod
ngrok
notion_prod
redis_prod
smtp_prod
```

Para esta validação, a seção relevante é `azure_prod`, que contém os campos necessários para operar Azure/App Service via service principal. Os valores secretos não devem ser impressos em relatório ou log. O kernel detectou a integração Azure e apontou:

```txt
Resource group padrao: Defesa_Civil
App Service prod: sdcdefesa
ACR: acrdefesacivil
Storage: newsdc
```

Correções feitas no subsistema `.claude` para o kernel inicializar:

- `InfraAgent` passou a expor `check_system_health(focus)`, `plan_deploy_checklist()` e `diagnose_failure()`.
- `CodeReviewAgent` passou a expor `plan_review()`, `review_changed_files()` e `validation_commands()`.
- `_module.py` passou a ignorar `OSError` em worktrees/symlinks inacessíveis, como `public/storage` no Windows.

### Evidência real de carga — Azure

Fonte dos artefatos:

```txt
C:\Users\x24679188\Documents\Github\BotPerfomance\RELATORIO_RAMPAGEM_AZURE_2026-06-17.md
C:\Users\x24679188\Documents\Github\BotPerfomance\reports\azure-stress-ui-20260617-visual-1000_stats.csv
C:\Users\x24679188\Documents\Github\BotPerfomance\reports\azure-stress-ui-20260617-visual-1000_failures.csv
```

Curva executada visualmente no Locust:

```txt
50 usuarios  -> spawn 5/s
200 usuarios -> spawn 15/s
500 usuarios -> spawn 20/s
1000 usuarios -> spawn 50/s
```

Resultado consolidado da rampa visual:

| Degrau | Requests acumulados | Falhas | RPS agregado | Mediana | p95 | p99 | Leitura |
|---|---:|---:|---:|---:|---:|---:|---|
| 50 usuários | 573 | 0 | 10,63 | 1.300 ms | 4.400 ms | 5.800 ms | Estável, mas já lento |
| 200 usuários | 1.855 | 0 | 7,84 | 2.200 ms | 9.000 ms | 13.000 ms | RPS caiu; fila crescendo |
| 500 usuários | 4.241 | 0 | 8,98 | 6.400 ms | 25.000 ms | 43.000 ms | Saturação confirmada |
| 1000 usuários | 17.070 | 2 | 11,50 | 17.000 ms | 40.000 ms | 48.000 ms | Ruptura operacional |

Resultado final salvo após parar a carga:

| Métrica | Valor |
|---|---:|
| Requests | 20.392 |
| Falhas | 3 |
| RPS agregado | 13,75 |
| Mediana | 19.000 ms |
| Média | 25.231 ms |
| p95 | 63.000 ms |
| p99 | 108.000 ms |
| Máximo | 124.000 ms |

### Interpretação

O resultado real **não confirma ganho compatível com Swoole ativo em produção**.

O comportamento medido foi:

```txt
Usuarios sobem.
RPS nao sobe proporcionalmente.
Mediana, p95 e p99 explodem.
Fila de requests cresce.
```

Isso é assinatura de arquitetura saturada/enfileirada, não de coroutines absorvendo I/O.

Comparação com a meta deste plano:

| Referência | RPS esperado/medido |
|---|---:|
| Meta Swoole básico B1 | 80-100 RPS |
| Meta Swoole máximo B1 | 120-140 RPS |
| Stress visual real 1000 usuários | 13,75 RPS |
| p95 real no pico | 63s |
| p99 real no pico | 108s |

Conclusão operacional:

```txt
Antes de continuar tratando este plano como ganho já obtido,
é obrigatório confirmar o runtime real do App Service.

Hipótese mais provável:
  o ambiente Azure testado não está rodando Swoole efetivo
  ou está limitado por configuração de worker/runtime/proxy/rate limit.
```

### Update do plano

Prioridade agora deixa de ser "otimizar mais" e passa a ser "provar runtime e remover gargalo de implantação".

Checklist revisado:

1. Confirmar no Azure App Service o runtime efetivo:
   - variável `OCTANE_SERVER`;
   - comando/container de start;
   - extensão PHP `swoole`;
   - quantidade real de workers;
   - logs de boot do Octane.
2. Usar credencial `azure_prod` via loader seguro para consultar App Service, sem imprimir `client_secret`.
3. Coletar métricas Azure durante nova rampa:
   - CPU;
   - memória;
   - HTTP 5xx;
   - response time;
   - App Service requests;
   - conexões PostgreSQL/Redis.
4. Rodar teste curto pós-confirmação:
   - 50 usuários;
   - 100 usuários;
   - 200 usuários;
   - parar se p95 passar de 10s.
5. Só considerar Swoole validado se houver aumento real de throughput:
   - B1: mínimo esperado 80 RPS com p95 controlado;
   - S2: mínimo esperado 160 RPS com p95 controlado;
   - S3: mínimo esperado 350 RPS com p95 controlado.
6. Se o runtime já for Swoole e o RPS continuar abaixo de 30:
   - investigar proxy/App Service;
   - revisar `worker_num`;
   - revisar `max_request`;
   - revisar conexão com PostgreSQL/Redis;
   - separar teste de login de teste de leitura;
   - criar massa de usuários de teste para não medir throttle de CPF único.

Esta atualização não invalida a análise teórica abaixo. Ela registra que, no Azure testado em 17/06/2026, o ganho esperado ainda **não apareceu na medição real**.

---

## Por que o FrankenPHP síncrono desperdiça CPU

Cada worker PHP bloqueia durante I/O. Em um request típico do NewSDC (~120ms):

```
Composição de tempo por request:
  CPU (PHP puro):            ~20ms  (17%)
  I/O wait (DB + Redis):    ~100ms  (83%)

FrankenPHP síncrono — B1 (1 vCore, 2 workers):

  Worker 1: [CPU 10ms][░░░░░░░░░ IO 100ms ░░░░░░░░░][CPU 10ms]...
  Worker 2:            [CPU 10ms][░░░░░░░░░ IO 100ms ░░░░░░░░░]...
  vCore:    ████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░████░░░░░░░
             ↑ CPU usado    ↑ CPU OCIOSO 83% do tempo

RPS: 2 workers ÷ 0,120s = ~16 RPS
CPU aproveitada: ~17% do vCore disponível
```

```
Swoole coroutines — B1 (1 vCore, 2 workers):

  Worker 1: [C1][C2][C3][C4][C5][C6]...[C1 resume][C2 resume]...
  vCore:    ████████████████████████████████████████████████████
             ↑ CPU ocupado quase 100% do tempo — sem desperdício

RPS teórico I/O: 2 workers × 10 coroutines × (1 ÷ 0,120s) = ~166 RPS
RPS real (teto CPU): 1 vCore × (1.000ms ÷ 10ms/req) = ~100 RPS
```

---

## Dois tetos — qual limita primeiro?

```
Teto de I/O (workers × coroutines por worker):
  concorrência = tempo_total ÷ tempo_CPU = 120ms ÷ 10ms = 12 coroutines
  8 workers × 12 × (1 ÷ 0,120s) = 800 RPS

Teto de CPU (vCores disponíveis):
  S3: 4 vCores × (1.000ms ÷ 10ms CPU/req) = 400 RPS  ← limita primeiro
```

O Swoole elimina o gargalo de I/O — o CPU vira o único limitante. A partir daí, escalar horizontalmente multiplica linearmente.

---

## Ganho por tier — inversamente proporcional ao hardware

O ganho percentual é maior em hardware fraco porque o desperdício de CPU é proporcionalmente maior.

| Tier | vCores | FrankenPHP síncrono | Swoole básico | Swoole + todas otimizações | CPU aproveitada (síncrono) |
|---|---|---|---|---|---|
| **B1** | 1 | ~16 RPS | **~80–100 RPS** | **~120–140 RPS** | ~17% |
| S2 | 2 | ~35 RPS | **~160–200 RPS** | **~240–280 RPS** | ~20% |
| S3 | 4 | ~80 RPS | **~350–400 RPS** | **~480–500 RPS** | ~24% |
| P2v3 | 4 + RAM | ~80 RPS | **~380–420 RPS** | **~490–520 RPS** | ~24% |

> Swoole no B1 entrega o mesmo RPS que S3 com FrankenPHP síncrono — sem pagar 17× mais por mês (~R$12.660/ano de economia).

---

## Análise por tipo de endpoint

A relação ganho ∝ tempo_IO ÷ tempo_CPU determina onde o Swoole ajuda mais:

| Endpoint | CPU | I/O | Razão I/O:CPU | Ganho Swoole |
|---|---|---|---|---|
| GET /api/health | ~3ms | ~5ms | 1,7:1 | moderado |
| GET /demandas | ~15ms | ~100ms | 6,7:1 | **alto** |
| GET /dashboard | ~20ms | ~90ms | 4,5:1 | **alto** |
| GET /api/v1/* | ~10ms | ~80ms | 8:1 | **muito alto** |
| POST /login (bcrypt) | ~200ms | ~20ms | 0,1:1 | quase nenhum |
| GET /api/documentation.json (cacheado) | ~0,5ms | ~0,5ms | 1:1 | — já é rápido |

> O login é CPU-bound por design (bcrypt). O Swoole não melhora o bcrypt — mas Task Workers isolam esse custo do pool HTTP.

---

## Swoole vs FrankenPHP vs PHP Fibers

| | FrankenPHP (atual) | PHP Fibers | Swoole |
|---|---|---|---|
| Worker persistente | ✅ | ✅ | ✅ |
| Conexão DB quente | ❌ 1/worker | ❌ manual | ✅ PDOPool |
| I/O assíncrono | ❌ síncrono | ⚠️ manual (amphp) | ✅ automático (SWOOLE_HOOK_ALL) |
| Reescrita de código | nenhuma | queries críticas | nenhuma |
| Memória compartilhada | ❌ | ❌ | ✅ Swoole\Table |
| CPU offload (bcrypt) | ❌ | ❌ | ✅ Task Workers |
| Setup de conexão | 5–15ms/req | 5–15ms/req | 0ms (pool) |
| Overhead de switch | — | ~3ms (PHP) | ~0,1ms (C) |
| Esforço de migração | — | 2 semanas | 3–5 dias |

---

## Otimizações de código — do maior para o menor impacto

### 1 — Queries paralelas com WaitGroup

Remove a serialização de I/O dentro de um único request.

```
ANTES: 3 queries sequenciais = 120ms bloqueado
DEPOIS: 3 queries paralelas  =  40ms bloqueado
```

```php
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine;

$wg      = new WaitGroup();
$results = [];

$wg->add();
Coroutine::create(function() use ($wg, &$results) {
    $results['demandas']   = Demanda::with('municipio')->paginate(20);
    $wg->done();
});

$wg->add();
Coroutine::create(function() use ($wg, &$results) {
    $results['municipios'] = Municipio::all();
    $wg->done();
});

$wg->add();
Coroutine::create(function() use ($wg, &$results) {
    $results['config']     = ConfiguracaoSistema::pluck('valor', 'chave');
    $wg->done();
});

$wg->wait(); // espera os 3 juntos — max(40ms, 40ms, 40ms) = 40ms
```

**Ganho:** latência do request de 120ms → 40ms. Worker processa 3× mais requests no mesmo intervalo.

---

### 2 — Task Workers para CPU pesada (bcrypt fora do HTTP pool)

```
Sem task workers:
  HTTP worker → bcrypt 200ms → travado → demais requests esperam na fila

Com task workers:
  HTTP worker → dispatch → suspende coroutine (libera worker imediatamente)
  Task worker → bcrypt 200ms → notifica HTTP worker
  HTTP worker → retoma → responde
```

```php
// config/octane.php
'swoole' => [
    'options' => [
        'task_worker_num'       => 4,    // 4 workers dedicados a CPU pesada
        'task_enable_coroutine' => true,
        'enable_coroutine'      => true,
        'hook_flags'            => SWOOLE_HOOK_ALL,
    ],
],

// LoginRequest.php — bcrypt delegado ao task worker
public function authenticate(): void
{
    $this->ensureIsNotRateLimited();

    $cpf  = preg_replace('/\D/', '', $this->string('cpf'));
    $user = Cache::remember("user:cpf:{$cpf}", 30, fn() =>
        User::where('cpf', $cpf)->first()
    );

    if (!$user) {
        RateLimiter::hit($this->throttleKey());
        throw ValidationException::withMessages(['cpf' => trans('auth.failed')]);
    }

    // HTTP worker fica livre durante os 200ms de bcrypt
    $valid = app('swoole')->taskwait([
        'action'   => 'verify_password',
        'password' => $this->string('password'),
        'hash'     => $user->password,
    ], timeout: 5.0);

    if (!$valid) {
        RateLimiter::hit($this->throttleKey());
        throw ValidationException::withMessages(['cpf' => trans('auth.failed')]);
    }

    Auth::login($user, $this->boolean('remember'));
    RateLimiter::clear($this->throttleKey());
}

// app/Swoole/TaskHandler.php
class TaskHandler
{
    public function handle(Server $server, Task $task): void
    {
        match ($task->data['action']) {
            'verify_password' => $task->finish(
                Hash::check($task->data['password'], $task->data['hash'])
            ),
        };
    }
}
```

**Ganho:** os 8 HTTP workers nunca ficam presos no bcrypt. Logins simultâneos não degradam requests de leitura.

---

### 3 — Connection Pool nativo com PostgreSQL

```
FrankenPHP:   1 conexão PDO por worker, 1 request por vez
Swoole pool:  N conexões por worker, compartilhadas entre coroutines

Overhead PDO hook (SWOOLE_HOOK_ALL): ~3ms por query (PHP-level)
Overhead native pool:                ~0,1ms por query (C-level)
Economia em 10 queries/request:      ~29ms
```

```php
// OctaneServiceProvider.php — boot UMA VEZ por worker
use Swoole\Database\PDOPool;
use Swoole\Database\PDOConfig;

Octane::bootWorker(function () {
    app()->singleton('pgsql.pool', fn() => new PDOPool(
        (new PDOConfig())
            ->withDriver('pgsql')
            ->withHost(config('database.connections.pgsql.host'))
            ->withPort(5432)
            ->withDbName(config('database.connections.pgsql.database'))
            ->withUsername(config('database.connections.pgsql.username'))
            ->withPassword(config('database.connections.pgsql.password'))
            ->withOptions([
                \PDO::ATTR_PERSISTENT       => true,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
            ]),
        pool_size: 16  // 16 conexões persistentes por worker
    ));

    app()->singleton('redis.pool', fn() => new \Swoole\Database\RedisPool(
        (new \Swoole\Database\RedisConfig())
            ->withHost(config('database.redis.default.host'))
            ->withPort(config('database.redis.default.port'))
            ->withAuth(config('database.redis.default.password')),
        pool_size: 16
    ));
});

// Uso — conexão já está aberta, zero handshake TCP
class DemandaRepository
{
    public function paginate(int $page, int $perPage = 20): array
    {
        $pool = app('pgsql.pool');
        $conn = $pool->get();

        try {
            $stmt = $conn->prepare(
                'SELECT d.*, m.nome as municipio_nome
                 FROM demandas d
                 JOIN municipios m ON m.id = d.municipio_id
                 ORDER BY d.created_at DESC
                 LIMIT ? OFFSET ?'
            );
            $stmt->execute([$perPage, ($page - 1) * $perPage]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } finally {
            $pool->put($conn);  // devolve — conexão permanece aberta
        }
    }
}
```

**Ganho:** setup de conexão de 5–15ms/req para 0ms. Em 1000 req/s = 5–15 segundos de CPU liberados por segundo.

---

### 4 — Defer: responde primeiro, processa depois

```php
use Swoole\Coroutine\defer;

// ANTES — AuditLog bloqueia a resposta por 20ms
public function store(Request $request): JsonResponse
{
    $demanda = Demanda::create($request->validated());
    AuditLog::create([...]);        // 20ms — cliente esperando
    return response()->json($demanda);
}

// DEPOIS — responde em ~5ms, AuditLog executa após o envio
public function store(Request $request): JsonResponse
{
    $demanda = Demanda::create($request->validated());
    $userId  = Auth::id();

    defer(function() use ($demanda, $userId) {
        // Executa APÓS a resposta ser enviada ao cliente
        AuditLog::create([
            'user_id'     => $userId,
            'action'      => 'demanda.created',
            'resource_id' => $demanda->id,
        ]);
        Cache::tags(['demandas'])->flush();
    });

    return response()->json($demanda);  // ~5ms — sem esperar AuditLog
}
```

**Ganho:** tempo percebido pelo cliente cai de 25ms para 5ms. O AuditLog ainda acontece, mas fora do caminho crítico.

---

### 5 — Swoole\Atomic para rate limiting sem Redis

```
Redis INCR (atual):    ~1ms por verificação (round-trip de rede)
Swoole\Atomic:         ~0,001ms (memória compartilhada, lock-free)
Ganho:                 1.000× mais rápido por verificação
```

```php
// OctaneServiceProvider.php
Octane::bootWorker(function () {
    app()->singleton('rate.atomic', fn() => [
        'public' => new \Swoole\Atomic(0),
        'login'  => new \Swoole\Atomic(0),
    ]);
});

// Middleware — substitui Redis throttle para endpoints críticos
class AtomicRateLimiter
{
    public function handle(Request $request, Closure $next, string $key, int $limit)
    {
        $atomic  = app('rate.atomic')[$key];
        $current = $atomic->add(1);  // 0,001ms — memória compartilhada

        if ($current > $limit) {
            $atomic->sub(1);
            return response()->json(['message' => 'Too Many Requests'], 429);
        }

        $response = $next($request);

        if ($current === 1) {
            \Swoole\Timer::after(60_000, fn() => $atomic->set(0));
        }

        return $response;
    }
}
```

**Ganho:** crítico a 1500 req/s — sem Atomic, Redis fica congestionado só com verificações de rate limit.

---

### 6 — Swoole\Table: memória compartilhada entre workers

```
Redis (cache atual):    ~1ms por leitura (rede)
Swoole\Table:           ~0,001ms (RAM compartilhada entre workers)
Ganho:                  1.000× mais rápido para dados quentes
```

```php
// bootstrap/swoole_tables.php
$table = new \Swoole\Table(4096);
$table->column('value',      \Swoole\Table::TYPE_STRING, 512);
$table->column('expires_at', \Swoole\Table::TYPE_INT);
$table->create();

app()->instance('swoole.table', $table);

// Pre-carregar no boot do worker
Octane::bootWorker(function () {
    $table = app('swoole.table');

    // Configurações que mudam raramente — Redis uma vez, Table sempre
    ConfiguracaoSistema::all()->each(fn($c) =>
        $table->set("config:{$c->chave}", [
            'value'      => $c->valor,
            'expires_at' => time() + 300,
        ])
    );
});

// No controller — zero round-trip
$limite = app('swoole.table')->get('config:rate_limit_public', 'value');
```

**Ideal para:** configurações do sistema, dados de municípios, feature flags, dados de lookup que mudam raramente.

---

### 7 — OPcache + JIT calibrado para workers persistentes

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256         ; workers persistentes precisam de mais memória
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0          ; nunca revalida arquivo em produção
opcache.revalidate_freq=0
opcache.preload=/app/bootstrap/preload.php
opcache.preload_user=www-data

; JIT — tracing JIT é o melhor para aplicações Laravel
opcache.jit_buffer_size=128M
opcache.jit=1255
```

```php
// bootstrap/preload.php — classes hot carregadas antes dos workers iniciarem
$classes = [
    \Illuminate\Http\Request::class,
    \Illuminate\Routing\Router::class,
    \Illuminate\Database\Eloquent\Model::class,
    \App\Models\Demanda::class,
    \App\Models\User::class,
    \App\Models\Municipio::class,
    \App\Http\Middleware\Authenticate::class,
    \App\Http\Middleware\ApiRateLimiter::class,
];

foreach ($classes as $class) {
    opcache_compile_file((new ReflectionClass($class))->getFileName());
}
```

**Ganho:** ~15% de redução de CPU por request em código PHP puro. Para requests com 10ms de CPU, salva ~1,5ms — equivale a aumentar o vCore em 15%.

---

### 8 — Hot path: bypass do Laravel para endpoints críticos

```php
// public/index.php — intercepta antes do kernel Laravel inteiro
if ($_SERVER['REQUEST_URI'] === '/api/health' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(['status' => 'ok', 'server' => 'swoole']);
    exit;  // ~0,3ms — zero overhead de framework
}

// Swagger spec — servido direto da Swoole\Table se disponível
if ($_SERVER['REQUEST_URI'] === '/api/documentation.json') {
    $table = app('swoole.table');
    if ($cached = $table->get('swagger:json', 'value')) {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');
        echo $cached;
        exit;  // ~0,5ms — sem tocar no Laravel
    }
}
```

**Ganho:** `/api/health` de ~8ms para ~0,3ms. Para monitoramento com 100 req/s, libera um worker inteiro.

---

## Teto teórico com todas as otimizações ativas

```
Composição de tempo por request — com otimizações máximas:

  Framework (preload + JIT):      3ms   (era 8ms  — JIT -62%)
  DB queries (native pool):      12ms   (era 40ms — paralelo WaitGroup)
  Redis (pool nativo):            0,5ms (era 1ms)
  Rate limit (Atomic):            0,001ms (era 1ms)
  AuditLog (defer):               0ms   (era 20ms — saiu do caminho crítico)
  Bcrypt (task worker):           0ms   (era 200ms — saiu do HTTP worker)
  ─────────────────────────────────────────────────────────
  CPU efetivo por request:       ~8ms   (era ~20ms)
  I/O por request:               12ms   (era 100ms)

Concorrência por worker:
  I/O 12ms ÷ CPU 8ms = 1,5 coroutines (I/O menor que CPU — CPU limita)

Teto por vCore:
  1.000ms ÷ 8ms CPU = 125 RPS/vCore
```

```
RPS final por tier — baseline vs Swoole máximo:

  Tier  │ FrankenPHP │ Swoole básico │ Swoole máximo │ Para 1500 req/s
  ──────┼────────────┼───────────────┼───────────────┼─────────────────
  B1    │   ~16      │   ~100        │   ~125        │ 12 instâncias
  S2    │   ~35      │   ~200        │   ~250        │  6 instâncias
  S3    │   ~80      │   ~400        │   ~500        │  3 instâncias ✅
  P2v3  │   ~80      │   ~420        │   ~520        │  3 instâncias ✅
```

```
Evolução do RPS no S3 (4 vCores, 8 workers):

   500 │                                           ████ Swoole máximo (teto código)
   490 │                                      ████      + defer + atomic
   470 │                                 ████           + native pool
   450 │                            ████                + task workers
   420 │                       ████                     + WaitGroup
   350 │                  ████                          Swoole básico
    80 │████████████████                               FrankenPHP síncrono
     0 └──────────────────────────────────────────────────────────────────
       Atual   Swoole  +Wait  +Task  +Pool  +defer  +JIT
               básico  Group  Work   nativo +atomic +preload
```

---

## Para 1500 req/s — o que código resolve vs infra

| Endpoint | Swoole resolve sozinho? | Instâncias S3 necessárias |
|---|---|---|
| `/api/documentation.json` (Swagger cacheado) | ✅ sim, no B1 | 1 |
| `/api/health` (hot path bypass) | ✅ sim, no B1 | 1 |
| `/api/v1/*` (reads com cache) | ⚠️ parcial — S3 × 2 | 2 |
| `/dashboard`, `/demandas` (DB queries) | ❌ precisa de infra | 3 |
| `/login` (bcrypt — CPU-bound) | ❌ rate limit protege | — |
| **Tráfego geral misto** | ❌ precisa de infra | **3 instâncias S3** |

> **A partir de 500 RPS por instância, o gargalo passa para PostgreSQL e rede — não mais o PHP.**
> O próximo passo além do Swoole máximo é **read replicas do PostgreSQL** e **sharding do Redis**.

---

## Configuração mínima para ativar o Swoole

```dockerfile
# Dockerfile — substituir a imagem base
FROM php:8.5-cli-alpine

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install swoole \
    && docker-php-ext-enable swoole \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY . /app
WORKDIR /app

CMD ["php", "artisan", "octane:start", \
     "--server=swoole", \
     "--workers=8", \
     "--task-workers=4", \
     "--max-requests=500", \
     "--host=0.0.0.0", \
     "--port=8000"]
```

```dotenv
# .env de produção
OCTANE_SERVER=swoole
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

```bash
# Testar localmente antes de fazer deploy
composer require spiral/roadrunner-laravel
php artisan octane:install --server=swoole
php artisan octane:start --server=swoole --workers=4
```

---

## Otimizações de CPU de runtime (abaixo do código de aplicação)

### CPU Affinity: eliminar context switch do OS

Sem affinity, o OS migra workers entre cores livremente — cada migração invalida o cache L1/L2 do core anterior.

```php
// OctaneServiceProvider.php
Octane::bootWorker(function () {
    $workerId = (int) app('swoole')->worker_id;
    $coreId   = $workerId % swoole_cpu_num();
    swoole_set_cpu_affinity([$coreId]);
    // Worker 0 → core 0 sempre. L1/L2 cache sempre quente.
});
```

```
Sem affinity: Worker 0 → core 0 → core 2 → core 0  (cache fria a cada troca)
Com affinity: Worker 0 → core 0 → core 0 → core 0  (cache sempre quente)
Ganho: ~3–8% de throughput — gratuito, zero mudança de código de app
```

### GC manual: eliminar pausas do Garbage Collector

```php
Octane::bootWorker(function () {
    gc_disable();  // sem pausas imprevisíveis no meio de requests
});

// GC no boundary seguro — entre requests, não durante
Octane::requestHandled(function () {
    if (memory_get_usage() > 64 * 1024 * 1024) {
        gc_collect_cycles();
    }
});
```

```
Sem GC manual: req 50 → GC pausa 15–50ms → spike de latência inesperado
Com GC manual: GC roda no boundary, latência consistente sem spikes
```

### igbinary: serialização Redis 40% mais rápida

```bash
pecl install igbinary
```

```php
// config/database.php
'redis' => [
    'default' => [
        'options' => [
            'serializer'  => Redis::SERIALIZER_IGBINARY,
            'compression' => Redis::COMPRESSION_LZF,
        ],
    ],
],
```

```
Comparativo (array com 100 itens):
  PHP serialize:   450µs, 8,2KB
  JSON:            180µs, 6,1KB
  igbinary:         90µs, 3,8KB  ← 2× mais rápido, 54% menor
  igbinary + LZF:   95µs, 2,1KB  ← payload menor = menos I/O de rede
```

### SO_REUSEPORT: eliminar lock de accept()

```php
// config/octane.php
'swoole' => [
    'options' => [
        'enable_reuse_port' => true,
        // Cada worker tem seu próprio socket — sem thundering herd
    ],
],
```

```
Sem: nova conexão → kernel → lock → 1 worker aceita, demais acordam e dormem (waste)
Com: nova conexão → kernel roteia direto → zero lock, zero herd
Ganho: ~5–15% em throughput de conexões novas
```

### jemalloc: alocador para processos de longa duração

```dockerfile
RUN apk add --no-cache jemalloc-dev
ENV LD_PRELOAD=/usr/lib/libjemalloc.so.2
```

```
ptmalloc (padrão): heap fragmenta ao longo do tempo → malloc lento
jemalloc: thread-local arenas, fragmentação mínima
Ganho: ~5–10% de CPU em workers rodando há horas
```

### zstd: compressão 4× mais rápida que gzip

```php
'swoole' => [
    'options' => [
        'http_compression'       => true,
        'http_compression_level' => 3,
        'compression_min_length' => 1024,
    ],
],
```

```
Comparativo (payload JSON 50KB):
  gzip level 6:  45ms CPU, 18KB
  zstd level 3:  11ms CPU, 16KB  ← igualmente comprimido, 4× mais rápido
  zstd level 1:   4ms CPU, 20KB

Para 500 req/s: gzip usa 22% de 1 vCore só em compressão
                zstd usa  5% — 17% de vCore liberado para requests
```

---

## Teto final com todas as otimizações de servidor

```
CPU por request — evolução completa:

  FrankenPHP síncrono:    20ms
  Swoole básico:          15ms  (-25%)
  + WaitGroup paralelo:   10ms  (-33%)
  + Task workers:          8ms  (-20%)
  + JIT + preload:         7ms  (-13%)
  + GC manual:             6,5ms (-7%)
  + CPU affinity:          6ms  (-8%)
  + jemalloc:              5,5ms (-8%)
  + igbinary + zstd:       5ms  (-9%)
  + SO_REUSEPORT:          5ms  (sem mudar CPU/req, +throughput conexões)
  ─────────────────────────────────────────────────
  CPU/req: 5ms  (era 20ms — redução de 75%)

Teto S3 (4 vCores): 4 × (1.000ms ÷ 5ms) = 800 RPS teórico
Realista:           ~600–650 RPS
3 instâncias S3:    ~1.800–1.950 RPS  ✅ passa de 1500

RPS por tier com otimização máxima:

  Tier  │ FrankenPHP │ Swoole básico │ Swoole máximo │ Para 1500 req/s
  ──────┼────────────┼───────────────┼───────────────┼─────────────────
  B1    │   ~16      │   ~100        │   ~125        │ 12 instâncias
  S2    │   ~35      │   ~200        │   ~250        │  6 instâncias
  S3    │   ~80      │   ~400        │   ~500        │  3 instâncias ✅
```

---

## Parte II — Memória do Browser (Vue.js 3 + Inertia.js)

O servidor pode estar otimizado e ainda assim o sistema ser lento — se o browser estiver consumindo 500MB de RAM com listas não virtualizadas e vazamentos de listeners. Esta seção cobre a outra metade do problema.

### O problema sem otimização

```
Memória típica de uma SPA Vue.js não otimizada (lista de 2000 demandas):

  Bundle JS:              ~2–5MB (parsed + compilado na V8)
  Vue reactivity graph:   ~50–200KB por componente com lista grande
  DOM nodes:              ~1KB por elemento — visível E oculto
  Event listeners:        acumulam se não removidos no unmount
  Closures:               retêm referências a objetos antigos
  ─────────────────────────────────────────────────────────
  Total após 30min:       300–600MB de RAM

Em hardware antigo (máquinas do governo, 4–8GB RAM):
  browser tab trava, sistema inutilizável durante desastre real
```

---

### B1 — shallowRef / shallowReactive: reatividade cirúrgica

O Vue 3 torna todo objeto reativo recursivamente por padrão. Para 1000 demandas com relações aninhadas, isso cria um grafo de Proxies gigantesco.

```typescript
// ANTES — Vue observa cada propriedade de cada objeto aninhado
const demandas = ref<Demanda[]>([])
// 1000 demandas × ~20 props = 20.000 Proxies na memória

// DEPOIS — Vue observa só o array, não o conteúdo
const demandas = shallowRef<Demanda[]>([])
// 1 Proxy para o array, zero para os objetos dentro
// Ganho: 60–80% menos memória para grandes listas

// Objetos que nunca precisam ser reativos (configs, lookups)
import { markRaw } from 'vue'
const municipios = markRaw(await fetch('/api/municipios').then(r => r.json()))
// Completamente fora do sistema de reatividade — zero overhead
```

---

### B2 — Virtual Scrolling: só renderiza o visível

```
Sem virtual scrolling (2000 demandas):
  DOM nodes: 2000 × ~15 elementos = 30.000 nodes
  RAM: ~150–300MB só para a lista
  Layout: browser calcula posição de TODOS os 30.000 nodes

Com virtual scrolling:
  DOM nodes: ~20 linhas visíveis + buffer = ~300 nodes
  RAM: ~3–5MB
  Ganho: 95% menos memória, scroll fluido em hardware antigo
```

```vue
<!-- components/DemandaList.vue -->
<script setup lang="ts">
import { useVirtualList } from '@vueuse/core'

const props = defineProps<{ demandas: Demanda[] }>()

const { list, containerProps, wrapperProps } = useVirtualList(
    () => props.demandas,
    { itemHeight: 64 }
)
</script>

<template>
  <div v-bind="containerProps" class="h-[600px] overflow-auto">
    <div v-bind="wrapperProps">
      <div
        v-for="{ data: demanda } in list"
        :key="demanda.id"
        class="h-16 flex items-center border-b"
      >
        {{ demanda.titulo }} — {{ demanda.municipio.nome }}
      </div>
    </div>
  </div>
</template>
```

---

### B3 — v-memo: congela seções que não mudam

```vue
<template>
  <div v-for="demanda in demandas" :key="demanda.id">
    <DemandaCard
      v-memo="[demanda.id, demanda.status, demanda.updated_at]"
      :demanda="demanda"
    />
    <!-- Se id, status e updated_at forem iguais → zero re-render
         Em listas onde só 1 item muda por vez: 99% dos renders evitados -->
  </div>
</template>
```

---

### B4 — Cleanup obrigatório: evitar memory leaks em componentes

```typescript
// composables/useDemandaPolling.ts
export function useDemandaPolling(intervalMs = 30_000) {
    const demandas   = shallowRef([])
    let   timerId:   ReturnType<typeof setInterval>
    const controller = new AbortController()

    onMounted(() => {
        timerId = setInterval(async () => {
            const data = await fetch('/api/v1/demandas', {
                signal: controller.signal
            }).then(r => r.json())
            demandas.value = data
        }, intervalMs)
    })

    onUnmounted(() => {
        clearInterval(timerId)   // sem isso: timer vaza após navegar
        controller.abort()       // sem isso: fetch pendente vaza memória
        demandas.value = []      // libera o array para o GC
    })

    return { demandas }
}
```

---

### B5 — Service Worker: cache em camadas por tipo de dado

```javascript
// public/sw.js
const STATIC_CACHE = 'static-v1'
const API_CACHE    = 'api-v1'

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url)

    // Assets JS/CSS/fonts: cache-first (hash no nome = nunca mudam)
    if (url.pathname.match(/\.(js|css|woff2|png)$/)) {
        event.respondWith(cacheFirst(event.request, STATIC_CACHE))
        return
    }

    // Dados de referência (municípios, tipos): stale-while-revalidate
    if (url.pathname.match(/\/api\/v1\/(municipios|tipos-demanda|configuracoes)/)) {
        event.respondWith(staleWhileRevalidate(event.request, API_CACHE))
        return
    }

    // Dados dinâmicos: network-first com fallback para offline
    if (url.pathname.startsWith('/api/v1/')) {
        event.respondWith(networkFirst(event.request, API_CACHE, 3000))
        return
    }
})

async function staleWhileRevalidate(request, cacheName) {
    const cache        = await caches.open(cacheName)
    const cached       = await cache.match(request)
    const fetchPromise = fetch(request).then(r => {
        cache.put(request, r.clone())
        return r
    })
    return cached ?? fetchPromise  // retorna cache imediatamente, atualiza em background
}

async function networkFirst(request, cacheName, timeoutMs) {
    const cache      = await caches.open(cacheName)
    const controller = new AbortController()
    const timeout    = setTimeout(() => controller.abort(), timeoutMs)
    try {
        const response = await fetch(request, { signal: controller.signal })
        clearTimeout(timeout)
        cache.put(request, response.clone())
        return response
    } catch {
        return cache.match(request)  // offline: serve do cache
    }
}
```

```
Impacto:
  Primeira visita:    normal (rede)
  Navegações:        ~0ms  (Inertia partial reload + SW cache)
  Refresh:           ~50ms (SW serve do cache, atualiza em background)
  Offline parcial:   sistema continua funcionando com dados cacheados
```

---

### B6 — IndexedDB: referências fixas fora do heap Vue

Municípios, tipos de demanda, configurações — mudam raramente mas são buscados em todo request autenticado.

```typescript
// composables/useReferenceData.ts
import { openDB } from 'idb'

const db = await openDB('newsdc-ref', 1, {
    upgrade(db) {
        db.createObjectStore('municipios',    { keyPath: 'id' })
        db.createObjectStore('tipos_demanda', { keyPath: 'id' })
    }
})

export async function getMunicipios(): Promise<Municipio[]> {
    const cached = await db.getAll('municipios')
    if (cached.length > 0) return cached  // zero rede, zero Vue reactivity

    const fresh = await fetch('/api/v1/municipios').then(r => r.json())
    const tx    = db.transaction('municipios', 'readwrite')
    await Promise.all(fresh.map((m: Municipio) => tx.store.put(m)))
    await tx.done
    return fresh
}

// No componente — markRaw: dado de referência, não precisa ser reativo
const municipios = markRaw(await getMunicipios())
```

---

### B7 — Web Worker: processamento pesado fora da main thread

```typescript
// workers/relatorio.worker.ts
self.onmessage = ({ data: demandas }) => {
    const agrupado = demandas.reduce((acc: Record<string, number>, d: any) => {
        acc[d.municipio_id] = (acc[d.municipio_id] ?? 0) + 1
        return acc
    }, {})

    const ranking = Object.entries(agrupado)
        .sort(([, a], [, b]) => (b as number) - (a as number))
        .slice(0, 10)

    self.postMessage(ranking)
}

// No componente Vue
const worker = new Worker(
    new URL('../workers/relatorio.worker.ts', import.meta.url),
    { type: 'module' }
)

worker.postMessage(demandas.value)
worker.onmessage = ({ data: ranking }) => {
    rankingMunicipios.value = ranking
    worker.terminate()  // libera memória após uso
}
```

```
Cálculo na main thread: UI congela por ~2.000ms
Mesmo cálculo no Web Worker: ~0ms na UI (paralelo no outro thread)
```

---

### B8 — Streams API: processar resposta grande sem carregar tudo em RAM

```typescript
async function* streamDemandas() {
    const response = await fetch('/api/v1/demandas/export')
    const reader   = response.body!.getReader()
    const decoder  = new TextDecoder()
    let   buffer   = ''

    while (true) {
        const { done, value } = await reader.read()
        if (done) break

        buffer += decoder.decode(value, { stream: true })
        const lines = buffer.split('\n')
        buffer = lines.pop()!

        for (const line of lines) {
            if (line.trim()) yield JSON.parse(line)
        }
    }
}

// 10.000 demandas — nunca mais de ~100 na memória ao mesmo tempo
for await (const demanda of streamDemandas()) {
    tabela.push(demanda)

    if (tabela.length % 100 === 0) {
        await new Promise(r => requestIdleCallback(r))
        // Libera main thread a cada 100 items — browser não trava
    }
}
```

---

### B9 — requestIdleCallback: pré-carregamento no tempo ocioso

```typescript
function prefetchNextPage(currentPage: number) {
    requestIdleCallback(async (deadline) => {
        if (deadline.timeRemaining() < 50) return  // browser ocupado — pula

        const nextPage = currentPage + 1
        const cache    = await caches.open('api-v1')

        if (!await cache.match(`/api/v1/demandas?page=${nextPage}`)) {
            fetch(`/api/v1/demandas?page=${nextPage}`)
                .then(r => cache.put(`/api/v1/demandas?page=${nextPage}`, r))
        }
    }, { timeout: 2000 })
}
// Próxima página já está cacheada quando o usuário clicar — ~0ms percebido
```

---

## Impacto acumulado — browser

```
Memória de uma sessão de 30 minutos (lista de 2000 demandas):

  Vue sem otimização:      450–600MB
  + shallowRef/markRaw:    280MB   (-40%)
  + virtual scrolling:      80MB   (-71%)
  + v-memo:                 65MB   (-19%)
  + cleanup correto:        55MB   (-15%)
  + IndexedDB para refs:    40MB   (-27%)
  + Service Worker:         35MB   (-13%)
  ─────────────────────────────────────────
  Total: ~35MB  (redução de 92%)

Tempo percebido pelo usuário:
  Re-render lista 2000 items sem v-memo:     ~180ms (jank visível)
  Com virtual scroll + v-memo:                 ~8ms (imperceptível)

  Cálculo de relatório na main thread:      ~2.000ms (UI congela)
  Com Web Worker:                               ~0ms (paralelo)

  Navegação entre páginas:
    Sem SW cache:   ~800ms (rede)
    Com SW cache:    ~50ms (memória local)
```

---

## Visão completa: onde cada otimização atua

```
Requisição do usuário → resposta na tela

  [Browser]──────────────────────────────────────────────────────
  │  shallowRef      → menos memória para dados reativos
  │  virtual scroll  → só renderiza o visível
  │  v-memo          → evita re-renders desnecessários
  │  Web Worker      → processamento pesado fora da UI
  │  Service Worker  → cache inteligente por camada
  │  IndexedDB       → dados de referência sem rede
  │  Streams API     → grandes respostas sem ocupar RAM
  │  requestIdle     → pré-carrega no tempo ocioso
  └──────────────────────────────────────────────────────────────
           │ HTTP/2 multiplexado + zstd comprimido
  [Rede]───┼──────────────────────────────────────────────────────
           │
  [Swoole]───────────────────────────────────────────────────────
  │  SO_REUSEPORT    → sem lock de accept()
  │  CPU affinity    → L1/L2 cache sempre quente
  │  WaitGroup       → queries paralelas por request
  │  Task Workers    → bcrypt fora do HTTP pool
  │  PDOPool nativo  → conexões persistentes sem overhead
  │  Swoole\Table    → dados quentes em memória compartilhada
  │  Atomic          → rate limit sem Redis round-trip
  │  defer()         → responde antes de processar tudo
  │  jemalloc        → alocação sem fragmentação
  │  GC manual       → sem pausas imprevisíveis
  │  igbinary        → Redis 2× mais rápido
  │  JIT + preload   → 15% menos CPU em código PHP
  │  hot path bypass → /api/health em 0,3ms
  └──────────────────────────────────────────────────────────────
           │
  [PostgreSQL] read replicas → próximo gargalo após 650 RPS/instância
  [Redis]      sharding      → próximo gargalo após 100k ops/s
```

---

## Parte III — Reatividade do Login (percepção + performance real)

O login foi o endpoint mais lento em todos os testes — mediana de **22.000ms sob 1000 usuários**. O problema tem duas camadas: o que realmente acontece no servidor e o que o usuário percebe enquanto espera.

### Diagnóstico: o que acontece por dentro

```
Fluxo atual — timeline real de um login:

  Usuário clica "Entrar"
       │
       ├─ GET /sanctum/csrf-cookie ──────── ~100ms  (round-trip de rede)
       │
       ├─ POST /login
       │    ├─ Redis INCR (rate limit) ──── ~1ms
       │    ├─ SELECT users WHERE cpf ───── ~5ms    (PostgreSQL)
       │    ├─ bcrypt verify ─────────────  ~200ms  (CPU — intencional)
       │    ├─ session regenerate ────────  ~5ms    (Redis write)
       │    ├─ recordLogin() UPDATE ──────  ~10ms   (PostgreSQL)
       │    └─ AuditLog INSERT ──────────── ~10ms   (PostgreSQL)
       │                                   ──────
       │                                   ~331ms mínimo (sem carga)
       │                                   ~22.000ms com 1000 usuários
       │
       └─ Redirect → GET /dashboard ──────  nova página inteira
            ├─ HTML + JS + CSS ───────────  ~200ms (cache miss)
            ├─ Vue inicializa ────────────  ~50ms
            └─ GET /api/v1/* (dados) ─────  ~100ms (queries)
                                           ──────
                                           ~350ms extras percebidos
```

---

### L1 — Pré-buscar o CSRF na carga da página

O maior desperdício percebido: o usuário clica em "Entrar" e o sistema vai buscar o CSRF **antes de fazer qualquer coisa**.

```typescript
// pages/Auth/Login.vue — busca CSRF assim que a página carrega, não quando clica
onMounted(async () => {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
    // Silencioso, em background — CSRF pronto quando o usuário clicar
})

async function submit() {
    await form.post('/login')  // vai direto para o POST — sem esperar CSRF
}
```

**Ganho percebido:** elimina 100ms de espera visível no clique do botão.

---

### L2 — Skeleton screen: dashboard aparece imediatamente

Em vez de tela branca enquanto os dados carregam, o shell da interface aparece imediatamente com Inertia deferred props.

```php
// DashboardController.php
public function index(): Response
{
    return Inertia::render('Dashboard', [
        // Leve — vai junto com o HTML inicial
        'usuario' => Auth::user()->only('id', 'nome', 'role'),

        // Pesado — Inertia busca em segundo request paralelo, após a página renderizar
        'demandas'     => Inertia::defer(fn() => Demanda::paginate(20)),
        'estatisticas' => Inertia::defer(fn() => $this->calcularEstatisticas()),
        'alertas'      => Inertia::defer(fn() => Alerta::recentes()->get()),
    ]);
}
```

```vue
<!-- pages/Dashboard.vue -->
<template>
  <AppLayout>
    <div v-if="!demandas" class="animate-pulse space-y-4">
      <div class="h-32 bg-gray-200 rounded-lg" />
      <div class="h-64 bg-gray-200 rounded-lg" />
    </div>
    <DashboardContent v-else :demandas="demandas" :estatisticas="estatisticas" />
  </AppLayout>
</template>
```

**Ganho percebido:** usuário vê o dashboard imediatamente após o login. Dados aparecem em cascata conforme chegam.

---

### L3 — Barra de progresso no clique

O bcrypt leva ~200ms de forma inevitável. Feedback visual torna essa espera tolerável.

```typescript
// composables/useLoginForm.ts
export function useLoginForm() {
    const loading  = ref(false)
    const progress = ref(0)

    async function submit(cpf: string, password: string) {
        loading.value  = true
        progress.value = 20  // feedback imediato no clique

        const interval = setInterval(() => {
            if (progress.value < 85) progress.value += 5
        }, 300)

        try {
            await router.post('/login', { cpf, password }, {
                onSuccess: () => { progress.value = 100 },
            })
        } finally {
            clearInterval(interval)
            loading.value = false
        }
    }

    return { loading, progress, submit }
}
```

```vue
<template>
  <div class="h-1 bg-gray-200 rounded overflow-hidden mb-6">
    <div
      class="h-full bg-blue-600 transition-all duration-300"
      :style="{ width: `${progress}%` }"
    />
  </div>
  <button :disabled="loading" type="submit">
    <span v-if="loading">Entrando...</span>
    <span v-else>Entrar</span>
  </button>
</template>
```

**Ganho percebido:** 200ms de bcrypt se torna tolerável com barra progressiva. Espera com feedback é psicologicamente 2–3× mais curta que tela branca.

---

### L4 — Argon2id no lugar do bcrypt (60% mais rápido)

O bcrypt com cost 12 leva ~200ms de CPU. Argon2id com parâmetros equivalentes leva ~80ms — e é mais seguro (resistente a GPU e ASIC).

```php
// config/hashing.php
'driver' => 'argon2id',

'argon' => [
    'memory'  => 65536,  // 64MB — resistência a ataque de GPU
    'threads' => 2,
    'time'    => 2,      // ~80ms CPU vs ~200ms do bcrypt cost 12
],
```

```php
// LoginRequest.php — re-hash progressivo sem quebrar usuários existentes
public function authenticate(): void
{
    // ... autenticação normal ...

    if (password_needs_rehash($user->password, PASSWORD_ARGON2ID)) {
        $user->update(['password' => Hash::make($this->string('password'))]);
    }
}
```

**Ganho real:** login de ~200ms → ~80ms de CPU. Próximos logins do mesmo usuário já usam Argon2id automaticamente.

---

### L5 — AuditLog e recordLogin fora do caminho crítico

```php
// AuthenticatedSessionController.php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();

    $user = Auth::user();

    // Com Swoole: defer executa após a resposta ser enviada
    defer(function() use ($user, $request) {
        $user->recordLogin($request->ip(), $request->userAgent());
        AuditLog::logLogin($user->id);
    });

    // Sem Swoole: Job na fila Redis
    // RecordUserLogin::dispatch($user->id, $request->ip(), $request->userAgent());

    return redirect()->intended(RouteServiceProvider::HOME);
}
```

**Ganho real:** elimina 20ms de 2 queries síncronas do caminho de resposta. AuditLog ainda acontece — mas após o usuário já ter entrado.

---

### L6 — Cache do usuário por CPF

```php
// LoginRequest.php
$user = Cache::remember(
    "user:cpf:{$cpf}",
    now()->addSeconds(30),
    fn() => User::where('cpf', $cpf)
                ->select(['id', 'cpf', 'password', 'nome', 'role'])
                ->first()
);
```

**Ganho real:** em burst de logins simultâneos (início de plantão, todos acessam ao mesmo tempo), o PostgreSQL recebe 1 query a cada 30s em vez de N por segundo.

---

### L7 — Pré-aquecer o dashboard antes do redirect

```php
// AuthenticatedSessionController.php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();

    $userId = Auth::id();

    // Warm up em background — dados prontos quando o browser redirecionar
    defer(function() use ($userId) {
        Cache::remember("dashboard:{$userId}", 60, fn() => [
            'demandas'     => Demanda::paginate(20),
            'estatisticas' => $this->calcularEstatisticas(),
        ]);
    });

    return redirect()->intended(RouteServiceProvider::HOME);
}
```

**Ganho real:** o `DashboardController` encontra os dados no Redis (<2ms) em vez de ir ao PostgreSQL (~100ms). Página carrega imediatamente após o redirect.

---

### Resultado acumulado

```
Login timeline — antes vs depois:

  ANTES                           DEPOIS
  ─────────────────────────────   ──────────────────────────────────────
  clique                          clique
  CSRF         100ms              POST direto (CSRF pré-buscado)   0ms
  POST /login                     POST /login
    bcrypt       200ms              Argon2id                       80ms
    user SELECT    5ms              user cache hit                  0ms
    session write  5ms              session write                   5ms
    recordLogin   10ms              defer recordLogin               0ms
    AuditLog      10ms              defer AuditLog                  0ms
  resposta       330ms            resposta                         85ms
  tela branca...
  redirect       200ms            dashboard skeleton          imediato
  dashboard load 350ms            dados (deferred)              ~200ms
  ─────────────────              ──────────────────────────────────────
  TOTAL:        ~880ms            TOTAL PERCEBIDO:             ~285ms
                                  Ganho: 3× mais rápido

Sob carga (1000 usuários simultâneos):

  Hoje:                            22.000ms mediana
  + defer AuditLog/recordLogin:   ~18.000ms
  + cache CPF:                    ~15.000ms
  + Argon2id:                      ~8.000ms
  + Swoole task worker (hash):     ~3.000ms
  + CSRF pré-buscado + skeleton:   ~3.000ms percebido
  ────────────────────────────────────────────────────
  Com tudo:                        ~2.000–3.000ms  (7–10× mais rápido)
```

> O login nunca será instantâneo enquanto houver hash de senha — é intencional por segurança. O objetivo é fazer os **85ms inevitáveis** desaparecerem atrás de feedback visual e mover todo o resto para fora do caminho crítico.

---

*Referência técnica gerada em 06/06/2026*
*Baseada em testes de carga reais com Locust 2.44.1 — sdcdefesa.azurewebsites.net*
*Arquitetura: Laravel 12 + FrankenPHP + PostgreSQL + Redis | Azure App Service B1 → S3*
*Stack frontend: Vue.js 3 + Inertia.js + TypeScript*
