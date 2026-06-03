<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // processos - Decretacoes (pagina mais lenta: TTFB 8.5s)
        Schema::table('processos', function (Blueprint $table) {
            $table->index('data_entrada', 'idx_processos_data_entrada');
            $table->index('tipo_desastre_id', 'idx_processos_tipo_desastre');
            $table->index(['status', 'data_entrada'], 'idx_processos_status_data');
            $table->index(['processo', 'status'], 'idx_processos_tipo_status');
            $table->index('deleted_at', 'idx_processos_soft_delete');
        });

        // processo_danos_humanos - FK municipio_id sem index
        Schema::table('processo_danos_humanos', function (Blueprint $table) {
            $table->index('municipio_id', 'idx_danos_humanos_municipio');
        });

        // processo_danos_materiais - FK municipio_id sem index
        if (Schema::hasTable('processo_danos_materiais')) {
            Schema::table('processo_danos_materiais', function (Blueprint $table) {
                $table->index('municipio_id', 'idx_danos_materiais_municipio');
            });
        }

        // processo_prejuizos - FK municipio_id sem index
        Schema::table('processo_prejuizos', function (Blueprint $table) {
            $table->index('municipio_id', 'idx_prejuizos_municipio');
        });

        // beneficiarios - composite para listagem com filtro
        Schema::table('beneficiarios', function (Blueprint $table) {
            $table->index(['status', 'municipio_id'], 'idx_beneficiarios_status_municipio');
            $table->index('desastre_id', 'idx_beneficiarios_desastre');
            $table->index('created_by', 'idx_beneficiarios_created_by');
        });

        // inscricoes - FK sem index explicito
        if (Schema::hasTable('inscricoes')) {
            Schema::table('inscricoes', function (Blueprint $table) {
                if (Schema::hasColumn('inscricoes', 'treinamento_id')) {
                    $table->index('treinamento_id', 'idx_inscricoes_treinamento');
                }
                if (Schema::hasColumn('inscricoes', 'user_id')) {
                    $table->index('user_id', 'idx_inscricoes_user');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropIndex('idx_processos_data_entrada');
            $table->dropIndex('idx_processos_tipo_desastre');
            $table->dropIndex('idx_processos_status_data');
            $table->dropIndex('idx_processos_tipo_status');
            $table->dropIndex('idx_processos_soft_delete');
        });

        Schema::table('processo_danos_humanos', function (Blueprint $table) {
            $table->dropIndex('idx_danos_humanos_municipio');
        });

        if (Schema::hasTable('processo_danos_materiais')) {
            Schema::table('processo_danos_materiais', function (Blueprint $table) {
                $table->dropIndex('idx_danos_materiais_municipio');
            });
        }

        Schema::table('processo_prejuizos', function (Blueprint $table) {
            $table->dropIndex('idx_prejuizos_municipio');
        });

        Schema::table('beneficiarios', function (Blueprint $table) {
            $table->dropIndex('idx_beneficiarios_status_municipio');
            $table->dropIndex('idx_beneficiarios_desastre');
            $table->dropIndex('idx_beneficiarios_created_by');
        });

        if (Schema::hasTable('inscricoes')) {
            Schema::table('inscricoes', function (Blueprint $table) {
                if (Schema::hasIndex('inscricoes', 'idx_inscricoes_treinamento')) {
                    $table->dropIndex('idx_inscricoes_treinamento');
                }
                if (Schema::hasIndex('inscricoes', 'idx_inscricoes_user')) {
                    $table->dropIndex('idx_inscricoes_user');
                }
            });
        }
    }
};
