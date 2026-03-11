<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela rat_ocorrencia_historico — timeline dedicada de eventos RAT.
 *
 * Cada linha representa um evento imutável registrado pelo RatHistoricoService.
 * Não há updated_at pois registros nunca são alterados após inserção.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rat_ocorrencia_historico', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ocorrencia_id')
                  ->comment('ID da ocorrência à qual pertence este evento');

            $table->unsignedBigInteger('user_id')
                  ->nullable()
                  ->comment('Usuário que realizou a ação (null = sistema)');

            $table->string('evento', 100)
                  ->comment('Nome do evento (ex: envolvido.adicionado, ocorrencia.finalizada)');

            $table->json('payload')
                  ->nullable()
                  ->comment('Dados extras do evento: IDs, nomes, diffs de campos');

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Imutável — apenas created_at
            $table->timestamp('created_at')->useCurrent();

            // Chave estrangeira com cascade delete — histórico segue destino da ocorrência
            $table->foreign('ocorrencia_id', 'rat_hist_ocorrencia_id_fk')
                  ->references('id')
                  ->on('rat_ocorrencias')
                  ->cascadeOnDelete();

            $table->foreign('user_id', 'rat_hist_user_id_fk')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            $table->index('ocorrencia_id', 'rat_hist_ocorrencia_id_idx');
            $table->index(['ocorrencia_id', 'evento'], 'rat_hist_ocorrencia_evento_idx');
            $table->index('created_at', 'rat_hist_created_at_idx');

            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rat_ocorrencia_historico');
    }
};
