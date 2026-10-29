<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fase `entregue` (Entregues) no Acompanhamento: o cronograma encerrado sai de
 * `em_execucao`. Daqui em diante CronogramaService::encerrar grava a fase.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tdap_cronogramas')
            ->whereNotNull('encerrado_em')
            ->update(['fase_tramite' => 'entregue']);
    }

    public function down(): void
    {
        DB::table('tdap_cronogramas')
            ->where('fase_tramite', 'entregue')
            ->update(['fase_tramite' => 'em_execucao']);
    }
};
