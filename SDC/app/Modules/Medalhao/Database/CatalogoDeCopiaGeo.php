<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Database;

use App\Modules\Medalhao\Support\TabelasGeo;
use App\Support\Database\Geo\DominioGeo;

/**
 * Catalogo das tabelas de fato do geoespacial que migram do sdc para o banco
 * dedicado. Fonte unica para a copia, a verificacao e a retencao.
 *
 * - tabela: nome qualificado, igual nos dois bancos (fase 1 espelha o destino).
 * - chave: colunas do ON CONFLICT. Natural onde a tabela tem uma estavel, id nas demais.
 * - alteracao: expressao SQL do instante da ultima mudanca da linha, para --desde.
 * - particionada: leituras que no destino sao particionadas por mes em medido_em.
 */
final class CatalogoDeCopiaGeo
{
    /**
     * @return list<array{tabela: string, chave: list<string>, alteracao: string, particionada: bool}>
     */
    public static function todas(): array
    {
        $bronzeAlteracao = 'GREATEST(coletado_em, COALESCE(processado_em, coletado_em), COALESCE(verificado_em, coletado_em))';

        // Ordem importa: bronze antes das silver que o referenciam, camadas antes das feicoes.
        $tabelas = [];

        foreach (DominioGeo::cases() as $dominio) {
            $tabelas[] = self::spec(TabelasGeo::ingestaoBruta($dominio), ['id'], $bronzeAlteracao);
        }

        return [
            ...$tabelas,
            self::spec(TabelasGeo::estacoesInmet(), ['codigo']),
            self::spec(TabelasGeo::estacoesCemaden(), ['codigo']),
            self::spec(TabelasGeo::sismos(), ['id']),
            self::spec(TabelasGeo::leiturasInmet(), ['codigo_estacao', 'medido_em'], particionada: true),
            self::spec(TabelasGeo::leiturasCemaden(), ['codigo_estacao', 'medido_em'], particionada: true),
            self::spec(TabelasGeo::camadas(), ['id']),
            self::spec(TabelasGeo::feicoes(), ['id']),
        ];
    }

    /**
     * Seleciona por nome completo ou so pelo nome da tabela (ex.: leituras_cemaden).
     *
     * @param list<string> $nomes vazio devolve todas
     * @return list<array{tabela: string, chave: list<string>, alteracao: string, particionada: bool}>
     */
    public static function filtrar(array $nomes): array
    {
        if ($nomes === []) {
            return self::todas();
        }

        return array_values(array_filter(
            self::todas(),
            static function (array $spec) use ($nomes): bool {
                foreach ($nomes as $nome) {
                    if ($spec['tabela'] === $nome || str_ends_with($spec['tabela'], '.'.$nome)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }

    /**
     * @param list<string> $chave
     * @return array{tabela: string, chave: list<string>, alteracao: string, particionada: bool}
     */
    private static function spec(string $tabela, array $chave, string $alteracao = 'updated_at', bool $particionada = false): array
    {
        return [
            'tabela' => $tabela,
            'chave' => $chave,
            'alteracao' => $alteracao,
            'particionada' => $particionada,
        ];
    }
}
