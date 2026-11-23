<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Enums\CategoriaOferta;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ponto unico da regra que poe uma turma numa categoria de oferta. A mesma
 * regra existe em PHP (classificar) e em SQL (condicaoSql, usada por
 * restringir e totais), lado a lado, para a vitrine filtrar no banco e a tela
 * classificar a linha ja carregada sem divergir:
 *
 *   EVENTO      categoria do treinamento = EVENTO (qualquer modalidade);
 *   PRESENCIAL  senao, modalidade PRESENCIAL (inteiramente presencial);
 *   TUTORIA     senao, ONLINE ou SEMI_PRESENCIAL (sempre), ou EAD com
 *               com_tutoria = true;
 *   LIVRE       senao (EAD), com_tutoria = false.
 *
 * INTEGRACAO_SABERES nao classifica turma (e o modulo de materiais).
 */
final class ClassificacaoDeOferta
{
    /** Modalidades que sao tutoria por natureza, sem depender do com_tutoria. */
    private const TIPOS_SEMPRE_TUTORIA = [TipoTreinamento::ONLINE, TipoTreinamento::SEMI_PRESENCIAL];

    public static function classificar(?CategoriaTreinamento $categoria, ?TipoTreinamento $tipo, bool $comTutoria): CategoriaOferta
    {
        if ($categoria === CategoriaTreinamento::EVENTO) {
            return CategoriaOferta::EVENTO;
        }

        if ($tipo === TipoTreinamento::PRESENCIAL) {
            return CategoriaOferta::PRESENCIAL;
        }

        if (in_array($tipo, self::TIPOS_SEMPRE_TUTORIA, true) || $comTutoria) {
            return CategoriaOferta::TUTORIA;
        }

        return CategoriaOferta::LIVRE;
    }

    /**
     * A opcao "com tutoria" so existe para EAD fora de evento: ONLINE e
     * semi-presencial ja sao tutoria por natureza, e presencial e evento nao
     * mudam de categoria (gravam false).
     */
    public static function admiteTutoria(?CategoriaTreinamento $categoria, ?TipoTreinamento $tipo): bool
    {
        return $categoria !== CategoriaTreinamento::EVENTO && $tipo === TipoTreinamento::EAD;
    }

    /** Espelho em SQL de classificar(), sobre a tabela de treinamentos. */
    public static function restringir(Builder $query, CategoriaOferta $oferta): Builder
    {
        return $query->whereRaw(self::condicaoSql($oferta, $query->getModel()->getTable()));
    }

    /**
     * Fonte unica da regra em SQL (valores sao constantes de enum, sem entrada
     * do usuario). categoria e tipo sao NOT NULL: "<>" e "NOT IN" nao perdem
     * linhas por NULL. As quatro condicoes sao exclusivas e cobrem tudo.
     */
    private static function condicaoSql(CategoriaOferta $oferta, string $tabela): string
    {
        $evento = CategoriaTreinamento::EVENTO->value;
        $naoEvento = "{$tabela}.categoria <> '{$evento}'";
        $presencial = TipoTreinamento::PRESENCIAL->value;
        $sempreTutoria = implode(', ', array_map(fn (TipoTreinamento $t) => "'{$t->value}'", self::TIPOS_SEMPRE_TUTORIA));
        $ead = TipoTreinamento::EAD->value;

        return match ($oferta) {
            CategoriaOferta::EVENTO => "{$tabela}.categoria = '{$evento}'",
            CategoriaOferta::PRESENCIAL => "{$naoEvento} AND {$tabela}.tipo = '{$presencial}'",
            CategoriaOferta::TUTORIA => "{$naoEvento} AND ({$tabela}.tipo IN ({$sempreTutoria}) OR ({$tabela}.tipo = '{$ead}' AND {$tabela}.com_tutoria = true))",
            CategoriaOferta::LIVRE => "{$naoEvento} AND {$tabela}.tipo = '{$ead}' AND {$tabela}.com_tutoria = false",
            CategoriaOferta::INTEGRACAO_SABERES => '1 = 0',
        };
    }

    /**
     * Total por categoria de uma consulta base: uma unica consulta agrupada
     * (SUM de CASE por categoria), com a mesma condicao SQL de restringir.
     *
     * @return array<string, int>
     */
    public static function totais(Builder $base): array
    {
        $tabela = $base->getModel()->getTable();
        $ofertas = CategoriaOferta::classificaveis();

        $consulta = $base->toBase()->cloneWithout(['columns', 'orders', 'limit', 'offset']);
        foreach ($ofertas as $i => $oferta) {
            $consulta->selectRaw('COALESCE(SUM(CASE WHEN ' . self::condicaoSql($oferta, $tabela) . " THEN 1 ELSE 0 END), 0) AS c{$i}");
        }
        $linha = $consulta->first();

        $totais = [];
        foreach ($ofertas as $i => $oferta) {
            $totais[$oferta->value] = (int) ($linha->{"c{$i}"} ?? 0);
        }

        return $totais;
    }

    /**
     * Total por categoria de turmas ja carregadas.
     *
     * @param  iterable<\App\Modules\Treinamento\Models\Treinamento>  $turmas
     * @return array<string, int>
     */
    public static function totaisDe(iterable $turmas): array
    {
        $totais = [];

        foreach ($turmas as $turma) {
            $valor = $turma->categoriaOferta()->value;
            $totais[$valor] = ($totais[$valor] ?? 0) + 1;
        }

        return $totais;
    }
}
