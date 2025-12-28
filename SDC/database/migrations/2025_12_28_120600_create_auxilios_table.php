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
        Schema::create('auxilios', function (Blueprint $table) {
            $table->id();

            // Tipo e Descrição
            $table->string('tipo_auxilio'); // KIT_ALIMENTACAO, KIT_HIGIENE, KIT_LIMPEZA, MONETARIO, OUTRO
            $table->string('descricao');

            // Data
            $table->date('data_distribuicao');

            // Relacionamentos
            $table->unsignedBigInteger('beneficiario_id');
            $table->unsignedBigInteger('abrigo_id')->nullable();

            // Auxílio Monetário
            $table->decimal('valor_monetario', 10, 2)->nullable();

            // Entrega
            $table->string('responsavel_entrega');
            $table->string('comprovante_path')->nullable();

            // Observações
            $table->text('observacoes')->nullable();

            // Status
            $table->string('status')->default('PLANEJADO'); // PLANEJADO, ENTREGUE, CANCELADO

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('beneficiario_id')
                  ->references('id')
                  ->on('beneficiarios')
                  ->onDelete('restrict');

            // Índices
            $table->index('beneficiario_id');
            $table->index('abrigo_id');
            $table->index('tipo_auxilio');
            $table->index('status');
            $table->index('data_distribuicao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auxilios');
    }
};
