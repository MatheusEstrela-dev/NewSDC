<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Database;

use App\Support\Database\Geo\DominioGeo;
use Illuminate\Database\Connection;

/**
 * Mantem referencia.municipios (copia de public.municipios do sdc) no destino.
 *
 * Os municipios mudam raramente e sao poucos, entao a copia e integral e o
 * hash do conjunto evita reescrever quando nada mudou. A mesma classe serve
 * ao banco dedicado (origem != destino) e ao modo desligado (mesma conexao).
 */
final class SincronizadorReferencia
{
    private const TABELA = 'municipios';

    private const LOTE = 500;

    private const COLUNAS = ['id', 'codigo_ibge', 'nome', 'uf', 'latitude', 'longitude'];

    /**
     * @return array{registros: int, alterou: bool}
     */
    public function sincronizar(Connection $origem, Connection $destino): array
    {
        $linhas = $origem->table('public.municipios')
            ->select(self::COLUNAS)
            ->orderBy('id')
            ->get()
            ->map(static fn (object $m): array => (array) $m)
            ->all();

        $hash = md5((string) json_encode($linhas));
        $controle = $destino->table(DominioGeo::REFERENCIA.'.sincronizacao')->where('tabela', self::TABELA);
        $anterior = $controle->first();

        $alterou = $anterior === null
            || $anterior->hash !== $hash
            || (int) $destino->table(DominioGeo::REFERENCIA.'.municipios')->count() !== count($linhas);

        if ($alterou) {
            $this->gravar($destino, $linhas);
        }

        $destino->table(DominioGeo::REFERENCIA.'.sincronizacao')->upsert(
            [['tabela' => self::TABELA, 'sincronizado_em' => now(), 'registros' => count($linhas), 'hash' => $hash]],
            ['tabela'],
            ['sincronizado_em', 'registros', 'hash'],
        );

        return ['registros' => count($linhas), 'alterou' => $alterou];
    }

    /** @param list<array<string, mixed>> $linhas */
    private function gravar(Connection $destino, array $linhas): void
    {
        $destino->transaction(function () use ($destino, $linhas): void {
            $tabela = DominioGeo::REFERENCIA.'.municipios';
            $agora = now();

            foreach (array_chunk($linhas, self::LOTE) as $lote) {
                $destino->table($tabela)->upsert(
                    array_map(static fn (array $l): array => $l + ['atualizado_em' => $agora], $lote),
                    ['id'],
                    [...array_slice(self::COLUNAS, 1), 'atualizado_em'],
                );
            }

            // Municipio que saiu do cadastro sai da copia.
            $destino->table($tabela)->whereNotIn('id', array_column($linhas, 'id'))->delete();
        });
    }
}
