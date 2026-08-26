<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantoes', function (Blueprint $table) {
            $table->foreignId('plantonista_saida_id')->nullable()->after('plantonista_nome')
                ->constrained('users')->nullOnDelete();
            $table->string('plantonista_saida_nome')->nullable()->after('plantonista_saida_id');

            $table->string('localizacao', 60)->nullable()->after('periodo');
            $table->text('ocorrencias_destaque')->nullable()->after('observacoes');

            $table->dateTime('encerrado_em')->nullable()->after('ocorrencias_destaque');
            // Quem declarou o estado. Quando difere de plantonista_id, o
            // encerramento foi feito por terceiro (ver secao 4.3 do spec).
            $table->foreignId('encerrado_por_id')->nullable()->after('encerrado_em')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('aceito_em')->nullable()->after('encerrado_por_id');
            $table->foreignId('aceito_por_id')->nullable()->after('aceito_em')
                ->constrained('users')->nullOnDelete();
            $table->text('divergencia')->nullable()->after('aceito_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('plantoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plantonista_saida_id');
            $table->dropConstrainedForeignId('encerrado_por_id');
            $table->dropConstrainedForeignId('aceito_por_id');
            $table->dropColumn([
                'plantonista_saida_nome',
                'localizacao',
                'ocorrencias_destaque',
                'encerrado_em',
                'aceito_em',
                'divergencia',
            ]);
        });
    }
};
