<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

/**
 * Copia uma tabela do Treinamento de uma conexao para outra, em lotes por PK
 * (keyset), com upsert: rodar de novo, ou rodar o delta, nao duplica nada.
 *
 * Sem estado de conexao entre chamadas: origem e destino vem por parametro.
 */
final class CopiaDeTabela
{
    /** Teto de parametros do Postgres e margem para o upsert. */
    private const MAX_PARAMETROS = 60000;

    /**
     * @param  string  $tabela  qualificada (schema.tabela)
     * @param  string  $pk  chave primaria (id, ou email no reset de senha)
     */
    public function __construct(
        private readonly string $tabela,
        private readonly string $pk,
    ) {}

    /**
     * Uma copia por tabela de cada schema, com a PK lida do catalogo: quem
     * apaga o schema precisa conferir todas, nao so as que a copia lista.
     *
     * @param  list<string>  $schemas
     * @return list<self>
     *
     * @throws \RuntimeException tabela sem PK (nao da para ordenar o hash)
     */
    public static function dosSchemas(Connection $conexao, array $schemas): array
    {
        $copias = [];

        foreach ($schemas as $schema) {
            $tabelas = $conexao->select(
                "SELECT c.relname AS tabela,
                        (SELECT a.attname FROM pg_index i JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = i.indkey[0]
                          WHERE i.indrelid = c.oid AND i.indisprimary) AS pk
                 FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
                 WHERE n.nspname = ? AND c.relkind IN ('r', 'p') AND NOT c.relispartition
                 ORDER BY c.relname",
                [$schema],
            );

            foreach ($tabelas as $t) {
                if ($t->pk === null) {
                    throw new \RuntimeException("{$schema}.{$t->tabela} nao tem chave primaria");
                }

                $copias[] = new self($schema.'.'.$t->tabela, (string) $t->pk);
            }
        }

        return $copias;
    }

    /** Contagem e hash do conteudo diferem entre origem e destino. */
    public function divergente(Connection $origem, Connection $destino): bool
    {
        $colunas = $this->colunas($origem);

        return $this->resumo($origem, $colunas) !== $this->resumo($destino, $colunas);
    }

    public function tabela(): string
    {
        return $this->tabela;
    }

    public function existeEm(Connection $conexao): bool
    {
        return $conexao->selectOne('SELECT to_regclass(?) AS oid', [$this->tabela])->oid !== null;
    }

    /**
     * @return list<string> colunas em ordem alfabetica (ordem estavel entre bancos)
     */
    public function colunas(Connection $conexao): array
    {
        [$schema, $nome] = explode('.', $this->tabela, 2);

        return array_map(
            static fn (object $c): string => $c->column_name,
            $conexao->select(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY column_name',
                [$schema, $nome],
            ),
        );
    }

    public function contar(Connection $conexao, ?string $desde = null): int
    {
        return (int) $this->consulta($conexao, $desde)->count();
    }

    /**
     * Copia (ou so conta, em dry-run) e devolve quantas linhas foram lidas.
     */
    public function copiar(Connection $origem, Connection $destino, int $lote, ?string $desde, bool $simular): int
    {
        $colunas = $this->colunas($origem);
        $lote = max(1, min($lote, intdiv(self::MAX_PARAMETROS, max(1, count($colunas)))));

        $total = 0;
        $ultimo = null;

        do {
            $consulta = $this->consulta($origem, $desde)->orderBy($this->pk)->limit($lote);

            if ($ultimo !== null) {
                $consulta->where($this->pk, '>', $ultimo);
            }

            $linhas = $consulta->get();

            if ($linhas->isEmpty()) {
                break;
            }

            $ultimo = $linhas->last()->{$this->pk};
            $total += $linhas->count();

            if (! $simular) {
                $this->gravar($destino, $linhas->map(static fn (object $l): array => (array) $l)->all(), $colunas);
            }
        } while ($linhas->count() === $lote);

        return $total;
    }

    /**
     * Sequence do id volta a ficar a frente do maior id copiado; sem isso o
     * proximo insert no banco novo bateria na PK de uma linha ja copiada.
     */
    public function ajustarSequence(Connection $destino): void
    {
        if ($this->pk !== 'id') {
            return;
        }

        $destino->statement(
            "SELECT setval(pg_get_serial_sequence('{$this->tabela}', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM {$this->tabela}"
        );
    }

    /**
     * Contagem e hash do conteudo inteiro, para comparar origem e destino. O
     * hash usa ROW(colunas em ordem alfabetica)::text, entao independe da
     * ordem fisica das colunas em cada banco.
     *
     * @param  list<string>  $colunas
     * @return array{contagem: int, hash: string}
     */
    public function resumo(Connection $conexao, array $colunas): array
    {
        $lista = implode(', ', array_map(static fn (string $c): string => '"'.$c.'"', $colunas));

        $r = $conexao->selectOne(
            "SELECT count(*) AS contagem, coalesce(md5(string_agg(md5(ROW({$lista})::text), '' ORDER BY \"{$this->pk}\")), '') AS hash FROM {$this->tabela}"
        );

        return ['contagem' => (int) $r->contagem, 'hash' => (string) $r->hash];
    }

    /**
     * @param  list<array<string, mixed>>  $linhas
     * @param  list<string>  $colunas
     */
    private function gravar(Connection $destino, array $linhas, array $colunas): void
    {
        $atualizar = array_values(array_diff($colunas, [$this->pk]));

        $destino->table($this->tabela)->upsert($linhas, [$this->pk], $atualizar);
    }

    /**
     * Delta: so linhas tocadas desde a data. Linha sem data nenhuma entra
     * sempre (copiar a mais e inofensivo, a menos nao).
     */
    private function consulta(Connection $conexao, ?string $desde): Builder
    {
        $consulta = $conexao->table($this->tabela);

        if ($desde !== null) {
            // So as colunas de data que a tabela tem (cidadao_password_reset_tokens
            // so tem created_at).
            $datas = array_values(array_intersect(['updated_at', 'created_at'], $this->colunas($conexao)));

            if ($datas !== []) {
                $expressao = count($datas) > 1 ? 'COALESCE(' . implode(', ', $datas) . ')' : $datas[0];

                $consulta->where(static function ($q) use ($desde, $expressao): void {
                    $q->whereRaw($expressao . ' >= ?', [$desde])
                        ->orWhereRaw($expressao . ' IS NULL');
                });
            }
        }

        return $consulta;
    }
}
