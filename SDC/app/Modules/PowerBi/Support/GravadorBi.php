<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Support\Database\ConexaoPowerBi;
use DateTimeInterface;
use Generator;

/**
 * Gravacao idempotente nas tabelas bi_*. Nao guarda Connection: cada metodo
 * pede ConexaoPowerBi::escrita() (sob pool por coroutine a conexao vale so
 * para a coroutine que a obteve).
 *
 * Hash = sha256 do json canonico das colunas de dados da linha (sem colunas de
 * controle). A linha so e regravada (e materializado_em so avanca) quando o
 * hash muda ou ela estava marcada como removida.
 */
final class GravadorBi
{
    /** Limite do protocolo do Postgres: 65535 parametros por statement. */
    private const MAX_PARAMETROS = 65535;

    private const CONDICIONAL = 'condicional';

    private const INCONDICIONAL = 'incondicional';

    private const SEM_CONFLITO = 'sem_conflito';

    /** Hash hexadecimal (sha256) da linha; usado tambem pelo VerificadorParidade. */
    public function hash(DefinicaoTabela $d, array $linha): string
    {
        $dados = [];
        foreach ($d->colunas as $coluna) {
            $dados[$coluna] = $linha[$coluna] ?? null;
        }

        return hash('sha256', json_encode(
            $this->canonico($dados),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        ));
    }

    /**
     * Upsert em lote; so grava o que mudou. Com $coletarChaves devolve em
     * $chaves as PKs gravadas (para o materializador regravar filhas desses pais).
     *
     * @param iterable<array<string, mixed>> $linhas
     * @param list<array<string, mixed>>|null $chaves
     */
    public function gravar(DefinicaoTabela $d, iterable $linhas, ?array &$chaves = null): ResultadoGravacao
    {
        $resultado = new ResultadoGravacao();

        foreach ($this->lotes($linhas, $this->tamanhoLote($d)) as $lote) {
            $pronto = $this->preparar($d, $lote);
            $resultado->lidas += count($lote);

            if ($chaves !== null) {
                $retornadas = ConexaoPowerBi::escrita()->select(
                    $this->sqlInsert($d->alvo(), $d, count($pronto), self::CONDICIONAL).' RETURNING '.implode(', ', $d->pk),
                    $this->binds($pronto)
                );
                foreach ($retornadas as $r) {
                    $chaves[] = (array) $r;
                }
                $resultado->gravadas += count($retornadas);
            } else {
                $resultado->gravadas += ConexaoPowerBi::escrita()->affectingStatement(
                    $this->sqlInsert($d->alvo(), $d, count($pronto), self::CONDICIONAL),
                    $this->binds($pronto)
                );
            }
        }

        return $resultado;
    }

    /**
     * Tabela filha: apaga as linhas dos pais informados e reinsere. Chame so
     * com os pais que mudaram, na mesma transacao do pai.
     *
     * @param list<int|string> $idsPai
     * @param iterable<array<string, mixed>> $linhas
     */
    public function gravarFilhas(DefinicaoTabela $filha, array $idsPai, iterable $linhas): ResultadoGravacao
    {
        $resultado = new ResultadoGravacao();
        $db = ConexaoPowerBi::escrita();

        foreach (array_chunk($idsPai, 5000) as $grupo) {
            $in = implode(', ', array_fill(0, count($grupo), '?'));
            $db->delete("DELETE FROM {$filha->alvo()} WHERE {$filha->colunaPai} IN ({$in})", $grupo);
        }

        foreach ($this->lotes($linhas, $this->tamanhoLote($filha)) as $lote) {
            $pronto = $this->preparar($filha, $lote);
            $resultado->lidas += count($lote);
            $resultado->gravadas += $db->affectingStatement(
                $this->sqlInsert($filha->alvo(), $filha, count($pronto), self::SEM_CONFLITO),
                $this->binds($pronto)
            );
        }

        return $resultado;
    }

