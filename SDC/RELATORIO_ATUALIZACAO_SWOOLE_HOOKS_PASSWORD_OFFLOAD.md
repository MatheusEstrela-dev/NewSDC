# Relatorio da atualizacao Swoole hooks + password offload

## Contexto

Esta atualizacao implementa duas frentes restritas de performance para Laravel
Octane com Swoole:

1. Preparar a ativacao segura de `OCTANE_HOOK_FLAGS_ENABLED`.
2. Mover a verificacao de senha (`Hash::check`) para task workers quando o
   processo estiver rodando em Swoole com task workers reais.

O default seguro foi mantido: `OCTANE_HOOK_FLAGS_ENABLED=false`.

## PasswordVerifier

Foi criado `App\Support\Security\PasswordVerifier` com API:

```php
verify(string $plain, string $hash): bool
```

Comportamento:

- Em Swoole + task workers disponiveis, executa `Hash::check` via
  `Concurrency::tasks()`.
- Fora de Swoole, em testes, queue workers, FrankenPHP/RoadRunner, CLI ou dentro
  de transacao aberta, faz fallback para `Hash::check` sincrono.
- Se o task worker falhar ou estourar timeout, registra warning e degrada para
  verificacao sincrona, evitando erro 500 no login.
- O payload de task retorna `1` ou `0`, nunca `false`, porque
  `Concurrency::tasks()` trata `false` como task nao concluida.

## Login

`LoginRequest::authenticate()` passou a usar `PasswordVerifier`.

Comportamentos preservados:

- cache de usuario por CPF por 30 segundos;
- validacao de usuario ativo/status;
- evento `Illuminate\Auth\Events\Failed` para auditoria de falha;
- rate limit de login;
- rehash progressivo para o driver atual;
- `Auth::login()` e limpeza do throttle em sucesso.

## Concurrency

`App\Support\Concurrency\Concurrency` ganhou o metodo publico:

```php
taskWorkersAvailable(): bool
```

Ele expõe a mesma regra ja usada internamente para decidir se task workers
reais estao disponiveis:

- `octane.server=swoole`;
- binding de `Swoole\Http\Server` presente no processo;
- `task_worker_num > 0`;
- nenhuma transacao de banco aberta.

Isso evita que o `PasswordVerifier` caia na estrategia de coroutines para uma
tarefa CPU-bound.

## Hooks Swoole e pools

O default de hooks continua desligado no codigo e no `.env.example`:

```env
OCTANE_HOOK_FLAGS_ENABLED=false
```

Tambem foi documentado:

```env
DB_PERSISTENT=false
SWOOLE_PG_POOL_TIMEOUT=3.0
OCTANE_TASK_WORKERS=4
OCTANE_TASK_WAIT_MS=5000
OCTANE_REDIS_POOL_SIZE=16
OCTANE_REDIS_POOL_TIMEOUT=3.0
```

O `SwoolePdoPool` passou a ter timeout configuravel no `acquire()`. Antes, se o
pool ficasse esgotado, a coroutine poderia aguardar indefinidamente. Agora o
pool lanca excecao tratavel:

```txt
SwoolePdoPool esgotado (timeout no acquire).
```

Foram adicionados metodos de diagnostico aos pools:

- `capacity()`;
- `created()`;
- `available()`;
- `timeout()`.

## Diagnostico Octane

Foi criado o comando:

```bash
php artisan octane:diagnostics
php artisan octane:diagnostics --json
```

O snapshot reporta:

- `octane.server`;
- extensao Swoole carregada;
- `hook_flags` efetivo;
- `task_worker_num`;
- `task_enable_coroutine`;
- `DB_PERSISTENT`;
- estado/configuracao do pool pgsql;
- estado/configuracao dos pools Redis.

Esse diagnostico foi mantido como comando artisan para evitar expor detalhes
internos em endpoint publico.

## Runbook de rollout e rollback

Foi criado `docs/SWOOLE_HOOKS_ROLLOUT.md` com:

- defaults seguros;
- campos obrigatorios do diagnostico;
- passos para ativacao em canario/Azure;
- smoke local com hooks OFF e ON;
- roteiro de login sob carga;
- rollback sem redeploy de imagem.

Rollback operacional:

```env
OCTANE_HOOK_FLAGS_ENABLED=false
```

Depois, reiniciar workers/app service e validar:

```bash
php artisan octane:diagnostics --json
```

## Testes adicionados

`tests/Unit/PasswordVerifierTest.php`

- verifica senha valida e invalida no fallback sincrono;
- garante que retorno `0` do task worker vira senha invalida sem usar `false`;
- garante fallback sincrono quando a task falha.

`tests/Feature/OctaneDiagnosticsCommandTest.php`

- valida a saida JSON do comando `octane:diagnostics --json`;
- cobre campos obrigatorios de server, hook flags, task workers e pools.

## Comandos executados

Passaram:

```bash
php -l app\Support\Security\PasswordVerifier.php
php -l app\Console\Commands\OctaneDiagnosticsCommand.php
php -l app\Support\Octane\OctaneDiagnostics.php
php -l app\Support\Database\SwoolePdoPool.php
php -l app\Support\Redis\CoroutineRedisManager.php
php -l app\Http\Requests\Auth\LoginRequest.php
php -l tests\Unit\PasswordVerifierTest.php
php -l tests\Feature\OctaneDiagnosticsCommandTest.php
php artisan test --filter=PasswordVerifierTest
php artisan test --filter=OctaneDiagnosticsCommandTest
php artisan octane:diagnostics --json
git diff --check
```

Observacoes:

- `php artisan test` completo foi iniciado, mas ficou sem progresso apos os
  testes novos passarem; a execucao foi interrompida para nao deixar processo
  preso. Sinal provavel: dependencia dos testes legados em banco/local state.
- Smoke Swoole OFF/ON nao foi executado neste PHP local porque a extensao Swoole
  nao esta carregada (`swoole=no`).
- `php artisan l5-swagger:generate` nao foi executado porque nao houve alteracao
  em anotacoes Swagger.

## Arquivos principais alterados

- `.env.example`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Support/Security/PasswordVerifier.php`
- `app/Support/Concurrency/Concurrency.php`
- `app/Support/Database/SwoolePdoPool.php`
- `app/Providers/OctaneServiceProvider.php`
- `app/Support/Redis/CoroutineRedisManager.php`
- `app/Support/Redis/SwooleRedisPool.php`
- `app/Support/Octane/OctaneDiagnostics.php`
- `app/Console/Commands/OctaneDiagnosticsCommand.php`
- `docs/SWOOLE_HOOKS_ROLLOUT.md`
- `tests/Unit/PasswordVerifierTest.php`
- `tests/Feature/OctaneDiagnosticsCommandTest.php`

## Proximo passo recomendado

Executar load test em ambiente com Swoole real e massa de CPFs distintos:

- 50 usuarios;
- 100 usuarios;
- 200 usuarios.

Parar se p95 passar de 10s ou se houver 5xx. Validar, em paralelo, que endpoints
de leitura continuam respondendo enquanto logins em massa executam hash nos task
workers.
