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

    private const CISTERNA_CACHE_KEY = 'municipios:cisterna_habilitados';

    private const CISTERNA_MEMO_TTL = 300;

    private static ?Collection $cisternaMemo = null;

    private static float $cisternaMemoExp = 0.0;

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

    /**
     * Municipios habilitados no Projeto Cisterna, ordenados por nome.
     *
     * O flag mora em `cedec_municipio.at_cisterna` — a mesma tabela que o
     * ImportCedecMunicipioCommand documenta como a ponte de municipio do
     * legado (`cedec_municipio.Codmundv = municipios.codigo_ibge`). Nao
     * duplicamos o flag em `municipios`: a fonte de verdade continua uma so.
     *
     * O legado resolvia isso com Municipio::where('at_cisterna', 1) em nove
     * pontos diferentes dos controllers.
     *
     * Camadas de cache identicas a catalogo(): memo por worker (300s) ->
     * Redis (24h) -> Postgres. A lista muda raramente e alimenta select de
     * praticamente toda tela do modulo.
     *
     * @return Collection<int, array{id: int, nome: string, uf: string}>
     */
    public static function habilitadosCisterna(): Collection
    {
        $now = microtime(true);

        if (self::$cisternaMemo !== null && self::$cisternaMemoExp > $now) {
            return self::$cisternaMemo;
        }

        $lista = Cache::remember(self::CISTERNA_CACHE_KEY, 86400, function (): Collection {
            return static::query()
                ->join('cedec_municipio', 'cedec_municipio.Codmundv', '=', 'municipios.codigo_ibge')
                ->where('cedec_municipio.at_cisterna', 1)
                ->orderBy('municipios.nome')
                ->get(['municipios.id', 'municipios.nome', 'municipios.uf'])
                ->map(fn (self $m): array => ['id' => (int) $m->id, 'nome' => $m->nome, 'uf' => $m->uf])
                ->values();
        });

        self::$cisternaMemo = $lista;
        self::$cisternaMemoExp = $now + self::CISTERNA_MEMO_TTL;

        return $lista;
    }

    /**
     * Somente os ids, para uso em whereIn de escopo por perfil.
     *
     * @return array<int, int>
     */
    public static function idsHabilitadosCisterna(): array
    {
        return static::habilitadosCisterna()->pluck('id')->all();
    }

    /**
     * Invalida a lista cacheada. Chamar depois de alterar cedec_municipio.
     */
    public static function esquecerHabilitadosCisterna(): void
    {
        self::$cisternaMemo = null;
        self::$cisternaMemoExp = 0.0;
        Cache::forget(self::CISTERNA_CACHE_KEY);
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
