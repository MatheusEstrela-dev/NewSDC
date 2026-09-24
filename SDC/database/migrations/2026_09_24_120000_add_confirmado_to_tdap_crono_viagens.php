<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Decisao do municipio: confirmada (1) ou reprovada (0).
 *
 * Ate aqui o COMPDEC so confirmava, e `confirmado_em` preenchido significava
 * "confirmada". Com a reprovacao, `confirmado_em`/`confirmado_por` passam a
 * registrar QUANDO e QUEM decidiu, e esta coluna diz O QUE -- no mesmo
 * contrato NULL/1/0 de `validado`.
 *
 * Aditiva: as viagens ja confirmadas recebem 1, e o resto segue NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdap_crono_viagens', function (Blueprint $table): void {
            $table->smallInteger('confirmado')->nullable()->after('confirmado_por');
        });

        DB::table('tdap_crono_viagens')
            ->whereNotNull('confirmado_em')
            ->update(['confirmado' => 1]);
    }

    public function down(): void
    {
        Schema::table('tdap_crono_viagens', function (Blueprint $table): void {
            $table->dropColumn('confirmado');
        });
    }
};
