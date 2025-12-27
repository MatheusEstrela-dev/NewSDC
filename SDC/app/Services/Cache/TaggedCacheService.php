<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Serviço de Cache com Tags
 *
 * Baseado no papiro2.md - "Memória Muscular"
 *
 * Problema: Limpar cache inteiro quando atualiza um município é desperdício
 * Solução: Cache::tags(['municipios'])->flush() limpa só o que importa
 *
 * Uso:
 * ```php
 * // Cachear
 * TaggedCacheService::remember('municipios', 'lista_completa', 3600, function() {
 *     return Municipio::all();
 * });
 *
 * // Limpar apenas cache de municípios
 * TaggedCacheService::flush('municipios');
 * ```
 */
class TaggedCacheService
{
    /**
     * Tags disponíveis no sistema
     */
    public const TAG_MUNICIPIOS = 'municipios';
    public const TAG_COBRADE = 'cobrade';
    public const TAG_USERS = 'users';
    public const TAG_ROLES = 'roles';
    public const TAG_PERMISSIONS = 'permissions';
    public const TAG_DECRETOS = 'decretos';
    public const TAG_DANOS = 'danos';
    public const TAG_CHAMADOS = 'chamados';
    public const TAG_SETTINGS = 'settings';

    /**
     * Cacheia com tag específica
     *
     * @param string|array $tags Tag(s) para organizar o cache
     * @param string $key Chave única do cache
     * @param int $ttl Tempo de vida em segundos
     * @param callable $callback Função que retorna o valor
     * @return mixed
     */
    public static function remember(string|array $tags, string $key, int $ttl, callable $callback): mixed
    {
        $tags = is_array($tags) ? $tags : [$tags];

        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Limpa TODOS os caches de uma tag específica
     *
     * Exemplo: TaggedCacheService::flush('municipios')
     * Limpa: lista_completa, lista_por_estado, municipio_123, etc
     */
    public static function flush(string|array $tags): void
    {
        $tags = is_array($tags) ? $tags : [$tags];

        Cache::tags($tags)->flush();
    }

    /**
     * Remove um item específico do cache
     */
    public static function forget(string|array $tags, string $key): void
    {
        $tags = is_array($tags) ? $tags : [$tags];

        Cache::tags($tags)->forget($key);
    }

    /**
     * Pega valor do cache (sem callback)
     */
    public static function get(string|array $tags, string $key, mixed $default = null): mixed
    {
        $tags = is_array($tags) ? $tags : [$tags];

        return Cache::tags($tags)->get($key, $default);
    }

    /**
     * Grava valor no cache
     */
    public static function put(string|array $tags, string $key, mixed $value, int $ttl): void
    {
        $tags = is_array($tags) ? $tags : [$tags];

        Cache::tags($tags)->put($key, $value, $ttl);
    }

    /**
     * Verifica se uma chave existe no cache
     */
    public static function has(string|array $tags, string $key): bool
    {
        $tags = is_array($tags) ? $tags : [$tags];

        return Cache::tags($tags)->has($key);
    }

    // ========================================================================
    // Helpers Específicos para Dados Estáticos (Performance)
    // ========================================================================

    /**
     * Cache de municípios (dados que mudam raramente)
     */
    public static function municipios(string $key, callable $callback, int $ttl = 86400): mixed
    {
        return self::remember(self::TAG_MUNICIPIOS, $key, $ttl, $callback);
    }

    /**
     * Cache de códigos COBRADE (dados que quase nunca mudam)
     */
    public static function cobrade(string $key, callable $callback, int $ttl = 604800): mixed // 7 dias
    {
        return self::remember(self::TAG_COBRADE, $key, $ttl, $callback);
    }

    /**
     * Cache de settings do sistema (configurações)
     */
    public static function settings(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return self::remember(self::TAG_SETTINGS, $key, $ttl, $callback);
    }

    /**
     * Cache de usuários (com TTL menor por segurança)
     */
    public static function users(string $key, callable $callback, int $ttl = 1800): mixed // 30 min
    {
        return self::remember(self::TAG_USERS, $key, $ttl, $callback);
    }
}
