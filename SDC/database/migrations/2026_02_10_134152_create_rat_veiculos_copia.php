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
        Schema::create('rat_veiculos', function (Blueprint $table) {
            $table->id();

            // Dados do Veículo com Unique na Placa
            $table->string('placa', 10)
                  ->unique('rat_veiculos_placa_unique')
                  ->comment('Placa do veículo');

            $table->string('modelo', 100)->comment('Modelo do veículo');
            $table->string('marca', 50)->comment('Marca do veículo');

            // Status e Auditoria
            $table->boolean('ativo')->default(1)->comment('Veículo ativo');
            $table->timestamps();
            $table->softDeletes();

            // Índices adicionais para performance
            $table->index('placa', 'idx_placa');
            $table->index('ativo', 'idx_ativo');
            $table->index('deleted_at', 'idx_deleted_at');

            // Configurações do Banco
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_veiculos');
    }
};