    /**
     * Estrategia completa: carrega tudo numa tabela de staging temporaria e, na
     * mesma transacao, mescla no alvo (so o que mudou) e marca como removido o
     * que nao veio. O alvo nunca fica vazio nem parcial para quem le.
     *
     * @param iterable<array<string, mixed>> $linhas
     */
    public function gravarCompleta(DefinicaoTabela $d, iterable $linhas): ResultadoGravacao
    {
        return ConexaoPowerBi::escrita()->transaction(function () use ($d, $linhas): ResultadoGravacao {
            $db = ConexaoPowerBi::escrita();
            $stg = 'pg_temp.stg_'.$d->nome;
            $pk = implode(', ', $d->pk);

            $db->statement("DROP TABLE IF EXISTS {$stg}");
            $db->statement("CREATE TEMP TABLE stg_{$d->nome} (LIKE {$d->alvo()} INCLUDING DEFAULTS) ON COMMIT DROP");
            $db->statement("ALTER TABLE {$stg} ADD PRIMARY KEY ({$pk})");

            foreach ($this->lotes($linhas, $this->tamanhoLote($d)) as $lote) {
                $pronto = $this->preparar($d, $lote);
                $db->affectingStatement($this->sqlInsert($stg, $d, count($pronto), self::INCONDICIONAL), $this->binds($pronto));
            }

            $lidas = (int) $db->selectOne("SELECT count(*) AS n FROM {$stg}")->n;

            $cols = implode(', ', [...$d->colunas, 'origem_atualizado_em', 'hash']);
            $gravadas = $db->affectingStatement(
                "INSERT INTO {$d->alvo()} AS alvo ({$cols}) SELECT {$cols} FROM {$stg} s WHERE true"
                .$this->clausulaConflito($d, self::CONDICIONAL)
            );

            $junta = implode(' AND ', array_map(fn (string $c): string => "s.{$c} = a.{$c}", $d->pk));
            $removidas = $db->affectingStatement(
                "UPDATE {$d->alvo()} a SET removido = true, materializado_em = now()
                 WHERE NOT a.removido AND NOT EXISTS (SELECT 1 FROM {$stg} s WHERE {$junta})"
            );

            return new ResultadoGravacao($lidas, $gravadas, $removidas);
        });
    }

    /** Linhas por statement: lote configurado, limitado pelo teto de parametros. */
    private function tamanhoLote(DefinicaoTabela $d): int
    {
        $porStatement = max(1, intdiv(self::MAX_PARAMETROS, count($d->colunas) + 2));

        return max(1, min((int) config('powerbi.lote', 1000), $porStatement));
    }

    /**
     * Valores na ordem das colunas + origem_atualizado_em + hash; deduplica a
     * PK dentro do lote (o ultimo vence) para o ON CONFLICT nao falhar.
     *
     * @param list<array<string, mixed>> $lote
     * @return list<list<mixed>>
     */
    private function preparar(DefinicaoTabela $d, array $lote): array
    {
        $porChave = [];
        foreach ($lote as $linha) {
            $valores = [];
            foreach ($d->colunas as $coluna) {
                $valores[] = $this->valor($linha[$coluna] ?? null);
            }
            $valores[] = $this->valor($linha['origem_atualizado_em'] ?? null);
            $valores[] = $this->hash($d, $linha);
            $porChave[$d->chaveDe($linha)] = $valores;
        }

        return array_values($porChave);
    }

    /** @param list<list<mixed>> $linhas */
    private function binds(array $linhas): array
    {
        return array_merge(...$linhas);
    }

    private function sqlInsert(string $alvo, DefinicaoTabela $d, int $linhas, string $modo): string
    {
        $colunas = implode(', ', [...$d->colunas, 'origem_atualizado_em', 'hash']);
        $uma = '('.implode(', ', array_fill(0, count($d->colunas) + 1, '?'))." , decode(?, 'hex'))";
        $valores = implode(', ', array_fill(0, $linhas, $uma));

        $sql = "INSERT INTO {$alvo} AS alvo ({$colunas}) VALUES {$valores}";

        return $modo === self::SEM_CONFLITO ? $sql : $sql.$this->clausulaConflito($d, $modo);
    }

    private function clausulaConflito(DefinicaoTabela $d, string $modo): string
    {
        $sets = [];
        foreach ([...array_diff($d->colunas, $d->pk), 'origem_atualizado_em', 'hash'] as $c) {
            $sets[] = "{$c} = EXCLUDED.{$c}";
        }
        $sets[] = 'materializado_em = now()';
        $sets[] = 'removido = false';

        $sql = ' ON CONFLICT ('.implode(', ', $d->pk).') DO UPDATE SET '.implode(', ', $sets);

        return $modo === self::CONDICIONAL
            ? $sql.' WHERE alvo.hash IS DISTINCT FROM EXCLUDED.hash OR alvo.removido'
            : $sql;
    }

    private function valor(mixed $v): mixed
    {
        return match (true) {
            is_bool($v) => $v ? 'true' : 'false',
            is_array($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $v instanceof DateTimeInterface => $v->format('Y-m-d\TH:i:s.uP'),
            default => $v,
        };
    }

    /** Ordena chaves de mapas recursivamente para o json ser estavel. */
    private function canonico(mixed $v): mixed
    {
        if ($v instanceof DateTimeInterface) {
            return $v->format('Y-m-d\TH:i:s.uP');
        }
        if (! is_array($v)) {
            return $v;
        }
        $v = array_map(fn (mixed $x): mixed => $this->canonico($x), $v);
        if (! array_is_list($v)) {
            ksort($v);
        }

        return $v;
    }

    /**
     * @param iterable<array<string, mixed>> $itens
     * @return Generator<int, list<array<string, mixed>>>
     */
    private function lotes(iterable $itens, int $tamanho): Generator
    {
        $lote = [];
        foreach ($itens as $item) {
            $lote[] = $item;
            if (count($lote) >= $tamanho) {
                yield $lote;
                $lote = [];
            }
        }
        if ($lote !== []) {
            yield $lote;
        }
    }
}
