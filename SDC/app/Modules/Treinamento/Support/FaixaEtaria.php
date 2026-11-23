<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

/**
 * Faixas etarias do painel de indicadores. Fonte unica: a expressao SQL que
 * classifica a inscricao e a lista (ordem e rotulo) que o painel exibe saem
 * da mesma tabela FAIXAS.
 *
 * Idade = anos completos na data da inscricao, nao hoje: o perfil e de quem
 * participou quando participou. Sem data de nascimento (inscricao antiga) ou
 * com data impossivel (depois da inscricao) = nao informado.
 */
final class FaixaEtaria
{
    public const NAO_INFORMADO = 'nao_informado';

    private const ROTULO_NAO_INFORMADO = 'Não informado';

    /**
     * faixa => [idade maxima (inclusive) ou null na ultima, rotulo], em ordem
     * crescente. Grupos definidos pela gestora: 18 a 20 e depois de 10 em 10.
     */
    private const FAIXAS = [
        'ate_17' => [17, 'Até 17'],
        '18_20' => [20, '18 a 20'],
        '21_30' => [30, '21 a 30'],
        '31_40' => [40, '31 a 40'],
        '41_50' => [50, '41 a 50'],
        '51_60' => [60, '51 a 60'],
        '61_mais' => [null, '61 ou mais'],
    ];

    /**
     * Faixa => rotulo na ordem de exibicao, nao informado por ultimo.
     *
     * @return array<string, string>
     */
    public static function rotulos(): array
    {
        $rotulos = array_map(fn (array $faixa) => $faixa[1], self::FAIXAS);
        $rotulos[self::NAO_INFORMADO] = self::ROTULO_NAO_INFORMADO;

        return $rotulos;
    }

    /**
     * CASE que devolve a chave da faixa. Os argumentos sao expressoes SQL
     * confiaveis (colunas), nunca entrada do usuario.
     *
     * @param  string  $nascimento  expressao date da data de nascimento
     * @param  string  $referencia  expressao date da data em que se mede a idade
     */
    public static function expressaoSql(string $nascimento, string $referencia): string
    {
        $idade = "EXTRACT(YEAR FROM AGE({$referencia}, {$nascimento}))";
        $nao = self::NAO_INFORMADO;

        $casos = ["WHEN {$nascimento} IS NULL OR {$nascimento} > {$referencia} THEN '{$nao}'"];
        $ultima = null;
        foreach (self::FAIXAS as $chave => [$maxima]) {
            if ($maxima === null) {
                $ultima = $chave;

                continue;
            }
            $casos[] = "WHEN {$idade} <= {$maxima} THEN '{$chave}'";
        }

        return 'CASE ' . implode(' ', $casos) . " ELSE '{$ultima}' END";
    }
}
