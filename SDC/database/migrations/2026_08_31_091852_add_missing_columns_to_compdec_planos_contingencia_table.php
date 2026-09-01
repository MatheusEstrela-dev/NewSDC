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

        // O indice ja nasce na migration de criacao da tabela
        // (2026_05_08_160000), que tambem ja traz enviado_em. Em banco novo as
        // guardas de coluna acima pulam tudo e esta linha era a unica sem
        // guarda: recriava um indice existente e derrubava o migrate:fresh
        // inteiro. Em banco que rodou a versao antiga daquela migration, o
        // indice nao existe e continua sendo criado aqui.
        if (! Schema::hasIndex('compdec_planos_contingencia', ['orgao_id', 'enviado_em'])) {
            Schema::table('compdec_planos_contingencia', function (Blueprint $table) {
                $table->index(['orgao_id', 'enviado_em']);
            });
        }
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
