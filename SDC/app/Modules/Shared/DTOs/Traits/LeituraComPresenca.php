<?php

declare(strict_types=1);

namespace App\Modules\Shared\DTOs\Traits;

/**
 * Leitura de entrada que lembra QUAIS colunas vieram no payload.
 *
 * E o contrato de update parcial do docs/plans/plano_dtos.md:
 *
 *  - chave AUSENTE  -> a coluna fica de fora do toArray() e o banco a preserva;
 *  - chave presente com null ou '' -> a coluna e limpa (null);
 *  - chave presente com false ou 0 -> o valor e gravado.
 *
 * O antigo `array_filter(fn ($v) => $v !== null && $v !== '')` misturava os
 * tres casos: impedia limpar um campo e, do outro lado, defaults como
 * `ativo ?? true` reescreviam colunas que o usuario nem enviou.
 *
 * Uso numa factory:
 *
 *     $presentes = [];
 *     return new self(
 *         nome: self::asStringOrNull(self::ler($dados, $presentes, 'nome')),
 *         ...,
 *         presentes: $presentes,
 *     );
 *
 * e no toArray():  return self::somentePresentes([...], $this->presentes);
 */
trait LeituraComPresenca
{
    /**
     * Le a coluna pelo nome plano ou por caminhos alternativos ("grupo.chave"
     * para payload aninhado). O primeiro caminho EXISTENTE vence, mesmo com
     * valor null -- e e isso que marca a coluna como presente.
     *
     * @param  array<array-key, mixed>  $dados
     * @param  array<int, string>  $presentes  acumulador, recebe $coluna se algum caminho existir
     */
    protected static function ler(array $dados, array &$presentes, string $coluna, string ...$alternativas): mixed
    {
        foreach ([$coluna, ...$alternativas] as $caminho) {
            [$existe, $valor] = self::buscar($dados, $caminho);

            if ($existe) {
                $presentes[] = $coluna;

                return $valor;
            }
        }

        return null;
    }

    /**
     * Mantem so as colunas presentes; string vazia vira null (limpar campo).
     * false e 0 passam intactos.
     *
     * @param  array<string, mixed>  $colunas
     * @param  array<int, string>  $presentes
     * @return array<string, mixed>
     */
    protected static function somentePresentes(array $colunas, array $presentes): array
    {
        $filtradas = array_intersect_key($colunas, array_flip($presentes));

        return array_map(fn ($valor) => $valor === '' ? null : $valor, $filtradas);
    }

    /** @return array{0: bool, 1: mixed} */
    private static function buscar(array $dados, string $caminho): array
    {
        $atual = $dados;

        foreach (explode('.', $caminho) as $parte) {
            if (! is_array($atual) || ! array_key_exists($parte, $atual)) {
                return [false, null];
            }

            $atual = $atual[$parte];
        }

        return [true, $atual];
    }
}
