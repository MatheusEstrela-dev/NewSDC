# Swoole hooks rollout e rollback

Este runbook cobre apenas a ativacao segura de `OCTANE_HOOK_FLAGS_ENABLED` e a
validacao do login com `Hash::check` fora do HTTP worker.

## Defaults seguros

- `OCTANE_HOOK_FLAGS_ENABLED=false` continua sendo o default.
- `task_enable_coroutine=false` deve permanecer assim por compatibilidade entre
  Octane 2.13 e Swoole 6.
- `DB_PERSISTENT=false` deve permanecer assim sob Octane/Swoole.
- O hook de cURL segue excluido em `config/octane.php`.

## Diagnostico

Rode no container/app:

```bash
php artisan octane:diagnostics
php artisan octane:diagnostics --json
```

Campos obrigatorios:

- `octane.server`: deve ser `swoole` no alvo.
- `octane.hook_flags_effective`: `0` com hooks OFF; maior que `0` com hooks ON.
- `octane.task_worker_num`: deve ser maior que `0` para offload de senha.
- `octane.task_enable_coroutine`: deve ser `false`.
- `database.db_persistent`: deve ser `false`.
- `pools.pgsql` e `pools.redis`: mostram bindings/pools registrados no processo.

## Ativacao em canario/Azure

1. Confirmar imagem com extensao Swoole carregada.
2. Confirmar `OCTANE_SERVER=swoole`.
3. Confirmar `DB_PERSISTENT=false`.
4. Ajustar sizing:
   - `SWOOLE_PG_POOL_SIZE * OCTANE_WORKERS * instancias <= max_connections - reserva`.
   - manter `OCTANE_REDIS_POOL_SIZE` alinhado com a concorrencia esperada.
5. Setar `OCTANE_HOOK_FLAGS_ENABLED=true`.
6. Reiniciar workers/app service.
7. Rodar `php artisan octane:diagnostics --json`.
8. Executar smoke de leitura + login.

## Smoke local

Baseline:

```bash
OCTANE_HOOK_FLAGS_ENABLED=false php artisan octane:start --server=swoole
```

Alvo:

```bash
OCTANE_HOOK_FLAGS_ENABLED=true php artisan octane:start --server=swoole
```

Validar:

- `/health` responde 200.
- Login valido autentica.
- Login invalido retorna erro de autenticacao, nao 500.
- Requests concorrentes com cache/session nao geram `Socket ... bound to another coroutine`.
- Queries concorrentes nao vazam tenant/transacao entre requests.

## Login sob carga

Use massa com CPFs diferentes para nao medir apenas throttle/cache de um unico CPF.
Rodar degraus curtos:

- 50 usuarios
- 100 usuarios
- 200 usuarios

Parar se p95 passar de 10s ou se aparecerem erros 5xx. Verificar se requests de
leitura continuam respondendo enquanto logins estao em massa.

## Rollback

Sem redeploy de imagem:

1. Setar `OCTANE_HOOK_FLAGS_ENABLED=false`.
2. Reiniciar workers/app service.
3. Confirmar `hook_flags_effective=0` em `php artisan octane:diagnostics --json`.
4. Repetir smoke de login e `/health`.

O offload de senha continua com fallback sincrono fora de task workers, entao o
rollback dos hooks nao quebra login.
