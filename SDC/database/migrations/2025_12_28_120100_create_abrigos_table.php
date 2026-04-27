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
        Schema::create('abrigos', function (Blueprint $table) {
            $table->id();

            // Informações Básicas
            $table->string('nome');
            $table->string('tipo_local'); // GINASIO, ESCOLA, IGREJA, OUTRO

            // Endereço
            $table->text('endereco_completo');
            $table->unsignedBigInteger('municipio_id');
            $table->string('bairro')->nullable();
            $table->string('cep')->nullable();
            $table->decimal('coordenadas_lat', 10, 8)->nullable();
            $table->decimal('coordenadas_lng', 11, 8)->nullable();

            // Capacidade
            $table->integer('capacidade_maxima');
            $table->integer('ocupacao_atual')->default(0);

            // Responsável
            $table->string('responsavel_nome');
            $table->string('responsavel_telefone');
            $table->string('responsavel_cargo')->nullable();

            // Status
            $table->string('status')->default('ATIVO'); // ATIVO, LOTADO, DESATIVADO, MANUTENCAO
            $table->date('data_abertura');
            $table->date('data_fechamento')->nullable();

            // Infraestrutura (JSON)
            $table->jsonb('infraestrutura')->nullable();
            $table->index('infraestrutura', 'idx_abrigos_infraestrutura', 'gin');
            $table->jsonb('recursos_disponiveis')->nullable();
            $table->index('recursos_disponiveis', 'idx_abrigos_recursos_disponiveis', 'gin');

            // Observações
            $table->text('observacoes')->nullable();

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
            $table->index('municipio_id');
            $table->index('data_abertura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abrigos');
    }
};
