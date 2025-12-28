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
        Schema::create('beneficiarios', function (Blueprint $table) {
            $table->id();

            // Tipo de Cadastro
            $table->string('tipo_cadastro'); // INDIVIDUAL, FAMILIAR

            // Dados Pessoais
            $table->string('cpf')->unique()->nullable();
            $table->string('nome_responsavel');
            $table->string('rg')->nullable();
            $table->date('data_nascimento')->nullable();

            // Contato
            $table->string('telefone')->nullable();
            $table->string('email')->nullable();

            // Endereço
            $table->text('endereco_completo');
            $table->unsignedBigInteger('municipio_id');
            $table->string('bairro')->nullable();
            $table->string('cep')->nullable();

            // Família
            $table->integer('numero_membros_familia')->default(1);

            // Situação
            $table->string('situacao_vulnerabilidade'); // DESABRIGADO, DESALOJADO, ISOLADO, EM_RISCO, OUTRA
            $table->unsignedBigInteger('desastre_id')->nullable();
            $table->date('data_cadastro');
            $table->string('status')->default('ATIVO'); // ATIVO, INATIVO, FALECIDO

            // Observações
            $table->text('observacoes')->nullable();

            // Abrigo Atual
            $table->unsignedBigInteger('abrigo_atual_id')->nullable();
            $table->date('data_entrada_abrigo')->nullable();

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('cpf');
            $table->index('status');
            $table->index('situacao_vulnerabilidade');
            $table->index('abrigo_atual_id');
            $table->index('municipio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiarios');
    }
};
