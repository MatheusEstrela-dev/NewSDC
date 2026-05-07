<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de log do ETL legado -> novo (spec § 7.4).
 * Efemera: drop apos 60 dias de validacao em producao.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compdec_etl_log')) {
            return;
        }

        Schema::create('compdec_etl_log', function (Blueprint $table) {
            $table->id();

            $table->string('recurso', 40)
                ->comment('orgaos|prefeituras|equipes|anexos|planos');

            $table->string('legacy_table', 40);
            $table->unsignedBigInteger('legacy_id');
            $table->unsignedBigInteger('new_id')->nullable();

            $table->string('acao', 20)
                ->comment('inserted|updated|skipped|error');

            $table->text('motivo')->nullable();
            $table->jsonb('payload_legado')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['recurso', 'acao']);
            $table->index('legacy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compdec_etl_log');
    }
};
