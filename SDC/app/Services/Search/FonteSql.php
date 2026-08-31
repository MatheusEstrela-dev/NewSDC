<?php

declare(strict_types=1);

namespace App\Services\Search;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Base das fontes que consultam uma tabela por trigram.
 *
 * O formato da consulta e sempre o mesmo, e a repeticao dele em cada modulo foi
 * o que travou o crescimento da busca:
 *
 *   WHERE <coluna> ILIKE '%termo%'  -> filtra pelo indice GIN trigram
 *   ORDER BY similarity(...) DESC   -> ordena o punhado que sobrou
 *
 * A ordem importa com o banco crescendo. `similarity()` no WHERE calcularia o
 * score de TODAS as linhas antes de filtrar; no ILIKE o indice corta primeiro e
 * o score roda so sobre o resultado, que e limitado. Sem o indice trigram, o
 * ILIKE com curinga a esquerda vira varredura completa e o custo cresce junto
 * com a tabela -- por isso cada coluna listada em `colunas()` PRECISA ter o
 * indice correspondente.
 */
abstract class FonteSql implements FonteDeBusca
{
    /** Tabela consultada. */
    abstract protected function tabela(): string;

    /**
     * Colunas pesquisaveis, na ordem de relevancia.
     *
     * @return array<int, string>
     */
    abstract protected function colunas(): array;

    /**
     * Colunas trazidas alem das pesquisaveis, para montar a linha do resultado.
     *
     * @return array<int, string>
     */
    abstract protected function selecionar(): array;

    /**
     * Converte a linha do banco no contrato do palette.
     *
     * @return array<string, mixed>
     */
    abstract protected function linha(object $registro): array;

    /**
     * Condicao extra, por exemplo o soft delete ou o recorte territorial.
     * Recebe bindings por referencia para a fonte poder parametrizar.
     *
     * @param  array<string, mixed>  $bindings
     */
    protected function filtroAdicional(array &$bindings): string
    {
        return '';
    }

    public function permissao(): ?string
    {
        return null;
    }

    public function buscar(string $termo, int $limite): array
    {
        try {
            $rows = DB::select($this->sql($limite), $this->bindings($termo));

            return array_map(fn (object $r): array => $this->linha($r), $rows);
        } catch (Throwable $e) {
            // Fonte quebrada nao pode derrubar a busca inteira: o usuario perde
            // um grupo de resultados, nao a caixa de pesquisa.
            report($e);

            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function bindings(string $termo): array
    {
        // Sem `limite`: ele e interpolado no SQL, e nao vinculado. LIMIT com
        // placeholder exige PDO::PARAM_INT explicito -- o servico antigo tinha
        // um array de tipos so por causa disso. Como o valor vem de config e
        // passa por (int), interpolar e seguro e some com a engenhoca.
        $bindings = ['termo' => $termo, 'curinga' => '%'.$termo.'%'];

        $this->filtroAdicional($bindings);

        return $bindings;
    }

    protected function sql(int $limite): string
    {
        $colunas = $this->colunas();

        // A coluna entra NUA no WHERE, exatamente como o indice a declara.
        //
        // Envolver em COALESCE(...::text, '') parecia defensivo e anulava o
        // indice: o planejador so casa a expressao indexada com a expressao da
        // consulta, e qualquer envelope quebra o casamento. Medido nas 8.099
        // linhas de cisternas: Seq Scan de 40ms com o indice existindo.
        //
        // O COALESCE nao fazia falta: `NULL ILIKE '%x%'` da NULL, e a linha e
        // descartada -- que e o comportamento desejado.
        //
        // Um unico bind reaproveitado em todas as colunas: nomear :like1,
        // :like2... como o servico antigo fazia obrigava a contar parametros a
        // mao e errava toda vez que uma coluna entrava ou saia.
        $onde = implode(' OR ', array_map(
            static fn (string $c): string => "{$c} ILIKE :curinga",
            $colunas,
        ));

        $score = implode(', ', array_map(
            static fn (string $c): string => "similarity(COALESCE({$c}::text, ''), :termo)",
            $colunas,
        ));

        $score = count($colunas) > 1 ? "GREATEST({$score})" : $score;

        $campos = implode(', ', array_unique([...$this->selecionar(), ...$colunas]));

        $bindings = [];
        $extra = $this->filtroAdicional($bindings);
        $extra = $extra === '' ? '' : "AND ({$extra})";

        return "
            SELECT {$campos}, {$score} AS score
            FROM {$this->tabela()}
            WHERE ({$onde})
            {$extra}
            ORDER BY score DESC
            LIMIT {$limite}
        ";
    }
}
