<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cabecalho da escala de um mes. O planejamento -- quem DEVERIA trabalhar.
 *
 * Nao confundir com `plantoes`, que e a execucao: o turno efetivamente
 * trabalhado, com passagem de servico e aceite formal. Sao entidades distintas
 * ligadas por plantoes.escala_item_id, e e essa ligacao que permite responder
 * "quem faltou ao plantao escalado".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_escalas', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('ano');
            $table->unsignedTinyInteger('mes');
            $table->string('titulo', 120)->nullable();

            // RASCUNHO -> PUBLICADA -> ARQUIVADA. Publicar e a transicao que
            // dispara notificacao; em rascunho ninguem e avisado de nada.
            $table->string('status', 20)->default('RASCUNHO');

            $table->timestamp('publicada_em')->nullable();
            $table->foreignId('publicada_por_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('criada_por_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->text('observacoes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['ano', 'mes']);
        });

        // Uma escala viva por mes. Parcial porque a escala arquivada e
        // soft-deleted precisa poder conviver com a nova do mesmo mes.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX plantao_escalas_mes_unico
                ON plantao_escalas (ano, mes)
                WHERE deleted_at IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_escalas');
    }
};
