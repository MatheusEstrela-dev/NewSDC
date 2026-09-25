<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

use Illuminate\Support\Facades\DB;

/**
 * Correspondencia origem -> destino em cedec_demanda_import_maps. E ela que torna
 * a carga idempotente: registro com hash igual e pulado, com hash diferente e
 * atualizado no mesmo destino, e nunca se liga nada por id numerico igual.
 */
final class MapaImportacao
{
    private const TABELA = 'cedec_demanda_import_maps';

    public function alvo(string $tabela, string $id): ?int
    {
        $v = DB::table(self::TABELA)->where(['source_table' => $tabela, 'source_id' => $id, 'status' => 'importado'])->value('target_id');

        return $v === null ? null : (int) $v;
    }

    public function hash(string $tabela, string $id): ?string
    {
        return DB::table(self::TABELA)->where(['source_table' => $tabela, 'source_id' => $id])->value('source_hash');
    }

    public function registrar(string $tabela, string $id, string $alvoTabela, ?int $alvoId, string $hash, string $status, ?string $motivo = null): void
    {
        DB::table(self::TABELA)->updateOrInsert(
            ['source_table' => $tabela, 'source_id' => $id],
            [
                'target_table' => $alvoTabela, 'target_id' => $alvoId, 'source_hash' => $hash,
                'status' => $status, 'rejection_reason' => $motivo,
                'imported_at' => $status === 'importado' ? now() : null, 'updated_at' => now(), 'created_at' => now(),
            ],
        );
    }
}
