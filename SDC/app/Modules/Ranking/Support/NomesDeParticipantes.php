<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Support;

use App\Modules\Ranking\Enums\EscopoPlacar;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Hidratacao de nome dos participantes do placar, feita NA BORDA.
 *
 * O LeaderboardQuery le so a base do ranking e devolve ids; o nome mora na base
 * operacional. Buscar aqui, depois da consulta e so para os ids que vao para a
 * tela, mantem o placar de pe quando a origem cai: nome e enfeite, o placar e o
 * dado. Por isso qualquer falha devolve [] e o chamador exibe o codigo.
 *
 * A conexao e resolvida pelo NOME configurado (ranking.conexao_origem), nunca
 * por autowiring de ConnectionInterface: o container entregaria a conexao
 * default, que e a base operacional com credencial de escrita.
 *
 * Octane: sem estado de instancia; cada chamada consulta de novo.
 */
final class NomesDeParticipantes
{
    /**
     * @param  array<int, int|string|null>  $ids
     * @return array<int, string> mapa id => nome; id sem registro fica de fora
     */
    public function para(EscopoPlacar $escopo, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));

        // Sem ids nao ha o que mostrar: evita ida ao banco (e a abertura da
        // conexao de origem) numa pagina vazia.
        if ($ids === []) {
            return [];
        }

        [$tabela, $coluna] = self::origem($escopo);

        try {
            // Registro com soft delete continua com nome: os pontos historicos
            // seguem no placar mesmo depois da exclusao do cadastro.
            return DB::connection((string) config('ranking.conexao_origem'))
                ->table($tabela)
                ->whereIn('id', $ids)
                ->pluck($coluna, 'id')
                ->mapWithKeys(static fn ($nome, $id): array => [(int) $id => trim((string) $nome)])
                ->filter(static fn (string $nome): bool => $nome !== '')
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /** @return array{0: string, 1: string} tabela e coluna de nome na base de origem */
    private static function origem(EscopoPlacar $escopo): array
    {
        return match ($escopo) {
            EscopoPlacar::Usuario => ['users', 'name'],
            EscopoPlacar::Orgao => ['compdec_orgaos', 'nome'],
            EscopoPlacar::Municipio => ['municipios', 'nome'],
        };
    }
}
