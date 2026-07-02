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
     * Catalogo completo (id, nome, uf) ordenado por nome, cacheado por 24h.
     * E a lista read-only usada em selects de varios modulos (PMDA, TDAP,
     * PAE, reset de senha); municipios mudam raramente. Retorna Collection
     * de arrays (leve para cache/Inertia e sem hidratar 853 models).
     */
    public static function catalogo(): Collection
    {
        return Cache::remember(self::CATALOGO_CACHE_KEY, 86400, function () {
            return static::query()
                ->orderBy('nome')
                ->get(['id', 'nome', 'uf'])
                ->map(fn (self $m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf]);
        });
    }

    /** Invalida o catalogo cacheado (chamar apos alterar municipios). */
    public static function limparCatalogo(): void
    {
        Cache::forget(self::CATALOGO_CACHE_KEY);
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
