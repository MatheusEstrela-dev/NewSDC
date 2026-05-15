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

            // Formato: YYYY-NNNNNNNNN-000
            // O sufixo 000 é substituído pelo código real da unidade
            // ao preencher os dados gerais (uni_bo_cod_unidade).
            return sprintf('%d-%09d-000', $year, $seq);
        });
    }

    private function getLatestSequence(int $year): int
    {
        $seq = 0;

        // Formato atual: YYYY-NNNNNNNNN-000
        $latestNew = RatOcorrencia::withTrashed()
            ->where('numero_bos', 'like', "{$year}-%-000")
            ->lockForUpdate()
            ->orderByDesc('numero_bos')
            ->value('numero_bos');

        if ($latestNew && preg_match('/^\d{4}-(\d+)-000$/', $latestNew, $m)) {
            $seq = max($seq, (int) $m[1]);
        }

        // Formato legado: RAT-YYYY-NNNNNN
        $latestOld = RatOcorrencia::withTrashed()
            ->where('numero_bos', 'like', "RAT-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('numero_bos')
            ->value('numero_bos');

        if ($latestOld) {
            $seq = max($seq, (int) substr($latestOld, strrpos($latestOld, '-') + 1));
        }

        return $seq;
    }
}
