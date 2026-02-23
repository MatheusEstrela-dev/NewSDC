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
        if (!Schema::hasTable('pae_datas_ciclos')) {
            Schema::create('pae_datas_ciclos', function (Blueprint $table) {
                // id_ciclo bigint unsigned NOT NULL AUTO_INCREMENT
                $table->bigIncrements('id_ciclo');

                // Chave Estrangeira (protocolo_id_fk) referenciando pae_protocolos
                $table->foreignId('protocolo_id_fk')
                      ->constrained('pae_protocolos')
                      ->onDelete('cascade');

                // Ciclo 1
                $table->string('notificacao_1_ciclo_1', 255)->nullable();
                $table->date('data_entrada_1')->nullable();
                $table->date('data_saida_1')->nullable();

                // Ciclo 2
                $table->string('notificacao_2_ciclo_2', 255)->nullable();
                $table->date('data_entrada_2')->nullable();
                $table->date('data_saida_2')->nullable();

                // Ciclo 3
                $table->string('notificacao_3_ciclo_3', 255)->nullable();
                $table->date('data_entrada_3')->nullable();
                $table->date('data_saida_3')->nullable();

                // Timestamps (created_at e updated_at)
                $table->timestamps();

                // Índice adicional para o protocolo (opcional, pois foreignId já cria um)
                $table->index('protocolo_id_fk', 'idx_datas_ciclos_protocolo');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_datas_ciclos');
    }
};