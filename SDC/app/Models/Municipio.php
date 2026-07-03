<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Municipio extends Model
{
    use HasFactory;

    private const CATALOGO_CACHE_KEY = 'municipios:catalogo';

    /**
     * Memo em processo (por worker Octane) na frente do cache Redis — mesmo
     * padrao do SetTenant::$tenantMemo. Workers residentes servem o catalogo
     * (~200KB) direto da RAM do processo, sem round-trip/deserializacao por
     * request. TTL menor que o do Redis: a janela de stale continua governada
     * pelo cache compartilhado.
     */
    private static ?Collection $catalogoMemo = null;

    private static float $catalogoMemoExp = 0.0;

    private const CATALOGO_MEMO_TTL = 300;

    /**
     * Catalogo completo (id, nome, uf) ordenado por nome. Camadas: memo por
     * worker (300s) -> Redis (24h) -> Postgres. E a lista read-only usada em
     * selects de varios modulos (PMDA, TDAP, PAE, reset de senha); municipios
     * mudam raramente. Retorna Collection de arrays (leve para cache/Inertia
     * e sem hidratar 853 models).
     */
    public static function catalogo(): Collection
    {
        $now = microtime(true);

        if (self::$catalogoMemo !== null && self::$catalogoMemoExp > $now) {
            return self::$catalogoMemo;
        }

        $catalogo = Cache::remember(self::CATALOGO_CACHE_KEY, 86400, function () {
            return static::query()
                ->orderBy('nome')
                ->get(['id', 'nome', 'uf'])
                ->map(fn (self $m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf]);
        });

        self::$catalogoMemo = $catalogo;
        self::$catalogoMemoExp = $now + self::CATALOGO_MEMO_TTL;

        return $catalogo;
    }

    /**
     * Invalida o catalogo cacheado (chamar apos alterar municipios). Zera o
     * Redis e o memo DESTE worker; os memos dos demais workers expiram pelo
     * TTL de 300s.
     */
    public static function limparCatalogo(): void
    {
        Cache::forget(self::CATALOGO_CACHE_KEY);
        self::$catalogoMemo = null;
        self::$catalogoMemoExp = 0.0;
    }

    protected $table = 'municipios';

    protected $fillable = [
        'codigo_ibge',
        'nome',
        'uf',
        'regiao',
        'mesorregiao',
        'microrregiao',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'codigo_ibge' => 'string',
        'nome' => 'string',
        'uf' => 'string',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
