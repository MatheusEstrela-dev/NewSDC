<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corretiva de drift: a migration principal (2026_05_14_140001) ja define
 * ativado_em/encerrado_em/arquivado_em, mas foi editada DEPOIS de ja ter
 * rodado em producao, entao essas colunas nunca chegaram ao banco
 * (artisan migrate nao re-executa migration aplicada). Sem arquivado_em,
 * App\Modules\Tdap\Models\Cronograma::scopeArquivado/NaoArquivado e os
 * Resources quebram com "column arquivado_em does not exist".
 *
 * Idempotente (hasColumn): so adiciona o que faltar, seguro re-rodar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdap_cronogramas', function (Blueprint $table) {
            if (! Schema::hasColumn('tdap_cronogramas', 'ativado_em')) {
                $table->timestampTz('ativado_em')->nullable();
            }
            if (! Schema::hasColumn('tdap_cronogramas', 'encerrado_em')) {
                $table->timestampTz('encerrado_em')->nullable();
            }
            if (! Schema::hasColumn('tdap_cronogramas', 'arquivado_em')) {
                $table->timestampTz('arquivado_em')->nullable()
                    ->comment('Arquivamento (distinto do soft delete deleted_at)');
            }
        });
    }

    public function down(): void
    {
        // Sem rollback destrutivo: as colunas pertencem a migration principal.
    }
};
