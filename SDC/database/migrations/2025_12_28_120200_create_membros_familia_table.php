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
        Schema::create('membros_familia', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->unsignedBigInteger('beneficiario_id');

            // Dados Pessoais
            $table->string('nome');
            $table->string('cpf')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('parentesco'); // CONJUGE, FILHO, PAI, MAE, IRMAO, AVO, NETO, OUTRO

            // Deficiência
            $table->boolean('possui_deficiencia')->default(false);
            $table->string('tipo_deficiencia')->nullable();

            // Observações
            $table->text('observacoes')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('beneficiario_id')
                  ->references('id')
                  ->on('beneficiarios')
                  ->onDelete('cascade');

            // Índices
            $table->index('beneficiario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membros_familia');
    }
};
