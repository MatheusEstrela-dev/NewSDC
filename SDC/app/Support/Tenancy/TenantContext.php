<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Swoole\Coroutine;

/**
 * Armazena o tenant resolvido da request em escopo SEGURO PARA COROUTINE.
 *
 * Sob Swoole com enable_coroutine + SWOOLE_HOOK_ALL, varias requests de tenants
 * distintos intercalam no mesmo worker. Guardar o tenant no container
 * (app()->instance('tenant')) ou em config global vaza entre coroutines: a
 * request A cede a CPU num I/O, a B sobrescreve o estado, e a A volta lendo o
 * tenant da B. Aqui o tenant vive no contexto da coroutine corrente
 * (Coroutine::getContext()), isolado por definicao e limpo quando a coroutine
 * termina.
 *
 * Fora de coroutine (RoadRunner / CLI / fila), cai num campo estatico: nesse
 * modelo so existe uma request por vez no worker, entao o reset por request
 * (SetTenant grava em todo request) basta.
 */
final class TenantContext
{
    private const KEY = '__sdc_tenant';

    private static ?Tenant $fallback = null;

    public static function set(?Tenant $tenant): void
    {
        if (self::emCoroutine()) {
            Coroutine::getContext()[self::KEY] = $tenant;

            return;
        }

        self::$fallback = $tenant;
    }

    public static function get(): ?Tenant
    {
        if (self::emCoroutine()) {
            return Coroutine::getContext()[self::KEY] ?? null;
        }

        return self::$fallback;
    }

    public static function clear(): void
    {
        if (self::emCoroutine()) {
            unset(Coroutine::getContext()[self::KEY]);

            return;
        }

        self::$fallback = null;
    }

    private static function emCoroutine(): bool
    {
        // extension_loaded faz curto-circuito sob RoadRunner (sem ext-swoole),
        // evitando referenciar a classe Coroutine. getCid() retorna -1 fora de
        // qualquer coroutine.
        return extension_loaded('swoole') && Coroutine::getCid() > 0;
    }
}
