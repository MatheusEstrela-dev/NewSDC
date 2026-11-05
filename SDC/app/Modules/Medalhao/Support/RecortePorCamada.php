<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Support;

use Illuminate\Database\Query\Builder;

/**
 * Recorte espacial das matviews de mapa pelas feicoes de uma camada de risco.
 *
 * Le camadas_gold.geo_feicao_mapa, e nao o silver de outro dominio: um dominio
 * so consome o gold do outro. O gold so tem camada aprovada, que e a unica que
 * o seletor de camadas do mapa oferece.
 *
 * EXISTS e nao JOIN: ponto dentro de duas feicoes da mesma camada apareceria
 * duplicado no JOIN, e o mapa desenharia dois marcadores no mesmo lugar.
 * ST_Intersects e nao ST_Contains: ponto exatamente sobre a divisa entra --
 * num sistema de Defesa Civil, errar incluindo e melhor que errar excluindo.
 */
final class RecortePorCamada
{
    public static function aplicar(Builder $query, ?int $camadaGeoId, string $tabelaMapa): void
    {
        if ($camadaGeoId === null) {
            return;
        }

        $feicoes = TabelasGeo::feicaoMapa();

        $query->whereRaw(
            "EXISTS (SELECT 1 FROM {$feicoes} f
                      WHERE f.camada_id = ? AND ST_Intersects(f.geom, {$tabelaMapa}.geom))",
            [$camadaGeoId]
        );
    }
}
