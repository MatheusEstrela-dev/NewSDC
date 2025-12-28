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
        Schema::create('doacoes', function (Blueprint $table) {
            $table->id();

            // Tipo de Doação
            $table->string('tipo_doacao'); // MONETARIA, MATERIAL

            // Dados do Doador
            $table->string('doador_tipo'); // PESSOA_FISICA, PESSOA_JURIDICA, ANONIMO
            $table->string('doador_nome')->nullable();
            $table->string('doador_cpf_cnpj')->nullable();
            $table->string('doador_telefone')->nullable();
            $table->string('doador_email')->nullable();

            // Data
            $table->date('data_doacao');

            // Doação Monetária
            $table->decimal('valor_monetario', 10, 2)->nullable();
            $table->string('forma_recebimento')->nullable(); // DINHEIRO, PIX, TED, CHEQUE
            $table->string('comprovante_path')->nullable();

            // Observações
            $table->text('observacoes')->nullable();

            // Status
            $table->string('status')->default('PENDENTE'); // PENDENTE, RECEBIDA, PROCESSADA, CANCELADA

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tipo_doacao');
            $table->index('status');
            $table->index('data_doacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doacoes');
    }
};
