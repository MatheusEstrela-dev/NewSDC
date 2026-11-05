<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Support;

use App\Support\Database\ConexaoGeo;

/**
 * REFRESH CONCURRENTLY das matviews gold pela conexao de escrita do geo.
 *
 * CONCURRENTLY exige o indice unico da matview e nao roda dentro de transacao:
 * quem chama nao pode estar num DB::transaction(). A ordem dos nomes importa
 * quando uma matview le outra (estatisticas depois do mapa).
 */
final class RefreshGold
{
    public static function executar(string ...$matviews): void
    {
        $conexao = ConexaoGeo::conexao();

        foreach ($matviews as $matview) {
            $conexao->statement("REFRESH MATERIALIZED VIEW CONCURRENTLY {$matview}");
        }
    }
}
