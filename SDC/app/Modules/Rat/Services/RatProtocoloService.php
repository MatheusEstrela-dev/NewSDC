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
            // Advisory lock serializes concurrent inserts when the table is empty
            // (lockForUpdate cannot lock non-existent rows)
            DB::statement("SELECT pg_advisory_xact_lock(hashtext('rat_protocolo_seq'))");

            $year = now()->year;
            $seq  = $this->getLatestSequence($year) + 1;
            return sprintf('RAT-%d-%06d', $year, $seq);
        });
    }

    private function getLatestSequence(int $year): int
    {
        $latest = RatOcorrencia::withTrashed()
            ->where('numero_bos', 'like', "RAT-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('numero_bos')
            ->value('numero_bos');

        if (!$latest) {
            return 0;
        }

        return (int) substr($latest, strrpos($latest, '-') + 1);
    }
}
