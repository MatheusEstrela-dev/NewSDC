<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Arquivos;

use App\Modules\PowerBi\Support\DefinicaoTabela;
use App\Support\Database\ConexaoPowerBi;
use Carbon\Carbon;
use Generator;

/**
 * Le uma tabela bi_* inteira (somente removido=false, colunas expostas) em
 * lotes por keyset da PK: memoria constante, sem cursor aberto no servidor.
 */
final class LeitorTabelaBi
{
    private const INTEIROS = ['bigint', 'integer', 'smallint'];

    /**
     * Tipo por coluna exposta: int | bool | timestamptz | texto.
     *
     * @return array<string, string>
     */
    public function tipos(DefinicaoTabela $d): array
    {
        $linhas = ConexaoPowerBi::leitura()->select(
            'SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
            [$d->schema, $d->nome]
        );
        $porNome = [];
        foreach ($linhas as $l) {
            $porNome[$l->column_name] = $l->data_type;
        }

        $tipos = [];
        foreach ($d->expostas as $c) {
            $tipo = $porNome[$c] ?? 'text';
            $tipos[$c] = match (true) {
                in_array($tipo, self::INTEIROS, true) => 'int',
                $tipo === 'boolean' => 'bool',
                $tipo === 'timestamp with time zone' => 'timestamptz',
                default => 'texto',
            };
        }

        return $tipos;
    }

    /**
     * @param array<string, string> $tipos
     * @return Generator<int, list<array<string, int|bool|string|null>>>
     */
    public function lotes(DefinicaoTabela $d, array $tipos, int $tamanho): Generator
    {
        $selecionar = array_values(array_unique([...$d->expostas, ...$d->pk]));
        $marcadores = implode(', ', array_fill(0, count($d->pk), '?'));
        $apos = null;

        do {
            $q = ConexaoPowerBi::leitura()->table($d->alvo())->select($selecionar)->where('removido', false);
            if ($apos !== null) {
                $q->whereRaw('('.implode(', ', $d->pk).") > ({$marcadores})", $apos);
            }
            foreach ($d->pk as $c) {
                $q->orderBy($c);
            }
            $brutas = $q->limit($tamanho)->get();
            if ($brutas->isEmpty()) {
                return;
            }

            $ultima = (array) $brutas->last();
            $apos = array_map(fn (string $c): string => (string) $ultima[$c], $d->pk);

            yield $brutas->map(fn (object $l): array => $this->converter($tipos, (array) $l))->all();
        } while ($brutas->count() === $tamanho);
    }

    /** Data de corte dos dados (replay da fonte), ou null. */
    public function dadosAte(DefinicaoTabela $d): ?Carbon
    {
        $v = ConexaoPowerBi::leitura()->table('controle.cursores')
            ->where('modulo', $d->modulo)->where('tabela', $d->nome)->value('fonte_replay_em');

        return $v !== null ? Carbon::parse($v) : null;
    }

    /**
     * @param array<string, string> $tipos
     * @param array<string, mixed> $linha
     * @return array<string, int|bool|string|null>
     */
    private function converter(array $tipos, array $linha): array
    {
        $saida = [];
        foreach ($tipos as $c => $tipo) {
            $v = $linha[$c];
            $saida[$c] = match (true) {
                $v === null => null,
                $tipo === 'int' => (int) $v,
                $tipo === 'bool' => (bool) $v,
                $tipo === 'timestamptz' => Carbon::parse((string) $v)->utc()->toIso8601String(),
                default => (string) $v,
            };
        }

        return $saida;
    }
}
