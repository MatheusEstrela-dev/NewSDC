<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('compdec_planos_contingencia', function (Blueprint $table) {
            if (! Schema::hasColumn('compdec_planos_contingencia', 'created_by')) {
                $table->foreignId('created_by')->nullable()
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->nullOnDelete()
                    ->comment('Usuario que cadastrou; recebe a trilha de acoes do registro');
            }

            if (! Schema::hasColumn('compdec_planos_contingencia', 'enviado_em')) {
                $table->timestamp('enviado_em')->nullable()
                    ->comment('Data do upload original; no legado, com_plano_upload.dt_upload');
            }

            if (! Schema::hasColumn('compdec_planos_contingencia', 'legacy_municipio_id')) {
                $table->unsignedBigInteger('legacy_municipio_id')->nullable()
                    ->comment('id_municipio legado (cedec_municipio.id) para reconciliacao');
            }
        });

        Schema::table('compdec_planos_contingencia', function (Blueprint $table) {
            $table->index(['orgao_id', 'enviado_em']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compdec_planos_contingencia', function (Blueprint $table) {
            $table->dropIndex(['orgao_id', 'enviado_em']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'enviado_em', 'legacy_municipio_id']);
        });
    }
};
