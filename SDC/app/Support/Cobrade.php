<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Fonte unica da Classificacao e Codificacao Brasileira de Desastres (COBRADE).
 *
 * Leitura: tabela `dec_cobrade`, compartilhada por Decretacoes, Ajuda
 * Humanitaria e RAT.
 *
 * Texto oficial: app/Enums/classificacao_desastres.php, transcricao da tabela
 * publicada pela SEDEC (65 codigos, conferidos um a um contra o PDF em
 * Doc/04095316-cobrade-classificacao-e-codificacao-brasileira-de-desastres.pdf).
 * O codigo 3.0.0.0.0 ("Outros") e acrescimo do SDC e nao consta do padrao.
 *
 * A denominacao segue a mesma precedencia do accessor `tipo_desastre_nome` do
 * model Processo (a_definicao -> subtipo -> tipo -> subgrupo -> grupo), para que
 * o mesmo COBRADE apareca com o mesmo nome em todos os modulos.
 */
final class Cobrade
{
    public const CODIGO_OUTROS = '3.0.0.0.0';

    /**
     * Opcoes para <select>: `value` e o id de `dec_cobrade` (gravado em
     * nat_cobrade_id) e `codigo` e o COBRADE em si (gravado em nat_codigo).
     *
     * Sem cache estatico de proposito: sob Octane o processo e longevo e a
     * lista ficaria congelada apos um seed.
     *
     * `descricao` e a definicao oficial completa, exibida como ajuda abaixo do
     * select para o operador conferir se o enquadramento e o certo. `grupo` e o
     * primeiro nivel da hierarquia, usado pela tela para filtrar em cascata.
     *
     * @return array<int, array{value: int, codigo: string, label: string, descricao: string, grupo: string}>
     */
    public static function opcoes(): array
    {
        return DB::table('dec_cobrade')
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nome', 'descricao', 'grupo'])
            ->map(static function (object $c): array {
                $codigo    = trim((string) $c->codigo);
                $descricao = trim((string) $c->descricao);
                // A denominacao oficial e uma frase e termina em ponto; como
                // rotulo de <option> o ponto final so atrapalha a leitura.
                $nome      = rtrim(trim((string) ($c->nome ?: $descricao)), '.');

                return [
                    'value'     => (int) $c->id,
                    'codigo'    => $codigo,
                    'label'     => trim($codigo !== '' ? "{$codigo} - {$nome}" : $nome),
                    'nome'      => $nome,
                    'descricao' => $descricao,
                    'grupo'     => trim((string) $c->grupo),
                ];
            })
            ->all();
    }

    /**
     * Texto oficial por codigo COBRADE.
     *
     * @return array<string, array{nome: string, descricao: string, grupo: string}>
     */
    public static function oficiais(): array
    {
        $arquivo = app_path('Enums/classificacao_desastres.php');

        if (! is_file($arquivo)) {
            return [];
        }

        $mapa = [];

        foreach ((array) include $arquivo as $item) {
            $codigo = trim((string) ($item['cobrade'] ?? ''));

            if ($codigo === '') {
                continue;
            }

            $nome = trim((string) (
                $item['a_definicao']
                ?? $item['subtipo']
                ?? $item['tipo']
                ?? $item['subgrupo']
                ?? $item['grupo']
                ?? ''
            ));

            if ($nome === '') {
                continue;
            }

            $mapa[$codigo] = [
                // `nome` e varchar(255); `descricao` e text (a definicao oficial
                // mais longa tem 497 caracteres e nao cabe em varchar).
                'nome'      => mb_substr($nome, 0, 255),
                'descricao' => trim((string) ($item['definicao'] ?? $nome)),
                'grupo'     => mb_substr(trim((string) ($item['grupo'] ?? '')), 0, 255),
            ];
        }

        return $mapa;
    }
}
