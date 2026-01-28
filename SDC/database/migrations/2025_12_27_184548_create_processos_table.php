<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processos', function (Blueprint $table) {
            $table->id();


            // Datas
            $table->foreignId('orgao_responsavel_id')
                ->nullable()
                ->constrained('orgaos')
                ->nullOnDelete()
                ->comment('COMPDEC responsável pelo processo');

            $table->date('data_entrada')->nullable();
            $table->date('data_ocorrencia_desastre')->nullable();
            $table->date('data_decreto_municipal')->nullable();
            $table->date('data_publicacao_mg')->nullable();
            $table->date('data_publicacao_diario')->nullable();

            // Tipo e Status
            $table->string('processo')->nullable(); // TipoProcesso enum
            $table->string('tipo_decreto')->nullable(); // TipoDecreto enum
            $table->string('status')->default('registro'); // StatusProcesso enum

            // Informações do Processo
            $table->string('analista')->nullable();
            $table->string('n_protocolo_fide')->nullable();
            $table->string('decreto_municipal')->nullable();
            $table->integer('prazo_vigencia')->nullable();

            // Tipo de Desastre
            $table->unsignedBigInteger('tipo_desastre_id')->nullable();
            $table->string('tipo_desastre_nome')->nullable();

            // Reconhecimento
            $table->string('reconhecimento_decreto_n_data')->nullable();
            $table->string('portaria_reconhecimento_fed')->nullable();
            $table->string('portaria_diario_oficial')->nullable();
            $table->string('reconhecimento_federal')->nullable();

            // Observações e SEI
            $table->text('observacoes')->nullable();
            $table->boolean('processo_inserido_sei')->default(false);

            // Área afetada (geometria)
            $table->text('area_afetada_geom')->nullable();

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
            $table->index('processo');
            $table->index('data_publicacao_mg');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processos');
    }
};
