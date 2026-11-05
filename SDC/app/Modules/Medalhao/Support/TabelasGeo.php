<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Support;

use App\Support\Database\Geo\DominioGeo;

/**
 * Nomes qualificados das tabelas do geoespacial, montados a partir do
 * DominioGeo. Existe para nenhum repositorio carregar literal de schema: se o
 * nome de uma tabela mudar, muda aqui.
 */
final class TabelasGeo
{
    public static function dominioDoGrupo(string $grupo): DominioGeo
    {
        return DominioGeo::doGrupo($grupo);
    }

    /** @return list<DominioGeo> dominios que de fato recebem ingestao */
    public static function dominiosComIngestao(): array
    {
        return DominioGeo::comIngestao();
    }

    public static function ingestaoBruta(DominioGeo $dominio): string
    {
        return $dominio->tabela('bronze', 'ingestao_bruta');
    }

    public static function execucoes(DominioGeo $dominio): string
    {
        return $dominio->tabela('ingestao', 'execucoes');
    }

    public static function cursores(DominioGeo $dominio): string
    {
        return $dominio->tabela('ingestao', 'cursores');
    }

    // Meteorologia

    public static function estacoesInmet(): string
    {
        return DominioGeo::Meteorologia->tabela('silver', 'estacoes_inmet');
    }

    public static function estacoesCemaden(): string
    {
        return DominioGeo::Meteorologia->tabela('silver', 'estacoes_cemaden');
    }

    public static function leiturasInmet(): string
    {
        return DominioGeo::Meteorologia->tabela('silver', 'leituras_inmet');
    }

    public static function leiturasCemaden(): string
    {
        return DominioGeo::Meteorologia->tabela('silver', 'leituras_cemaden');
    }

    public static function inmetMapa(): string
    {
        return DominioGeo::Meteorologia->tabela('gold', 'inmet_mapa');
    }

    public static function inmetEstatisticas(): string
    {
        return DominioGeo::Meteorologia->tabela('gold', 'inmet_estatisticas');
    }

    public static function cemadenMapa(): string
    {
        return DominioGeo::Meteorologia->tabela('gold', 'cemaden_mapa');
    }

    public static function cemadenEstatisticas(): string
    {
        return DominioGeo::Meteorologia->tabela('gold', 'cemaden_estatisticas');
    }

    // Sismo

    public static function sismos(): string
    {
        return DominioGeo::Sismo->tabela('silver', 'sismos');
    }

    public static function sismosMapa(): string
    {
        return DominioGeo::Sismo->tabela('gold', 'sismos_mapa');
    }

    public static function sismosEstatisticas(): string
    {
        return DominioGeo::Sismo->tabela('gold', 'sismos_estatisticas');
    }

    // Camadas

    public static function camadas(): string
    {
        return DominioGeo::Camadas->tabela('silver', 'geo_camadas');
    }

    public static function feicoes(): string
    {
        return DominioGeo::Camadas->tabela('silver', 'geo_feicoes');
    }

    public static function feicaoMapa(): string
    {
        return DominioGeo::Camadas->tabela('gold', 'geo_feicao_mapa');
    }

    public static function camadaMunicipios(): string
    {
        return DominioGeo::Camadas->tabela('gold', 'geo_camada_municipios');
    }

    // Referencia

    public static function municipios(): string
    {
        return DominioGeo::REFERENCIA.'.municipios';
    }
}
