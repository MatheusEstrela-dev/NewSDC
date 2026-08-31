<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use Illuminate\Support\Facades\DB;

/**
 * Leitura paginada de cisterna_legado_raw, decodificando o doc jsonb.
 */
final class LeitorRaw
{
    /**
     * @param  callable(array<string, mixed> $doc, int $legacyId): void  $callback
     */
    public function porTabela(string $tabela, int $chunk, callable $callback): void
    {
        DB::table('cisterna_legado_raw')
            ->where('tabela', $tabela)
            ->orderBy('id')
            ->chunkById($chunk, function ($linhas) use ($callback): void {
                foreach ($linhas as $linha) {
                    $doc = json_decode((string) $linha->doc, true);

                    $callback(is_array($doc) ? $doc : [], (int) $linha->pk_legado);
                }
            });
    }

    public function contar(string $tabela): int
    {
        return DB::table('cisterna_legado_raw')->where('tabela', $tabela)->count();
    }
}
