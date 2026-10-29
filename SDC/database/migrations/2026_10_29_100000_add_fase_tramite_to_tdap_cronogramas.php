<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase de tramitacao do cronograma (FaseCronograma): quem precisa agir agora.
 *
 * Gravada, e nao derivada como o `estado`, porque `elaboracao`, `em_analise`
 * e `aprovado` sao o mesmo rascunho para as colunas existentes.
 *
 * Backfill: cronograma ja ativado (ou encerrado) entra direto em execucao; o
 * resto comeca em elaboracao e passa pela analise como qualquer novo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdap_cronogramas', function (Blueprint $table): void {
            $table->string('fase_tramite', 20)->default('elaboracao')->index();
        });

        DB::table('tdap_cronogramas')
            ->where(fn ($q) => $q->where('ativo', true)->orWhereNotNull('encerrado_em'))
            ->update(['fase_tramite' => 'em_execucao']);
    }

    public function down(): void
    {
        Schema::table('tdap_cronogramas', function (Blueprint $table): void {
            $table->dropIndex(['fase_tramite']);
            $table->dropColumn('fase_tramite');
        });
    }
};
