<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Support\Facades\DB;


class RatProtocoloService
{
    public function generate(): string
    {
        return DB::transaction(function () {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT pg_advisory_xact_lock(hashtext('rat_protocolo_seq'))");
            }

            $year = now()->year;
            $seq  = $this->getLatestSequence($year) + 1;

            // Formato: YYYY-SEQUENCE-SUFFIX
            // SEQUENCE: número sequencial único (incrementa a cada novo RAT raiz)
            // SUFFIX: 000 para novo RAT, 001/002... para RATs relacionados
            return sprintf('%d-%09d-001', $year, $seq);
        });
    }

    private function getLatestSequence(int $year): int
    {
        $seq = 0;

<<<<<<< Updated upstream
        // Ordena pela parte numérica do sequencial (segundo segmento) para não
        // ser afetado pelo sufixo (terceiro segmento: 000 para raiz, 001+ para relacionados).
=======
        // O sufixo pode ser 000 (placeholder) ou o código real da unidade (ex: 123).
        // Busca por qualquer sufixo de 3 dígitos para não perder o sequencial
        // após o protocolo ser atualizado por saveDadosGerais.
>>>>>>> Stashed changes
        $latestOld = RatOcorrencia::withTrashed()
            ->where('numero_bos', 'like', "{$year}-%-%")
            ->where('numero_bos', 'not like', 'RAT-%')
            ->lockForUpdate()
            ->orderByRaw("CAST(SPLIT_PART(numero_bos, '-', 2) AS BIGINT) DESC")
            ->value('numero_bos');

<<<<<<< Updated upstream
        if ($latestOld && preg_match('/^\d{4}-(\d+)-\d{3}$/', $latestOld, $m)) {
            $seq = max($seq, (int) $m[1]);
=======
         if ($latestOld) {
            $seq = max($seq, (int) substr($latestOld, strrpos($latestOld, '-') + 1));
>>>>>>> Stashed changes
        }

        return $seq;
    }
}


